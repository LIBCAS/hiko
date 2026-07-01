<?php

namespace App\Services;

use App\Models\GlobalIdentity;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GlobalIdentityStrictMergeOdsService
{
    public const CONFIRM_TOKEN = 'strict-merge-global-identities-from-ods';

    private const ODS_TO_SCALAR_FIELDS = [
        'surname' => 'surname',
        'forename' => 'forename',
        'name' => 'name',
        'birth_year' => 'birth_year',
        'death_year' => 'death_year',
        'type' => 'type',
    ];

    private const MAJORITY_SCALAR_FIELDS = [
        'general_name_modifier',
        'gender',
        'viaf_id',
    ];

    public function __construct(
        private readonly GlobalIdentityStrictMergeService $mergeService,
    ) {
    }

    public function runFromPublicFile(string $relativePath, array $options = []): array
    {
        $path = $this->resolvePublicPath($relativePath);

        return $this->run($this->readOdsRows($path), $options + [
            'source' => $relativePath,
        ]);
    }

    public function run(array $rows, array $options = []): array
    {
        $dryRun = (bool)($options['dry_run'] ?? true);
        $start = max(0, (int)($options['start'] ?? 0));
        $groupLimit = max(0, (int)($options['group_limit'] ?? 0));
        $recordLimit = max(0, (int)($options['record_limit'] ?? 0));

        $normalizedRows = $this->normalizeRows($rows);
        $duplicateGroups = $this->duplicateGroups($normalizedRows);
        $selectedGroups = $this->sliceGroups($duplicateGroups, $start, $groupLimit, $recordLimit);

        $results = [];
        $summary = [
            'source' => $options['source'] ?? null,
            'dry_run' => $dryRun,
            'rows_read' => count($normalizedRows),
            'duplicate_groups_detected' => count($duplicateGroups),
            'start' => $start,
            'group_limit' => $groupLimit ?: null,
            'record_limit' => $recordLimit ?: null,
            'groups_selected' => count($selectedGroups),
            'source_records_selected' => array_sum(array_map(fn(array $group) => count($group['rows']), $selectedGroups)),
            'groups_would_merge' => 0,
            'groups_merged' => 0,
            'groups_skipped' => 0,
            'groups_failed' => 0,
            'next_start' => null,
        ];

        foreach ($selectedGroups as $group) {
            $result = $this->processGroup($group, $dryRun);
            $results[] = $result;

            if (($result['status'] ?? null) === 'merged') {
                $summary['groups_merged']++;
            } elseif (($result['status'] ?? null) === 'would_merge') {
                $summary['groups_would_merge']++;
            } elseif (($result['status'] ?? null) === 'failed') {
                $summary['groups_failed']++;
            } else {
                $summary['groups_skipped']++;
            }
        }

        $processedUntil = $start + count($selectedGroups);
        $summary['next_start'] = $processedUntil < count($duplicateGroups) ? $processedUntil : null;

        return [
            'summary' => $summary,
            'results' => $results,
        ];
    }

    public function normalizeRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row, int $index): array {
                $id = (int)($row['id'] ?? 0);

                return [
                    'source_row' => (int)($row['source_row'] ?? ($index + 1)),
                    'id' => $id,
                    'name' => $this->cleanValue($row['name'] ?? ''),
                    'surname' => $this->cleanValue($row['surname'] ?? ''),
                    'forename' => $this->cleanValue($row['forename'] ?? ''),
                    'birth_year' => $this->cleanYear($row['birth_year'] ?? ''),
                    'death_year' => $this->cleanYear($row['death_year'] ?? ''),
                    'type' => $this->cleanValue($row['type'] ?? ''),
                    'general_name_modifier' => $this->cleanValue($row['general_name_modifier'] ?? ''),
                    'gender' => $this->cleanValue($row['gender'] ?? ''),
                    'viaf_id' => $this->cleanValue($row['viaf_id'] ?? ''),
                    'raw' => $row,
                ];
            })
            ->filter(fn(array $row): bool => $row['id'] > 0 && $row['name'] !== '')
            ->values()
            ->all();
    }

    public function duplicateGroups(array $rows): array
    {
        return collect($rows)
            ->groupBy(fn(array $row): string => $this->duplicateKey($row))
            ->filter(fn(Collection $group): bool => $group->count() >= 2)
            ->map(function (Collection $group, string $key): array {
                $rows = $group
                    ->sortBy('source_row')
                    ->values()
                    ->all();

                return [
                    'key' => $key,
                    'name' => $rows[0]['name'] ?? '',
                    'birth_year' => $rows[0]['birth_year'] ?? '',
                    'death_year' => $rows[0]['death_year'] ?? '',
                    'ids' => collect($rows)->pluck('id')->values()->all(),
                    'rows' => $rows,
                ];
            })
            ->values()
            ->all();
    }

    private function processGroup(array $group, bool $dryRun): array
    {
        $ids = collect($group['ids'])
            ->map(fn($id): int => (int)$id)
            ->filter(fn(int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $result = [
            'status' => $dryRun ? 'would_merge' : 'skipped',
            'key' => $group['key'],
            'name' => $group['name'],
            'birth_year' => $group['birth_year'],
            'death_year' => $group['death_year'],
            'ids' => $ids,
            'survivor_id' => $ids[0] ?? null,
            'deleted_ids' => count($ids) >= 2 ? array_values(array_slice($ids, 1)) : [],
            'source_rows' => collect($group['rows'])->pluck('source_row')->all(),
            'selected_scalars' => [],
            'message' => null,
        ];

        if (count($ids) < 2) {
            return array_merge($result, [
                'status' => 'skipped',
                'message' => 'Group has fewer than two unique IDs.',
            ]);
        }

        $records = $this->mergeService->getPreviewRecords($ids);
        if ($records->count() !== count($ids)) {
            $existingIds = $records->pluck('id')->map(fn($id): int => (int)$id)->all();

            return array_merge($result, [
                'status' => 'skipped',
                'existing_ids' => $existingIds,
                'missing_ids' => array_values(array_diff($ids, $existingIds)),
                'message' => 'One or more selected global identities no longer exist.',
            ]);
        }

        if (!$this->mergeService->hasSingleType($records)) {
            return array_merge($result, [
                'status' => 'skipped',
                'record_types' => $records->pluck('type', 'id')->all(),
                'message' => 'Group contains mixed identity types.',
            ]);
        }

        try {
            $scalarSelections = $this->scalarSelections($records, $group['rows']);
            $multiSelections = $this->mergeService->defaultMultiSelections($records);
            $result['selected_scalars'] = $this->describeScalarSelections($records, $scalarSelections);

            if ($dryRun) {
                return $result;
            }

            $mergeResult = $this->mergeService->execute(
                $ids,
                $result['survivor_id'],
                $scalarSelections,
                $multiSelections
            );

            return array_merge($result, [
                'status' => 'merged',
                'merge_result' => $mergeResult,
            ]);
        } catch (\Throwable $e) {
            return array_merge($result, [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function scalarSelections(Collection $records, array $rows): array
    {
        $selections = [];
        $fields = $this->mergeService->fieldsForType($records->first()?->type);

        foreach (self::ODS_TO_SCALAR_FIELDS as $rowField => $field) {
            if (!in_array($field, $fields, true)) {
                continue;
            }

            $value = $this->preferredOdsValue($rows, $rowField);
            if ($value === '' && $field === 'type') {
                $value = (string)$records->first()?->type;
            }

            if ($value !== '') {
                $selections[$field] = $this->scalarKeyForValue($records, $field, $value);
            }
        }

        foreach (self::MAJORITY_SCALAR_FIELDS as $field) {
            if (!in_array($field, $fields, true)) {
                continue;
            }

            $value = $this->preferredNonEmptyValue($records, $rows, $field);
            if ($value !== '') {
                $selections[$field] = $this->scalarKeyForValue($records, $field, $value);
            }
        }

        return $selections;
    }

    private function describeScalarSelections(Collection $records, array $scalarSelections): array
    {
        $descriptions = [];

        foreach ($scalarSelections as $field => $key) {
            $option = collect($this->mergeService->scalarOptions($records, $field))
                ->firstWhere('key', $key);
            $descriptions[$field] = $option['value'] ?? null;
        }

        return $descriptions;
    }

    private function preferredOdsValue(array $rows, string $field): string
    {
        $nonEmpty = collect($rows)
            ->map(fn(array $row): string => $this->cleanValue($row[$field] ?? ''))
            ->filter(fn(string $value): bool => $value !== '')
            ->values();

        if ($nonEmpty->isEmpty()) {
            return '';
        }

        return (string)$nonEmpty->countBy()->sortDesc()->keys()->first();
    }

    private function preferredNonEmptyValue(Collection $records, array $rows, string $field): string
    {
        $rowValues = collect($rows)
            ->mapWithKeys(fn(array $row): array => [(int)$row['id'] => $this->cleanValue($row[$field] ?? '')])
            ->filter(fn(string $value): bool => $value !== '');

        $values = $records
            ->map(function (GlobalIdentity $record) use ($rowValues, $field): array {
                return [
                    'id' => (int)$record->id,
                    'value' => $rowValues[(int)$record->id] ?? $this->cleanValue($record->getAttribute($field)),
                ];
            })
            ->filter(fn(array $item): bool => $item['value'] !== '')
            ->values();

        if ($values->isEmpty()) {
            return '';
        }

        $counts = [];
        foreach ($values as $item) {
            $counts[$item['value']] = ($counts[$item['value']] ?? 0) + 1;
        }

        $max = max($counts);
        $candidates = array_keys(array_filter($counts, fn(int $count): bool => $count === $max));
        $selected = $values
            ->filter(fn(array $item): bool => in_array((string)$item['value'], $candidates, true))
            ->sortBy('id')
            ->first();

        return (string)($selected['value'] ?? '');
    }

    private function scalarKeyForValue(Collection $records, string $field, string $value): string
    {
        $option = collect($this->mergeService->scalarOptions($records, $field))
            ->first(fn(array $option): bool => $this->sameScalarValue($option['value'] ?? null, $value));

        if (!$option) {
            throw new \InvalidArgumentException("Value '{$value}' for {$field} is not available among selected records.");
        }

        return (string)$option['key'];
    }

    private function sliceGroups(array $groups, int $start, int $groupLimit, int $recordLimit): array
    {
        $selected = [];
        $recordCount = 0;

        foreach (array_slice($groups, $start) as $group) {
            if ($groupLimit > 0 && count($selected) >= $groupLimit) {
                break;
            }

            $groupRecords = count($group['rows']);
            if ($recordLimit > 0 && $selected !== [] && ($recordCount + $groupRecords) > $recordLimit) {
                break;
            }

            $selected[] = $group;
            $recordCount += $groupRecords;
        }

        return $selected;
    }

    private function readOdsRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheet(0);
        $rawRows = $sheet->toArray(null, true, true, true);
        $header = [];
        $rows = [];

        foreach ($rawRows as $rowNumber => $rawRow) {
            $values = array_map(fn($value): string => $this->cleanValue($value), $rawRow);

            if ($header === []) {
                $header = $this->normalizeHeader($values);
                continue;
            }

            if (collect($values)->filter(fn(string $value): bool => $value !== '')->isEmpty()) {
                continue;
            }

            $row = ['source_row' => $rowNumber];
            foreach ($header as $column => $field) {
                if ($field !== null) {
                    $row[$field] = $values[$column] ?? '';
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function normalizeHeader(array $values): array
    {
        return collect($values)
            ->mapWithKeys(function (string $value, string $column): array {
                return [$column => match ($this->headerKey($value)) {
                    'id' => 'id',
                    'name' => 'name',
                    'surname', 'prijmeni' => 'surname',
                    'forename', 'firstname', 'jmeno' => 'forename',
                    'birthyear', 'birth', 'narozeni' => 'birth_year',
                    'deathyear', 'death', 'umrti' => 'death_year',
                    'type' => 'type',
                    'generalnamemodifier', 'modifier' => 'general_name_modifier',
                    'gender' => 'gender',
                    'viafid', 'viaf' => 'viaf_id',
                    default => null,
                }];
            })
            ->all();
    }

    private function resolvePublicPath(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');
        $path = public_path($relativePath);
        $realPath = realpath($path);
        $publicPath = realpath(public_path());

        if (!$realPath || !$publicPath || !str_starts_with($realPath, $publicPath . DIRECTORY_SEPARATOR)) {
            throw new \InvalidArgumentException('The source file must exist inside the public directory.');
        }

        if (!str_ends_with(strtolower($realPath), '.ods')) {
            throw new \InvalidArgumentException('The source file must be an ODS file.');
        }

        return $realPath;
    }

    private function duplicateKey(array $row): string
    {
        return implode('|', [
            mb_strtolower(preg_replace('/\s+/', ' ', $row['name'])),
            $row['birth_year'],
            $row['death_year'],
        ]);
    }

    private function sameScalarValue($left, $right): bool
    {
        return $this->cleanValue($left) === $this->cleanValue($right);
    }

    private function cleanYear($value): string
    {
        $value = $this->cleanValue($value);

        return preg_replace('/\.0$/', '', $value) ?? $value;
    }

    private function cleanValue($value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', (string)$value) ?? '');
    }

    private function headerKey(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower($value)) ?? '';
    }
}
