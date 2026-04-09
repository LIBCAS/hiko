<?php

namespace App\Livewire;

use App\Models\Letter;
use App\Models\Language;
use App\Models\OcrSnapshot;
use App\Services\DocumentService;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class OcrUpload extends Component
{
    use WithFileUploads;

    public const APPLY_SELECTED = 'selected';
    public const APPLY_EMPTY = 'empty';
    public const APPLY_OVERWRITE = 'overwrite';

    public ?int $letterId = null;
    public array $photos = [];
    public bool $isProcessing = false;
    public string $ocrText = '';
    public array $metadata = [];
    public array $uploadedFiles = [];
    public string $selectedModel = DocumentService::MODEL_GEMINI_FLASH_2;   // Selected by default
    public string $applyMode = self::APPLY_SELECTED;
    public ?int $selectedSnapshotId = null;
    public array $snapshots = [];
    public array $transientMappedFields = [];
    public array $selectedFields = [];
    public bool $selectAllMappedFields = false;
    public array $fieldDiffs = [];
    public array $currentFormValues = [];

    protected $rules = [
        'photos.*' => 'file|mimes:jpeg,jpg,png,doc,docx,pdf|max:20480',
    ];

    protected $messages = [];

    public function mount(?int $letterId = null): void
    {
        $this->letterId = $letterId;
        $this->messages = [
            'photos.*.file' => __('hiko.photo_file'),
            'photos.*.mimes' => __('hiko.photo_mimes'),
            'photos.*.max' => __('hiko.photo_max'),
        ];

        if ($this->letterId) {
            $this->currentFormValues = $this->resolveLetterValues($this->letterId);
            $this->loadSnapshots();
        }
    }

    public function uploadAndProcess(DocumentService $documentService): void
    {
        $this->validate();

        if (count($this->photos) === 0) {
            $this->addError('photos', __('hiko.no_files_selected'));
            return;
        }

        if (count($this->photos) > 100) {
            $this->addError('photos', __('hiko.max_files_exceeded'));
            return;
        }

        $this->isProcessing = true;
        $filePaths = [];
        $sourceFiles = [];
        $aggregatedOcrText = '';
        $aggregatedMetadata = [];
        $requestPrompt = '';
        $rawResponse = '';
        $provider = 'openai';
        $modelName = 'gpt-4o';
        $this->uploadedFiles = [];

        try {
            foreach ($this->photos as $photo) {
                $filePath = $this->saveUploadedFile($photo);
                $filePaths[] = $filePath;
                $sourceFiles[] = basename($filePath);
                $this->uploadedFiles[] = $filePath;

                $result = $documentService->processDocument($filePath, $this->selectedModel);
                $provider = $result['provider'] ?? $provider;
                $modelName = $result['model'] ?? $modelName;
                $requestPrompt = $result['request_prompt'] ?? $requestPrompt;
                $rawResponse = $result['raw_response'] ?? $rawResponse;

                $aggregatedOcrText .= ($result['recognized_text'] ?? '') . "\n";

                if (isset($result['metadata']) && is_array($result['metadata'])) {
                    foreach ($result['metadata'] as $key => $value) {
                        if (!isset($aggregatedMetadata[$key])) {
                            $aggregatedMetadata[$key] = $value;
                            continue;
                        }

                        if (is_array($aggregatedMetadata[$key]) && is_array($value)) {
                            $aggregatedMetadata[$key] = array_values(array_unique(array_merge($aggregatedMetadata[$key], $value)));
                        } else {
                            $aggregatedMetadata[$key] = $value;
                        }
                    }
                }
            }

            $this->ocrText = trim($aggregatedOcrText);
            $this->metadata = $aggregatedMetadata;
            $mappedFields = $this->mapMetadataToFormFields($this->metadata, $this->ocrText);
            $this->transientMappedFields = $mappedFields;
            $this->selectedFields = array_keys($mappedFields);
            $this->selectAllMappedFields = !empty($mappedFields);
            $this->fieldDiffs = $this->computeDiffs($mappedFields);

            $snapshot = $this->persistSnapshot([
                'provider' => $provider,
                'model' => $modelName,
                'status' => 'success',
                'source_files' => $sourceFiles,
                'recognized_text' => $this->ocrText,
                'metadata' => $this->metadata,
                'mapped_fields' => $mappedFields,
                'request_prompt' => $requestPrompt,
                'raw_response' => $rawResponse,
            ]);

            Log::info('OCR extraction completed.', [
                'tenant_id' => $this->tenantId(),
                'tenant_prefix' => $this->tenantPrefix(),
                'letter_id' => $this->letterId,
                'provider' => $provider,
                'model' => $modelName,
                'snapshot_id' => $snapshot?->id,
            ]);

            if ($snapshot) {
                $this->loadSnapshots();
                $this->selectSnapshot((int) $snapshot->id);
            } else {
                $this->selectedSnapshotId = null;
            }

            session()->flash('message', __('hiko.ocr_processing_completed_successfully'));
            $this->resetValidation();
        } catch (Exception $e) {
            $this->persistSnapshot([
                'provider' => $this->providerLabel($this->selectedModel),
                'model' => $this->modelLabel($this->selectedModel),
                'status' => 'failed',
                'source_files' => $sourceFiles,
                'recognized_text' => '',
                'metadata' => [],
                'mapped_fields' => [],
                'request_prompt' => $requestPrompt,
                'raw_response' => $rawResponse,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('OCR processing failed.', [
                'tenant_id' => $this->tenantId(),
                'tenant_prefix' => $this->tenantPrefix(),
                'letter_id' => $this->letterId,
                'model_key' => $this->selectedModel,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            session()->flash('error', __('hiko.error_processing_document') . ' ' . $e->getMessage());
        } finally {
            $this->isProcessing = false;

            foreach ($filePaths as $path) {
                $this->cleanupUploadedFile($path);
            }

            DocumentService::cleanupTempFiles();
        }
    }

    public function selectSnapshot(int $snapshotId): void
    {
        $snapshot = OcrSnapshot::query()
            ->when($this->tenantId(), fn($q) => $q->where('tenant_id', $this->tenantId()))
            ->where('id', $snapshotId)
            ->when($this->letterId, fn($q) => $q->where('letter_id', $this->letterId))
            ->first();

        if (!$snapshot) {
            return;
        }

        $this->selectedSnapshotId = $snapshotId;
        $mapped = $this->filterCopyableMappedFields($snapshot->mapped_fields ?? []);
        $this->selectedFields = array_keys($mapped);
        $this->selectAllMappedFields = !empty($mapped);
        $this->fieldDiffs = $this->computeDiffs($mapped);
        $this->ocrText = (string) ($snapshot->recognized_text ?? '');
        $this->metadata = $snapshot->metadata ?? [];
    }

    public function updatedSelectedFields(): void
    {
        $this->syncSelectAllMappedFields();
    }

    public function updatedSelectAllMappedFields(bool $value): void
    {
        $fieldKeys = array_keys($this->currentMappedFields());
        $this->selectedFields = $value ? $fieldKeys : [];
    }

    public function applySnapshot(): void
    {
        $snapshot = null;
        $mapped = [];

        if ($this->selectedSnapshotId) {
            $snapshot = OcrSnapshot::find($this->selectedSnapshotId);
            if (!$snapshot) {
                $this->addError('selectedSnapshotId', __('hiko.no_snapshot_selected'));
                return;
            }

            $mapped = $this->filterCopyableMappedFields($snapshot->mapped_fields ?? []);
        } else {
            $mapped = $this->filterCopyableMappedFields($this->transientMappedFields);
            if (empty($mapped)) {
                $this->addError('selectedSnapshotId', __('hiko.no_snapshot_selected'));
                return;
            }
        }

        $allowed = array_flip($this->selectedFields);
        $selected = [];
        foreach ($mapped as $field => $value) {
            if (isset($allowed[$field])) {
                $selected[$field] = $value;
            }
        }

        if (empty($selected)) {
            $this->addError('selectedFields', __('hiko.no_fields_selected'));
            return;
        }

        if ($snapshot) {
            $snapshot->update([
                'applied_at' => now(),
                'applied_by_user_id' => auth()->id(),
                'apply_mode' => $this->applyMode,
                'applied_field_keys' => array_keys($selected),
            ]);
        }

        Log::info('OCR snapshot applied to letter form.', [
            'tenant_id' => $this->tenantId(),
            'tenant_prefix' => $this->tenantPrefix(),
            'letter_id' => $this->letterId,
            'snapshot_id' => $snapshot?->id,
            'apply_mode' => $this->applyMode,
            'selected_field_keys' => array_keys($selected),
            'user_id' => auth()->id(),
        ]);

        $this->dispatch('ocr-apply-snapshot', [
            'snapshot_id' => $snapshot?->id,
            'mode' => $this->applyMode,
            'fields' => $selected,
            'full_text' => $snapshot?->recognized_text ?? $this->ocrText,
        ]);
    }

    public function resetForm(): void
    {
        $this->reset([
            'photos',
            'ocrText',
            'metadata',
            'uploadedFiles',
            'selectedSnapshotId',
            'selectedFields',
            'selectAllMappedFields',
            'fieldDiffs',
            'transientMappedFields',
        ]);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.ocr-upload', [
            'ocrText' => $this->ocrText,
            'metadata' => $this->metadata,
            'models' => collect(DocumentService::supportedModels())
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->all(),
            'snapshots' => $this->snapshots,
            'applyModes' => [
                self::APPLY_SELECTED => __('hiko.ocr_apply_selected'),
                self::APPLY_EMPTY => __('hiko.ocr_apply_empty_only'),
                self::APPLY_OVERWRITE => __('hiko.ocr_apply_overwrite'),
            ],
        ]);
    }

    private function saveUploadedFile($file): string
    {
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        if (!Storage::disk('public')->putFileAs('media', $file, $fileName)) {
            throw new Exception(__('hiko.failed_to_save_the_uploaded_file'));
        }

        return Storage::disk('public')->path("media/{$fileName}");
    }

    private function cleanupUploadedFile(?string $filePath): void
    {
        if (!$filePath) {
            return;
        }

        $relativePath = str_replace(Storage::disk('public')->path(''), '', $filePath);
        $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);

        if ($relativePath && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }

    private function mapMetadataToFormFields(array $metadata, string $recognizedText): array
    {
        $get = function (array $meta, string $key, $default = null) {
            return Arr::get($meta, $key, $default);
        };

        $resolveBool = function (array $meta, string ...$keys): ?bool {
            foreach ($keys as $key) {
                if (!array_key_exists($key, $meta)) {
                    continue;
                }

                $value = $meta[$key];
                if (is_bool($value)) {
                    return $value;
                }

                if (is_numeric($value)) {
                    return (bool) $value;
                }

                if (is_string($value)) {
                    $lower = Str::lower(trim($value));
                    if (in_array($lower, ['true', 'yes', 'ano', '1'], true)) {
                        return true;
                    }
                    if (in_array($lower, ['false', 'no', 'ne', '0'], true)) {
                        return false;
                    }
                }
            }

            return null;
        };

        $resolveLanguages = function (array $meta): array {
            $value = $meta['Jazyk'] ?? $meta["Jazyk (array of codes like 'cs', 'de')"] ?? null;

            if (is_null($value)) {
                return [];
            }

            $codes = [];

            if (is_array($value)) {
                $codes = $value;
            } elseif (is_string($value)) {
                $trimmed = trim($value);

                if ($trimmed === '') {
                    return [];
                }

                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $codes = $decoded;
                } else {
                    $codes = preg_split('/[\s,;|]+/', $trimmed) ?: [];
                }
            } else {
                return [];
            }

            $languageMap = Language::all()
                ->mapWithKeys(function ($language) {
                    return [Str::lower((string) $language->code) => (string) $language->name];
                });

            return collect($codes)
                ->map(fn($code) => Str::lower(trim((string) $code)))
                ->filter()
                ->map(fn($code) => $languageMap->get($code))
                ->filter()
                ->unique()
                ->values()
                ->all();
        };

        $mapped = [
            'date_year' => $get($metadata, 'Rok'),
            'date_month' => $get($metadata, 'Měsíc'),
            'date_day' => $get($metadata, 'Den'),
            'date_marked' => $get($metadata, 'Datum označené v dopise'),
            'date_note' => $get($metadata, 'Poznámka k datu'),
            'author_note' => $get($metadata, 'Poznámka k autorům'),
            'recipient_note' => $get($metadata, 'Poznámka k příjemcům'),
            'notes_private' => $get($metadata, 'Poznámka pro zpracovatele'),
            'notes_public' => $get($metadata, 'Veřejná poznámka'),
            'abstract_cs' => $get($metadata, 'Abstrakt CS'),
            'abstract_en' => $get($metadata, 'Abstrakt EN'),
            'incipit' => $get($metadata, 'Incipit'),
            'explicit' => $get($metadata, 'Explicit'),
            'copyright' => $get($metadata, 'Copyright'),
            'languages' => $resolveLanguages($metadata),
            'date_uncertain' => $resolveBool($metadata, 'Datum je nejisté', 'Datum je nejisté (bool)'),
            'date_approximate' => $resolveBool($metadata, 'Datum je přibližné', 'Datum je přibližné (bool)'),
            'date_inferred' => $resolveBool($metadata, 'Datum je odvozené', 'Datum je odvozené (bool)'),
            'date_is_range' => $resolveBool($metadata, 'Datum je uvedené v rozmezí', 'Datum je uvedené v rozmezí (bool)'),
            'author_inferred' => $resolveBool($metadata, 'Autor je odvozený', 'Autor je odvozený (bool)'),
            'author_uncertain' => $resolveBool($metadata, 'Autor je nejistý', 'Autor je nejistý (bool)'),
            'recipient_inferred' => $resolveBool($metadata, 'Příjemce je odvozený', 'Příjemce je odvozený (bool)'),
            'recipient_uncertain' => $resolveBool($metadata, 'Příjemce je nejistý', 'Příjemce je nejistý (bool)'),
            'destination_inferred' => $resolveBool($metadata, 'Místo určení je odvozené', 'Místo určení je odvozené (bool)'),
            'destination_uncertain' => $resolveBool($metadata, 'Místo určení je nejisté', 'Místo určení je nejisté (bool)'),
            'origin_inferred' => $resolveBool($metadata, 'Místo odeslání je odvozené', 'Místo odeslání je odvozené (bool)'),
            'origin_uncertain' => $resolveBool($metadata, 'Místo odeslání je nejisté', 'Místo odeslání je nejisté (bool)'),
        ];

        return array_filter($mapped, function ($value) {
            if (is_null($value)) {
                return false;
            }
            if (is_string($value) && trim($value) === '') {
                return false;
            }
            if (is_array($value) && empty($value)) {
                return false;
            }
            return true;
        });
    }

    private function resolveLetterValues(int $letterId): array
    {
        $letter = Letter::find($letterId);
        if (!$letter) {
            return [];
        }

        return [
            'date_year' => $letter->date_year,
            'date_month' => $letter->date_month,
            'date_day' => $letter->date_day,
            'date_marked' => $letter->date_marked,
            'date_note' => $letter->date_note,
            'author_note' => $letter->author_note,
            'recipient_note' => $letter->recipient_note,
            'notes_private' => $letter->notes_private,
            'notes_public' => $letter->notes_public,
            'abstract_cs' => data_get($letter, 'translations.abstract.cs'),
            'abstract_en' => data_get($letter, 'translations.abstract.en'),
            'incipit' => $letter->incipit,
            'explicit' => $letter->explicit,
            'copyright' => $letter->copyright,
            'languages' => array_values(array_filter(explode(';', (string) $letter->languages))),
            'date_uncertain' => (bool) $letter->date_uncertain,
            'date_approximate' => (bool) $letter->date_approximate,
            'date_inferred' => (bool) $letter->date_inferred,
            'date_is_range' => (bool) $letter->date_is_range,
            'author_inferred' => (bool) $letter->author_inferred,
            'author_uncertain' => (bool) $letter->author_uncertain,
            'recipient_inferred' => (bool) $letter->recipient_inferred,
            'recipient_uncertain' => (bool) $letter->recipient_uncertain,
        ];
    }

    private function computeDiffs(array $mapped): array
    {
        $diffs = [];
        foreach ($mapped as $key => $value) {
            $current = $this->currentFormValues[$key] ?? null;
            if (is_array($value)) {
                $left = $value;
                $right = is_array($current) ? $current : [];
                sort($left);
                sort($right);
                $diffs[$key] = $left !== $right;
                continue;
            }

            $diffs[$key] = (string) $current !== (string) $value;
        }

        return $diffs;
    }

    private function syncSelectAllMappedFields(): void
    {
        $fieldKeys = array_keys($this->currentMappedFields());
        if (empty($fieldKeys)) {
            $this->selectAllMappedFields = false;
            return;
        }

        $selected = array_intersect($fieldKeys, $this->selectedFields);
        $this->selectAllMappedFields = count($selected) === count($fieldKeys);
    }

    private function currentMappedFields(): array
    {
        if ($this->selectedSnapshotId) {
            $selectedSnapshot = collect($this->snapshots)->firstWhere('id', $this->selectedSnapshotId);
            return $this->filterCopyableMappedFields($selectedSnapshot['mapped_fields'] ?? []);
        }

        return $this->filterCopyableMappedFields($this->transientMappedFields);
    }

    private function filterCopyableMappedFields(array $mapped): array
    {
        $blockedKeys = ['recognized_text'];

        return array_filter(
            $mapped,
            fn($value, $key) => !in_array((string) $key, $blockedKeys, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function loadSnapshots(): void
    {
        if (!$this->letterId) {
            $this->snapshots = [];
            return;
        }

        $tenantId = $this->tenantId();

        $list = OcrSnapshot::query()
            ->where('letter_id', $this->letterId)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->latest('id')
            ->limit(10)
            ->get();

        $this->snapshots = $list->map(fn(OcrSnapshot $snapshot) => [
            'id' => $snapshot->id,
            'provider' => $snapshot->provider,
            'model' => $snapshot->model,
            'status' => $snapshot->status,
            'created_at' => optional($snapshot->created_at)?->format('Y-m-d H:i:s'),
            'mapped_fields' => $snapshot->mapped_fields ?? [],
        ])->all();
    }

    private function persistSnapshot(array $payload): ?OcrSnapshot
    {
        if (!$this->letterId) {
            return null;
        }

        $snapshot = OcrSnapshot::create([
            'tenant_id' => $this->tenantId(),
            'tenant_prefix' => $this->tenantPrefix(),
            'letter_id' => $this->letterId,
            'user_id' => auth()->id(),
            'user_email' => optional(auth()->user())->email,
            'provider' => $payload['provider'] ?? $this->providerLabel($this->selectedModel),
            'model' => $payload['model'] ?? $this->modelLabel($this->selectedModel),
            'status' => $payload['status'] ?? 'success',
            'source_files' => $payload['source_files'] ?? [],
            'recognized_text' => $payload['recognized_text'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
            'mapped_fields' => $payload['mapped_fields'] ?? null,
            'request_prompt' => $this->truncate($payload['request_prompt'] ?? null),
            'raw_response' => $this->truncate($payload['raw_response'] ?? null),
            'error_message' => $payload['error_message'] ?? null,
        ]);

        $this->trimSnapshots();

        return $snapshot;
    }

    private function trimSnapshots(): void
    {
        if (!$this->letterId) {
            return;
        }

        $tenantId = $this->tenantId();
        $idsToKeep = OcrSnapshot::query()
            ->where('letter_id', $this->letterId)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->latest('id')
            ->limit(10)
            ->pluck('id')
            ->all();

        OcrSnapshot::query()
            ->where('letter_id', $this->letterId)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->when(!empty($idsToKeep), fn($q) => $q->whereNotIn('id', $idsToKeep))
            ->delete();
    }

    private function providerLabel(string $modelKey): string
    {
        return $modelKey === DocumentService::MODEL_GEMINI_FLASH_2 ? 'gemini' : 'openai';
    }

    private function modelLabel(string $modelKey): string
    {
        return $modelKey === DocumentService::MODEL_GEMINI_FLASH_2 ? 'gemini-2.0-flash' : 'gpt-4o';
    }

    private function truncate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::limit($value, 5000, '');
    }

    private function tenantId(): ?int
    {
        if (function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant) {
            return (int) tenancy()->tenant->id;
        }

        return null;
    }

    private function tenantPrefix(): ?string
    {
        if (function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant) {
            return (string) tenancy()->tenant->table_prefix;
        }

        return null;
    }
}
