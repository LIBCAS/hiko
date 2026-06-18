<x-app-layout :title="$title">
    <x-success-alert />
    <x-form-errors />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-sm text-gray-600">{{ __('hiko.target_tenant') }}</p>
            <p class="font-semibold">{{ $targetTenant->displayName() }}</p>
        </div>
        <form method="POST" action="{{ route('inter-tenant-transfers.store') }}">
            @csrf
            <input type="hidden" name="target_tenant_id" value="{{ $targetTenant->id }}">
            @foreach ($payload['letters'] as $letter)
                <input type="hidden" name="letter_ids[]" value="{{ $letter->id }}">
            @endforeach
            <button class="bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                {{ __('hiko.submit_transfer_request') }}
            </button>
        </form>
    </div>

    <h2 class="mb-3 text-lg font-semibold">{{ __('hiko.selected_letters') }} ({{ $payload['letters']->count() }})</h2>
    @include('pages.inter-tenant-transfers.partials.letters-table')

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        @foreach (['identities', 'places', 'keywords', 'locations'] as $type)
            <section>
                <h2 class="mb-2 text-lg font-semibold">{{ __('hiko.' . $type) }}</h2>
                <ul class="divide-y divide-gray-200 border border-gray-200 bg-white text-sm">
                    @forelse ($payload['dependencies'][$type] as $entity)
                        @php
                            $name = $entity->name;
                            if ($type === 'keywords') {
                                $translations = json_decode($entity->name, true);
                                $name = $translations[app()->getLocale()] ?? $translations['cs'] ?? $translations['en'] ?? $entity->name;
                            }
                        @endphp
                        <li class="px-3 py-2">#{{ $entity->id }} {{ $name }} <span class="text-gray-500">({{ __('hiko.local') }})</span></li>
                    @empty
                    @endforelse
                    @forelse ($payload['global_dependencies'][$type] as $entity)
                        @php
                            $name = $entity->name;
                            if ($type === 'keywords') {
                                $translations = json_decode($entity->name, true);
                                $name = $translations[app()->getLocale()] ?? $translations['cs'] ?? $translations['en'] ?? $entity->name;
                            }
                        @endphp
                        <li class="px-3 py-2">#{{ $entity->id }} {{ $name }} <span class="text-gray-500">({{ __('hiko.global') }})</span></li>
                    @empty
                        @if ($payload['dependencies'][$type]->isEmpty())
                            <li class="px-3 py-2 text-gray-500">{{ __('hiko.no_results') }}</li>
                        @endif
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</x-app-layout>
