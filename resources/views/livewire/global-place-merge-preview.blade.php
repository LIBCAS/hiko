<div>
    {{-- Filters --}}
    <div class="mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <label>
                <span class="block text-sm font-medium mb-1">{{ __('hiko.name_2') }}</span>
                <input
                    id="filters-name"
                    type="text"
                    wire:model.live.debounce.1000ms="filters.name"
                    placeholder="{{ __('hiko.search_by_name') }}"
                    class="w-full px-3 py-2 border rounded text-sm">
            </label>

            <label>
                <span class="block text-sm font-medium mb-1">{{ __('hiko.country') }}</span>
                <input
                    id="filters-country"
                    type="text"
                    wire:model.live.debounce.1000ms="filters.country"
                    placeholder="{{ __('hiko.search_by_country') }}"
                    class="w-full px-3 py-2 border rounded text-sm">
            </label>

            <label>
                <span class="block text-sm font-medium mb-1">{{ __('hiko.method') }}</span>
                <select id="filters-method" wire:model.live="filters.strategy" class="w-full px-3 py-2 border rounded text-sm">
                    <option value="all">{{ __('hiko.all') }}</option>
                    <option value="merge">{{ __('hiko.merge_only') }}</option>
                    <option value="move">{{ __('hiko.move_only') }}</option>
                </select>
            </label>

            <label>
                <span class="block text-sm font-medium mb-1">{{ __('hiko.reason') }}</span>
                <select id="filters-reason" wire:model.live="filters.reason" class="w-full px-3 py-2 border rounded text-sm">
                    <option value="all">{{ __('hiko.all_reasons') }}</option>
                    <option value="geoname_id">{{ __('hiko.merge_reason_geoname_id') }}</option>
                    <option value="alternative_names">{{ __('hiko.merge_reason_alternative_names') }}</option>
                    <option value="country_and_name">{{ __('hiko.merge_reason_country_and_name') }}</option>
                    <option value="name_similarity">{{ __('hiko.merge_reason_name_similarity') }}</option>
                    <option value="coordinates">{{ __('hiko.merge_reason_coordinates') }}</option>
                </select>
            </label>
        </div>
    </div>

    {{-- Hidden inputs to ensure ALL selected places are submitted, not just the ones visible on the current pagination page --}}
    @foreach($selectedPlaces as $selectedId)
        <input type="hidden" name="selected_places[]" value="{{ $selectedId }}">
    @endforeach

    {{-- Loading Indicator Overlay --}}
    <div class="relative min-h-[200px]">
        {{-- This div appears only when Livewire is working --}}
        <div wire:loading.flex class="absolute inset-0 z-10 flex items-center justify-center bg-white bg-opacity-75 transition-opacity duration-200">
            <div class="flex flex-col items-center">
                <svg class="animate-spin h-10 w-10 text-primary mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-semibold text-gray-700">{{ __('hiko.loading') }}</span>
            </div>
        </div>

        {{-- Main Table Container - Dims on loading --}}
        <div wire:loading.class="opacity-50 pointer-events-none transition-opacity duration-200">
            @if($previewData->total() > 0)
                <div class="mb-4 flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" wire:model.live="selectAll" class="mr-2">
                        <span class="text-sm font-medium">{{ __('hiko.select_all') }}</span>
                    </label>
                    <span class="text-sm text-gray-600">
                        {{ __('hiko.selected_count', ['count' => count($selectedPlaces)]) }} / {{ $previewData->total() }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 border">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('hiko.local_place') }}
                                </th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('hiko.method') }} & {{ __('hiko.reason') }}
                                </th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('hiko.global_place') }}
                                </th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('hiko.merged_result') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($previewData as $item)
                                @include('livewire.global-place-merge-preview-row', [
                                    'local' => $item['local'],
                                    'global' => $item['global'],
                                    'strategy' => $item['strategy'],
                                    'reason' => $item['reason']
                                ])
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $previewData->links() }}
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <p>{{ __('hiko.no_places_to_merge') }}</p>
                    <p class="text-sm mt-2">{{ __('hiko.adjust_criteria_or_filters') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function mergeRowData(placeId, local, global, strategy) {
    return {
        placeId: placeId,
        local: local,
        global: global,
        strategy: strategy,

        // Default: prefer global place attributes for merge
        attrs: {
            name: 'global',
            country: 'global',
            division: 'global',
            latitude: 'global',
            longitude: 'global',
            geoname_id: 'global'
        },

        getMergedValue(attr) {
            if (this.strategy !== 'merge' || !this.global) {
                return this.local[attr] || '—';
            }

            const source = this.attrs[attr];
            if (source === 'local') {
                return this.local[attr] || '—';
            } else {
                return this.global[attr] || '—';
            }
        }
    };
}
</script>
@endpush
