<x-app-layout :title="$title">
    <x-success-alert />
    <x-form-errors />

    @if (session('error'))
        <div class="mb-5 border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    @if (!empty($mappingWarnings))
        <div class="mb-5 border border-yellow-400 bg-yellow-50 px-4 py-3 text-sm text-yellow-900">
            <p class="font-semibold">{{ __('hiko.transfer_saved_mappings_invalid') }}</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($mappingWarnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <dl class="mb-8 grid gap-4 border-y border-gray-200 py-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
        <div><dt class="text-gray-500">{{ __('hiko.source_tenant') }}</dt><dd class="font-semibold">{{ $transfer->sourceTenant->displayName() }}</dd></div>
        <div><dt class="text-gray-500">{{ __('hiko.target_tenant') }}</dt><dd class="font-semibold">{{ $transfer->targetTenant->displayName() }}</dd></div>
        <div><dt class="text-gray-500">{{ __('hiko.requested_by') }}</dt><dd class="font-semibold">{{ $transfer->requested_by_name }}</dd></div>
        <div><dt class="text-gray-500">{{ __('hiko.requested_at') }}</dt><dd class="font-semibold">{{ $transfer->created_at }}</dd></div>
        <div><dt class="text-gray-500">{{ __('hiko.status') }}</dt><dd class="font-semibold">{{ __('hiko.transfer_status_' . $transfer->status) }}</dd></div>
        <div><dt class="text-gray-500">{{ __('hiko.decided_by') }}</dt><dd class="font-semibold">{{ $transfer->decided_by_name ?: '—' }}</dd></div>
        <div><dt class="text-gray-500">{{ __('hiko.decided_at') }}</dt><dd class="font-semibold">{{ $transfer->decided_at ?: '—' }}</dd></div>
    </dl>

    @if ($transfer->decision_reason)
        <div class="mb-6 border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
            <span class="font-semibold">{{ __('hiko.reason') }}:</span> {{ $transfer->decision_reason }}
        </div>
    @endif

    @if ($transfer->error_message)
        <div class="mb-6 border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $transfer->error_message }}
        </div>
    @endif

    @if ($loadError)
        <div class="mb-6 border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $loadError }}
        </div>
        @if ($transfer->isPending() && $isTarget)
            <form method="POST" action="{{ route('inter-tenant-transfers.reject', $transfer) }}">
                @csrf
                <label class="block text-sm">
                    <span class="font-semibold">{{ __('hiko.rejection_reason_optional') }}</span>
                    <textarea name="reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                </label>
                <button class="mt-3 border border-red-700 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-700 hover:text-white">
                    {{ __('hiko.reject_transfer') }}
                </button>
            </form>
        @elseif ($transfer->isPending())
            <form method="POST" action="{{ route('inter-tenant-transfers.cancel', $transfer) }}">
                @csrf
                <button class="border border-red-700 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-700 hover:text-white">
                    {{ __('hiko.cancel_transfer') }}
                </button>
            </form>
        @endif
    @elseif ($payload)
        <h2 class="mb-3 text-lg font-semibold">{{ __('hiko.selected_letters') }} ({{ $payload['letters']->count() }})</h2>
        @include('pages.inter-tenant-transfers.partials.letters-table')

        @if ($isTarget)
            <form method="POST" action="{{ route('inter-tenant-transfers.approve', $transfer) }}" class="mt-8">
                @csrf
                <p class="mb-5 text-sm text-gray-700">{{ __('hiko.transfer_mapping_help') }}</p>

                @foreach (['identities', 'places', 'keywords', 'locations'] as $type)
                    @php
                        $sourceEditRoutes = [
                            'identities' => 'identities.edit',
                            'places' => 'places.edit',
                            'keywords' => 'keywords.edit',
                            'locations' => 'locations.edit',
                        ];
                    @endphp
                    <section class="mb-8">
                        <h2 class="mb-3 text-lg font-semibold">{{ __('hiko.' . $type) }}</h2>
                        <div class="overflow-visible border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left">{{ __('hiko.source_entity') }}</th>
                                        <th class="px-3 py-2 text-left">{{ __('hiko.target_entity') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse ($payload['dependencies'][$type] as $entity)
                                        @php
                                            $displayName = $entity->name;
                                            if ($type === 'keywords') {
                                                $translations = json_decode($entity->name, true);
                                                $displayName = $translations[app()->getLocale()] ?? $translations['cs'] ?? $translations['en'] ?? $entity->name;
                                            }
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-2">
                                                @php
                                                    $sourcePath = route($sourceEditRoutes[$type], $entity->id, false);
                                                    $sourceUrl = $sourceDomain
                                                        ? request()->getScheme() . '://' . $sourceDomain . $sourcePath
                                                        : $sourcePath;
                                                @endphp
                                                <a href="{{ $sourceUrl }}" target="_blank"
                                                    class="font-mono text-xs font-semibold text-primary-dark hover:underline">
                                                    #{{ $entity->id }}
                                                </a>
                                                {{ $displayName }}
                                                @if ($type === 'identities')
                                                    <span class="text-gray-500">({{ __('hiko.' . $entity->type) }})</span>
                                                @elseif ($type === 'locations')
                                                    <span class="text-gray-500">({{ __('hiko.' . $entity->type) }})</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2">
                                                @php
                                                    $hasSavedMappings = is_array($transfer->mappings);
                                                    $savedValue = $savedMappings[$type][$entity->id] ?? '';
                                                    $defaultValue = $hasSavedMappings
                                                        ? $savedValue
                                                        : ($type === 'identities' ? ($identityAutoMappings[$entity->id] ?? null) : null);
                                                @endphp
                                                <x-transfer-mapping-input :type="$type" :sourceId="$entity->id" :transferId="$transfer->id"
                                                    :defaultValue="$defaultValue"
                                                    :copyEnabled="in_array($type, ['places', 'keywords', 'locations'], true)"
                                                    :locationType="$type === 'locations' ? $entity->type : null"
                                                    :sourceType="$type === 'identities' ? $entity->type : null" />
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="px-3 py-2 text-gray-500">{{ __('hiko.no_mapping_required') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endforeach

                @if (collect($payload['global_dependencies'])->sum(fn ($items) => $items->count()) > 0)
                    @php
                        $globalEditRoutes = [
                            'identities' => 'global.identities.edit',
                            'places' => 'global.places.edit',
                            'keywords' => 'global.keywords.edit',
                            'locations' => 'global.locations.edit',
                        ];
                    @endphp
                    <section class="mb-8">
                        <h2 class="mb-3 text-lg font-semibold">{{ __('hiko.global_entities_reused') }}</h2>
                        <ul class="divide-y divide-gray-200 border border-gray-200 bg-white text-sm">
                            @foreach (['identities', 'places', 'keywords', 'locations'] as $type)
                                @foreach ($payload['global_dependencies'][$type] as $entity)
                                    @php
                                        $displayName = $entity->name;
                                        if ($type === 'keywords') {
                                            $translations = json_decode($entity->name, true);
                                            $displayName = $translations[app()->getLocale()] ?? $translations['cs'] ?? $translations['en'] ?? $entity->name;
                                        }
                                    @endphp
                                    <li class="px-3 py-2">
                                        {{ __('hiko.' . $type) }}:
                                        <a href="{{ route($globalEditRoutes[$type], $entity->id) }}" target="_blank"
                                            class="font-mono font-semibold text-primary-dark hover:underline">
                                            #{{ $entity->id }}
                                        </a>
                                        {{ $displayName }}
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    </section>
                @endif

                <div class="flex flex-wrap gap-3">
                    <button type="submit"
                        formaction="{{ route('inter-tenant-transfers.save-mappings', $transfer) }}"
                        class="border border-primary px-4 py-2 text-sm font-semibold text-primary-dark hover:bg-primary hover:text-white">
                        {{ __('hiko.save_for_later') }}
                    </button>
                    <button type="submit" class="bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                        {{ __('hiko.approve_and_copy') }}
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('inter-tenant-transfers.reject', $transfer) }}" class="mt-10 border-t border-gray-200 pt-6">
                @csrf
                <label class="block text-sm">
                    <span class="font-semibold">{{ __('hiko.rejection_reason_optional') }}</span>
                    <textarea name="reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                </label>
                <button class="mt-3 border border-red-700 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-700 hover:text-white">
                    {{ __('hiko.reject_transfer') }}
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('inter-tenant-transfers.cancel', $transfer) }}" class="mt-6">
                @csrf
                <button class="border border-red-700 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-700 hover:text-white">
                    {{ __('hiko.cancel_transfer') }}
                </button>
            </form>
        @endif
    @elseif ($transfer->status === \App\Models\InterTenantTransferRequest::STATUS_COMPLETED)
        <h2 class="mb-3 text-lg font-semibold">{{ __('hiko.copied_letters') }}</h2>
        <div class="overflow-x-auto border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left">{{ __('hiko.source_id') }}</th><th class="px-3 py-2 text-left">{{ __('hiko.target_id') }}</th></tr></thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach (($transfer->result['letter_id_map'] ?? []) as $sourceId => $targetId)
                        @php
                            $sourcePath = route('letters.show', $sourceId, false);
                            $targetPath = route('letters.show', $targetId, false);
                            $sourceUrl = $sourceDomain
                                ? request()->getScheme() . '://' . $sourceDomain . $sourcePath
                                : $sourcePath;
                            $targetUrl = $targetDomain
                                ? request()->getScheme() . '://' . $targetDomain . $targetPath
                                : $targetPath;
                        @endphp
                        <tr>
                            <td class="px-3 py-2">
                                <a href="{{ $sourceUrl }}" class="font-semibold text-primary-dark hover:underline">
                                    #{{ $sourceId }}
                                </a>
                            </td>
                            <td class="px-3 py-2">
                                <a href="{{ $targetUrl }}" class="font-semibold text-primary-dark hover:underline">
                                    #{{ $targetId }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-app-layout>
