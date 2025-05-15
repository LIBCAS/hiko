<?php

namespace App\Builders;

use App\Models\Letter;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class LetterBuilder extends Builder
{
    // Date filters: before and after a given date
    public function before($date): LetterBuilder
    {
        return $this->whereDate('date_computed', '<=', $date);
    }

    public function after($date): LetterBuilder
    {
        return $this->whereDate('date_computed', '>=', $date);
    }

    // Full-text search in the content_stripped field
    public function fulltext($query): LetterBuilder
    {
        if (!empty(trim($query))) {
            return $this->where('content_stripped', 'like', '%' . trim($query) . '%');
        }

        return $this;
    }

    // Main filter method, applying all necessary filters
    public function filter(array $filters): LetterBuilder
    {
        if (!empty($filters['fulltext'])) {
            $this->fulltext($filters['fulltext']);
        }

        if (!empty($filters['abstract'])) {
            $this->where(function ($query) use ($filters) {
                $query->whereRaw("LOWER(JSON_EXTRACT(abstract, '$.cs')) LIKE ?", ['%' . Str::lower($filters['abstract']) . '%'])
                      ->orWhereRaw("LOWER(JSON_EXTRACT(abstract, '$.en')) LIKE ?", ['%' . Str::lower($filters['abstract']) . '%']);
            });
        }

        if (!empty($filters['id'])) {
            $this->where('id', 'LIKE', '%' . $filters['id'] . '%');
        }

        if (!empty($filters['status'])) {
            $this->where('status', $filters['status']);
        }

        if (!empty($filters['approval'])) {
            $this->where('approval', $filters['approval']);
        }

        if (!empty($filters['after'])) {
            $this->after($filters['after']);
        }

        if (!empty($filters['before'])) {
            $this->before($filters['before']);
        }

        if (!empty($filters['signature'])) {
            $this->whereRaw("LOWER(JSON_EXTRACT(copies, '$[*].signature')) LIKE ?", ['%' . Str::lower($filters['signature']) . '%']);
        }

        if (!empty($filters['content'])) {
            $this->where('content_stripped', 'LIKE', '%' . $filters['content'] . '%');
        }

        if (!empty($filters['author'])) {
            $this->addIdentityNameFilter('author', $filters['author']);
        }

        if (!empty($filters['recipient'])) {
            $this->addIdentityNameFilter('recipient', $filters['recipient']);
        }

        if (!empty($filters['origin'])) {
            $this->addPlaceFilter('origin', $filters['origin']);
        }

        if (!empty($filters['destination'])) {
            $this->addPlaceFilter('destination', $filters['destination']);
        }

        if (!empty($filters['repository'])) {
            $this->whereRaw("JSON_EXTRACT(copies, '$[*].repository') LIKE ?", ['%' . $filters['repository'] . '%']);
        }

        if (!empty($filters['archive'])) {
            $this->whereRaw("JSON_EXTRACT(copies, '$[*].archive') LIKE ?", ['%' . $filters['archive'] . '%']);
        }

        if (!empty($filters['collection'])) {
            $this->whereRaw("JSON_EXTRACT(copies, '$[*].collection') LIKE ?", ['%' . $filters['collection'] . '%']);
        }

        if (!empty($filters['keyword'])) {
            // Check if keyword is in ID-prefixed format (e.g., local-4 or global-7)
            if (preg_match('/^(local|global)-(\d+)$/', $filters['keyword'], $matches)) {
                [$full, $type, $id] = $matches;

                if ($type === 'local') {
                    $keywordTable = tenancy()->tenant->table_prefix . '__keywords';
                    $this->whereHas('localKeywords', function ($query) use ($id, $keywordTable) {
                        $query->where("{$keywordTable}.id", $id);
                    });
                } elseif ($type === 'global') {
                    $keywordTable = 'global_keywords';
                    $this->whereHas('globalKeywords', function ($query) use ($id, $keywordTable) {
                        $query->where("{$keywordTable}.id", $id);
                    });
                }
            } else {
                // fallback: name search in both local and global keywords
                $this->where(function ($query) use ($filters) {
                    $query->whereHas('localKeywords', function ($q) use ($filters) {
                        $q->whereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) LIKE ?", ['%' . Str::lower($filters['keyword']) . '%'])
                          ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.en')) LIKE ?", ['%' . Str::lower($filters['keyword']) . '%']);
                    })->orWhereHas('globalKeywords', function ($q) use ($filters) {
                        $q->whereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) LIKE ?", ['%' . Str::lower($filters['keyword']) . '%'])
                          ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.en')) LIKE ?", ['%' . Str::lower($filters['keyword']) . '%']);
                    });
                });
            }
        }

        if (!empty($filters['mentioned'])) {
            $this->addIdentityNameFilter('mentioned', $filters['mentioned']);
        }

        if (!empty($filters['editor'])) {
            $this->applyEditorFilter($filters['editor']);
        }

        if (!empty($filters['note'])) {
            $this->addNoteFilter($filters['note']);
        }

        return $this;
    }

    // Add identity name filter by role (e.g., author, recipient, mentioned)
    protected function addIdentityNameFilter(string $type, $search): LetterBuilder
    {
        return $this->whereHas('identities', function ($query) use ($type, $search) {
            $pivotTable = tenancy()->tenant->table_prefix . '__identity_letter';
            $identityTable = tenancy()->tenant->table_prefix . '__identities';

            $query->where("{$pivotTable}.role", $type);

            $query->where(function ($subquery) use ($identityTable, $search) {
                if (is_numeric($search)) {
                    $subquery->where("{$identityTable}.id", $search);
                } else {
                    $subquery->where("{$identityTable}.name", 'like', '%' . $search . '%')
                             ->orWhereRaw("LOWER({$identityTable}.alternative_names) LIKE ?", ['%' . strtolower($search) . '%']);
                }
            });
        });
    }

    // Add place filter by role (e.g., origin, destination)
    protected function addPlaceFilter(string $type, $search): LetterBuilder
    {
        return $this->whereHas('places', function ($query) use ($type, $search) {
            $pivotTable = tenancy()->tenant->table_prefix . '__letter_place';
            $placeTable = tenancy()->tenant->table_prefix . '__places';

            $query->where("{$pivotTable}.role", $type);

            $query->where(function ($subquery) use ($placeTable, $search) {
                if (is_numeric($search)) {
                    $subquery->where("{$placeTable}.id", $search);
                } else {
                    $subquery->where("{$placeTable}.name", 'like', '%' . $search . '%')
                            ->orWhereRaw("LOWER({$placeTable}.alternative_names) LIKE ?", ['%' . strtolower($search) . '%']);
                }
            });
        });
    }

    // Add note filter
    protected function addNoteFilter($search): LetterBuilder
    {
        return $this->where(function ($query) use ($search) {
            $query->where('date_note', 'LIKE', '%' . $search . '%')
                  ->orWhere('author_note', 'LIKE', '%' . $search . '%')
                  ->orWhere('recipient_note', 'LIKE', '%' . $search . '%')
                  ->orWhere('destination_note', 'LIKE', '%' . $search . '%')
                  ->orWhere('origin_note', 'LIKE', '%' . $search . '%')
                  ->orWhere('people_mentioned_note', 'LIKE', '%' . $search . '%')
                  ->orWhere('notes_private', 'LIKE', '%' . $search . '%')
                  ->orWhere('notes_public', 'LIKE', '%' . $search . '%');
        });
    }

    protected function applyEditorFilter($editor): LetterBuilder
    {
        if (request()->user()->can('manage-users')) {
            return $this->whereHas('users', function ($query) use ($editor) {
                $query->where('users.name', 'LIKE', '%' . $editor . '%');
            });
        } elseif (request()->user()->can('manage-metadata')) {
            return $this->whereHas('users', function ($query) {
                $query->where('users.id', request()->user()->id);
            });
        }

        return $this;
    }

    public function orderByDate(string $direction = 'asc'): LetterBuilder
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        return $this->orderBy('date_computed', $direction);
    }
}
