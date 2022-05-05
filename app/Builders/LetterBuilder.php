<?php

namespace App\Builders;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class LetterBuilder extends Builder
{
    public function search($filters, $lang)
    {
        if (isset($filters['id']) && !empty($filters['id'])) {
            $this->where('id', 'LIKE', "%" . $filters['id'] . "%");
        }

        if (isset($filters['status']) && !empty($filters['status'])) {
            $this->where('status', '=', $filters['status']);
        }

        if (isset($filters['after']) && !empty($filters['after'])) {
            $this->whereDate('date_computed', '>=', $filters['after']);
        }

        if (isset($filters['before']) && !empty($filters['before'])) {
            $this->whereDate('date_computed', '<=', $filters['before']);
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

        if (isset($filters['keyword']) && !empty($filters['keyword'])) {
            $this->keyword($filters['media']);

            $this->whereHas('keywords', function ($subquery) use ($filters, $lang) {
                $subquery
                    ->whereRaw("LOWER(JSON_EXTRACT(name, '$.{$lang}')) like ?", ['%' . Str::lower($filters['keyword']) . '%']);
            });
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

        return $this;
    }

    protected function addIdentityNameFilter(string $type, $search)
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

    protected function addPlaceFilter(string $type, $search)
    {
        $this->whereHas('places', function ($subquery) use ($type, $search) {
            $subquery
                ->where('role', '=', $type)
                ->where('name', 'LIKE', "%{$search}%");
        });

        return $this;
    }
}
