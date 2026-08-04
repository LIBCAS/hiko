<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MetadataDigestRecipientResolver
{
    /**
     * @return Collection<string, array{email:string, normalized_email:string, name:?string, tenant_ids:array<int>}>
     */
    public function all(): Collection
    {
        $memberships = collect();

        Tenant::query()->orderBy('id')->each(function (Tenant $tenant) use ($memberships) {
            $admins = DB::connection('mysql')
                ->table($tenant->table_prefix . '__users')
                ->where('role', 'admin')
                ->whereNull('deactivated_at')
                ->get(['name', 'email']);

            foreach ($admins as $admin) {
                $memberships->push([
                    'email' => $admin->email,
                    'name' => $admin->name,
                    'tenant_id' => (int) $tenant->getKey(),
                ]);
            }
        });

        return $this->fromMemberships($memberships);
    }

    /**
     * @param iterable<array{email:?string, name:?string, tenant_id:int}> $memberships
     * @return Collection<string, array{email:string, normalized_email:string, name:?string, tenant_ids:array<int>}>
     */
    public function fromMemberships(iterable $memberships): Collection
    {
        $recipients = collect();

        foreach ($memberships as $membership) {
            $this->addMembership(
                $recipients,
                $membership['email'],
                $membership['name'],
                $membership['tenant_id']
            );
        }

        return $recipients;
    }

    /**
     * @return array{email:string, normalized_email:string, name:?string, tenant_ids:array<int>}|null
     */
    public function forEmail(string $email): ?array
    {
        $normalized = $this->normalize($email);

        return $this->all()->get($normalized);
    }

    private function addMembership(
        Collection $recipients,
        ?string $email,
        ?string $name,
        int $tenantId
    ): void {
        if (!$email || !filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $normalized = $this->normalize($email);
        $recipient = $recipients->get($normalized, [
            'email' => trim($email),
            'normalized_email' => $normalized,
            'name' => $name,
            'tenant_ids' => [],
        ]);

        $recipient['tenant_ids'][] = $tenantId;
        $recipient['tenant_ids'] = array_values(array_unique($recipient['tenant_ids']));
        sort($recipient['tenant_ids']);

        $recipients->put($normalized, $recipient);
    }

    public function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
