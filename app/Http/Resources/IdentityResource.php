<?php

namespace App\Http\Resources;

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
            'global_identity' => $this->whenLoaded('globalIdentity', function () {
                return [
                    'id' => $this->globalIdentity?->id,
                    'name' => $this->globalIdentity?->name,
                    'type' => $this->globalIdentity?->type,
                    'birth_year' => $this->globalIdentity?->birth_year,
                    'death_year' => $this->globalIdentity?->death_year,
                ];
            }),
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
                    'global_identity_id' => $identity->global_identity_id,
                    'updated_at' => $identity->updated_at,
                ])->values();
            }),
            // Conditionally load relationships if they are loaded
            'professions' => ProfessionResource::collection($this->whenLoaded('professions')),
            'global_professions' => ProfessionResource::collection($this->whenLoaded('globalProfessions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
