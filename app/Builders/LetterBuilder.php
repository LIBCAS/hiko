<?php

namespace App\Builders;

use App\Models\Letter;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class LetterBuilder extends Builder
{
    public function before($date): LetterBuilder
    {
        $this->whereDate('date_computed', '<=', $date);

        return $this;
    }

    public function after($date): LetterBuilder
    {
        $this->whereDate('date_computed', '>=', $date);

        return $this;
    }

    public function fulltext($query): LetterBuilder
    {
        $query = trim($query);

        if (!empty($query)) {
            $this->where('content_stripped', 'like', '%' . $query . '%');
        }

        return $this;
    }

    public function filter($filters): LetterBuilder
    {
        if (isset($filters['fulltext'])) {
            $this->fulltext($filters['fulltext']);
        }

        if (isset($filters['abstract'])) {
            $this->where(function ($query) use ($filters) {
                $query->whereRaw("LOWER(JSON_EXTRACT(abstract, '$.cs')) like ?", ['%' . Str::lower($filters['abstract']) . '%']);
                $query->orWhereRaw("LOWER(JSON_EXTRACT(abstract, '$.en')) like ?", ['%' . Str::lower($filters['abstract']) . '%']);
            });
        }

        if (isset($filters['id']) && !empty($filters['id'])) {
            $this->where('id', 'LIKE', '%' . $filters['id'] . '%');
        }

        if (isset($filters['status']) && !empty($filters['status'])) {
            $this->where('status', '=', $filters['status']);
        }

        if (isset($filters['after']) && !empty($filters['after'])) {
            $this->after($filters['after']);
        }

        if (isset($filters['before']) && !empty($filters['before'])) {
            $this->before($filters['before']);
        }

        if (isset($filters['signature']) && !empty($filters['signature'])) {
            $this->whereRaw("LOWER(JSON_EXTRACT(copies, '$[*].signature')) like ?", ['%' . Str::lower($filters['signature']) . '%']);
        }

        if (isset($filters['author']) && !empty($filters['author'])) {
            $this->addIdentityNameFilter('author', $filters['author']);
        }

        if (isset($filters['recipient']) && !empty($filters['recipient'])) {
            $this->addIdentityNameFilter('recipient', $filters['recipient']);
        }

        if (isset($filters['origin']) && !empty($filters['origin'])) {
            $this->addPlaceFilter('origin', $filters['origin']);
        }

        if (isset($filters['destination']) && !empty($filters['destination'])) {
            $this->addPlaceFilter('destination', $filters['destination']);
        }

        if (isset($filters['repository'])) {
            $this->where(function ($query) use ($filters) {
                $query->whereRaw("JSON_EXTRACT(copies, '$[*].repository') like ?", ['%' . $filters['repository'] . '%']);
            });
        }

        if (isset($filters['archive'])) {
            $this->where(function ($query) use ($filters) {
                $query->whereRaw("JSON_EXTRACT(copies, '$[*].archive') like ?", ['%' . $filters['archive'] . '%']);
            });
        }

        if (isset($filters['collection'])) {
            $this->where(function ($query) use ($filters) {
                $query->whereRaw("JSON_EXTRACT(copies, '$[*].collection') like ?", ['%' . $filters['collection'] . '%']);
            });
        }

        if (isset($filters['keyword']) && !empty($filters['keyword'])) {
            $this->whereHas('keywords', function ($subquery) use ($filters) {
                $subquery
                    ->whereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($filters['keyword']) . '%']);
                $subquery
                    ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($filters['keyword']) . '%']);
            });
        }

        if (isset($filters['languages']) && !empty($filters['languages'])) {
            $this->where(function ($query) use ($filters) {
                foreach (explode(';', $filters['languages']) as $lang) {
                    $query->orWhereRaw("LOWER(languages) like ?", ['%' . Str::lower(trim($lang)) . '%']);
                }
            });
        }

        if (isset($filters['mentioned']) && !empty($filters['mentioned'])) {
            $this->addIdentityNameFilter('mentioned', $filters['mentioned']);
        }

        if (isset($filters['media']) && $filters['media'] !== '') {
            if ($filters['media']) {
                $this->whereHas('media');
            } else {
                $this->whereDoesntHave('media');
            }
        }

        if (isset($filters['editor']) && !empty($filters['editor'])) {
            if (request()->user()->can('manage-users')) {
                $this->whereHas('users', function ($subquery) use ($filters) {
                    $subquery->where('users.name', 'LIKE', "%" . $filters['editor'] . "%");
                });
            } elseif (request()->user()->can('manage-metadata')) {
                $this->whereHas('users', function ($subquery) {
                    $subquery->where('users.id', request()->user()->id);
                });
            }
        }

        if (isset($filters['note']) && !empty($filters['note'])) {
            $this->addNoteFilter($filters['note']);
        }

        return $this;
    }

    protected function addIdentityNameFilter(string $type, $search): LetterBuilder
    {
        $this->whereHas('identities', function ($subquery) use ($type, $search) {
            $subquery
                ->where('role', '=', $type)
                ->where(function ($namesubquery) use ($type, $search) {
                    $namesubquery->where('name', 'LIKE', "%{$search}%")
                        ->orWhereRaw('LOWER(alternative_names) like ?', ['%' . Str::lower($search) . '%']);
                });
        });

        return $this;
    }

    protected function addPlaceFilter(string $type, $search): LetterBuilder
    {
        $this->whereHas('places', function ($subquery) use ($type, $search) {
            $subquery
                ->where('role', '=', $type)
                ->where('name', 'LIKE', "%{$search}%");
        });

        return $this;
    }

    protected function addNoteFilter($search): LetterBuilder
    {
        $this->where(function ($query) use ($search) {
            $query->where('date_note', 'LIKE', '%' . $search . '%')
                  ->orWhere('author_note', 'LIKE', '%' . $search . '%')
                  ->orWhere('recipient_note', 'LIKE', '%' . $search . '%')
                  ->orWhere('destination_note', 'LIKE', '%' . $search . '%')
                  ->orWhere('origin_note', 'LIKE', '%' . $search . '%')
                  ->orWhere('people_mentioned_note', 'LIKE', '%' . $search . '%')
                  ->orWhere('notes_private', 'LIKE', '%' . $search . '%')
                  ->orWhere('notes_public', 'LIKE', '%' . $search . '%');
        });

        return $this;
    }
}
