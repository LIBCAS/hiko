<?php

namespace App\Http\Resources;

use App\Models\GlobalIdentity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'surname' => $this->surname,
            'forename' => $this->forename,
            'type' => $this->type,
            'nationality' => $this->nationality,
            'gender' => $this->gender,
            'birth_year' => $this->birth_year,
            'death_year' => $this->death_year,
            'viaf_id' => $this->viaf_id,
            'note' => $this->note,
            'alternative_names' => $this->alternative_names,
            'related_names' => $this->related_names,
            'global_identity_id' => $this->global_identity_id,
            'global_identity' => $this->when(
                $this->resource->relationLoaded('globalIdentity') || !empty($this->global_identity_id),
                function () {
                    $identity = $this->globalIdentity;
                    $id = $identity?->id ?? $this->global_identity_id;

                    return $id ? [
                        'id' => (int) $id,
                        'scope' => 'global',
                        'reference' => 'global-' . (int) $id,
                        'name' => $identity?->name,
                        'type' => $identity?->type,
                        'birth_year' => $identity?->birth_year,
                        'death_year' => $identity?->death_year,
                    ] : null;
                }
            ),
            'linked_local_identities_count' => $this->when(
                isset($this->linked_local_identities_count),
                (int) ($this->linked_local_identities_count ?? 0)
            ),
            'linked_local_identities' => $this->whenLoaded('localIdentities', function () {
                return $this->localIdentities->map(fn($identity) => [
                    'id' => $identity->id,
                    'name' => $identity->name,
                    'surname' => $identity->surname,
                    'forename' => $identity->forename,
                    'type' => $identity->type,
                    'scope' => 'local',
                    'reference' => 'local-' . (int) $identity->id,
                    'global_identity_id' => $identity->global_identity_id,
                    'updated_at' => $identity->updated_at,
                ])->values();
            }),
            'professions' => $this->when(
                $this->hasLoadedProfessions(),
                fn() => $this->collectProfessionItems()
            ),
            'religions' => $this->whenLoaded('religions', function () {
                return $this->religions->map(fn($religion) => [
                    'id' => $religion->id,
                    'name' => $religion->name,
                    'is_active' => (bool) $religion->is_active,
                ])->values();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function hasLoadedProfessions(): bool
    {
        return $this->resource->relationLoaded('professions')
            || $this->resource->relationLoaded('localProfessions')
            || $this->resource->relationLoaded('globalProfessions');
    }

    protected function collectProfessionItems(): array
    {
        $local = collect();
        $global = collect();

        if ($this->resource instanceof GlobalIdentity) {
            $global = $this->resource->relationLoaded('professions') ? $this->professions : collect();
        } else {
            $local = $this->resource->relationLoaded('localProfessions')
                ? $this->localProfessions
                : ($this->resource->relationLoaded('professions') ? $this->professions : collect());
            $global = $this->resource->relationLoaded('globalProfessions') ? $this->globalProfessions : collect();
        }

        return array_values(array_merge(
            $this->mapProfessionItems($local, 'local'),
            $this->mapProfessionItems($global, 'global')
        ));
    }

    protected function mapProfessionItems($items, string $scope): array
    {
        return collect($items)->map(function ($profession) use ($scope) {
            $name = json_decode($profession->getAttributes()['name'] ?? '{}', true) ?: [];
            $id = (int) $profession->id;

            return [
                'id' => $id,
                'scope' => $scope,
                'reference' => "{$scope}-{$id}",
                'name' => [
                    'cs' => $name['cs'] ?? '',
                    'en' => $name['en'] ?? '',
                ],
                'category_id' => $profession->profession_category_id,
            ];
        })->values()->toArray();
    }
}
