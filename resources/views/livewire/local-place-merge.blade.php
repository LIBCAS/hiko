<div>
    {{-- CONFIGURATION --}}
    @if(!$scanComplete)
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">{{ __('hiko.merging_criteria') }}</h2>
        <p class="text-sm text-gray-600 mb-4">{{ __('hiko.local_place_merging_criteria_description') }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" wire:model="criteria" value="geoname_id" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                <div>
                    <div class="font-medium">{{ __('hiko.merge_by_geoname_id') }}</div>
                    <div class="text-sm text-gray-600">{{ __('hiko.merge_by_geoname_id_desc') }}</div>
                </div>
            </label>

            <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" wire:model="criteria" value="alternative_names" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                <div>
                    <div class="font-medium">{{ __('hiko.merge_by_alternative_names') }}</div>
                    <div class="text-sm text-gray-600">{{ __('hiko.merge_by_alternative_names_desc') }}</div>
                </div>
            </label>

            <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" wire:model="criteria" value="name_similarity" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                <div class="w-full">
                    <div class="font-medium">{{ __('hiko.merge_by_name_similarity') }}</div>
                    <div class="text-sm text-gray-600">{{ __('hiko.merge_by_name_similarity_desc') }}</div>
                    <div x-show="$wire.criteria.includes('name_similarity')" class="mt-2">
                        <label class="flex items-center text-sm">
                            <span class="mr-2">{{ __('hiko.threshold') }}:</span>
                            <input type="number" wire:model="nameSimilarityThreshold" min="0" max="100" class="w-20 px-2 py-1 border rounded text-sm">
                            <span class="ml-1">%</span>
                        </label>
                    </div>
                </div>
            </label>

            <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" wire:model="criteria" value="coordinates" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                <div class="w-full">
                    <div class="font-medium">{{ __('hiko.merge_by_coordinates') }}</div>
                    <div class="text-sm text-gray-600">{{ __('hiko.merge_by_coordinates_desc') }}</div>
                    <div x-show="$wire.criteria.includes('coordinates')" class="mt-2 space-y-2">
                        <div class="flex items-center text-sm">
                            <span class="text-sm mr-2 w-24">{{ __('hiko.latitude_abbr') }}:</span>
                            <input type="number" step="0.001" wire:model="latitudeTolerance" class="w-24 px-2 py-1 border rounded text-sm">
                            <span class="text-sm ml-1">{{ __('hiko.tolerance') }}</span>
                        </div>
                        <div class="flex items-center text-sm">
                            <span class="text-sm mr-2 w-24">{{ __('hiko.longitude_abbr') }}:</span>
                            <input type="number" step="0.001" wire:model="longitudeTolerance" class="w-24 px-2 py-1 border rounded text-sm">
                            <span class="text-sm ml-1">{{ __('hiko.tolerance') }}</span>
                        </div>
                    </div>
                </div>
            </label>

            <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" wire:model="criteria" value="country_and_name" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                <div class="w-full">
                    <div class="font-medium">{{ __('hiko.merge_by_country_and_name') }}</div>
                    <div class="text-sm text-gray-600">{{ __('hiko.merge_by_country_and_name_desc') }}</div>
                    <div x-show="$wire.criteria.includes('country_and_name')" class="mt-2">
                        <label class="flex items-center text-sm">
                            <span class="mr-2">{{ __('hiko.threshold') }}:</span>
                            <input type="number" wire:model="countryAndNameThreshold" min="0" max="100" class="w-20 px-2 py-1 border rounded text-sm">
                            <span class="ml-1">%</span>
                        </label>
                    </div>
                </div>
            </label>
        </div>

        <div class="mt-6 flex justify-center">
            <button wire:click="scan" wire:loading.attr="disabled" class="px-4 py-2 mt-4 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 disabled:opacity-25 disabled:cursor-not-allowed transition ease-in-out duration-150 flex items-center gap-2">
                <svg wire:loading class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ __('hiko.search_for_duplicates') }}</span>
            </button>
        </div>
    </div>
    @endif

    {{-- POSSIBLE DUPLICATES --}}
    @if($scanComplete)
    <div class="mb-4">
        <button wire:click="resetScan" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
            ← {{ __('hiko.change_criteria') }}
        </button>
    </div>

    @if(empty($groups))
    <div class="bg-white p-8 text-center rounded border border-dashed border-gray-300">
        {{ __('hiko.no_duplicates_found') }}
    </div>
    @else
    <div class="flex flex-col gap-6">
        @foreach($groups as $index => $group)
        <div wire:key="group-{{ $index }}-{{ count($group['items']) }}"
            x-data="mergeGroupData(@js($group['items']), @js($index))"
            class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">

            {{-- Header --}}
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-bold text-gray-700">
                    {{ __('hiko.group') }} #{{ $index + 1 }}
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <th class="px-3 py-2 text-center w-10">
                                <input type="checkbox" x-model="allChecked" @change="toggleAll" class="rounded border-gray-300 text-primary focus:ring-primary">
                            </th>
                            <th class="px-3 py-2 text-left">{{ __('hiko.id') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('hiko.name_2') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('hiko.additional_name_abbr') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('hiko.country') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('hiko.division_abbr') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('hiko.latitude_abbr') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('hiko.longitude_abbr') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('hiko.alternative_names_abbr') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('hiko.note') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('hiko.geoname_id') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="item in items" :key="item.id">
                            <tr :class="{'bg-blue-50': selectedIds.includes(item.id)}" class="hover:bg-gray-50">
                                <td class="px-3 py-3 text-center align-top">
                                    <input type="checkbox" :value="item.id" x-model="selectedIds" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </td>

                                <td class="px-3 py-3 align-top text-sm font-mono">
                                    <a :href="`/places/${item.id}/edit`" x-text="item.id" target="_blank" class="text-primary hover:underline"></a>
                                </td>

                                <td class="px-3 py-3 align-top cursor-pointer text-sm"
                                    @click="final.name = item.name"
                                    :class="final.name === item.name ? 'bg-yellow-100 font-bold' : ''">
                                    <span x-text="item.name"></span>
                                </td>

                                <td class="px-3 py-3 align-top cursor-pointer text-sm"
                                    @click="final.additional_name = item.additional_name"
                                    :class="final.additional_name === item.additional_name ? 'bg-yellow-100 font-bold' : ''">
                                    <span x-text="item.additional_name || '—'"></span>
                                </td>

                                <td class="px-3 py-3 align-top cursor-pointer text-sm"
                                    @click="final.country = item.country"
                                    :class="final.country === item.country ? 'bg-yellow-100 font-bold' : ''">
                                    <span x-text="item.country"></span>
                                </td>

                                <td class="px-3 py-3 align-top cursor-pointer text-sm"
                                    @click="final.division = item.division"
                                    :class="final.division === item.division ? 'bg-yellow-100 font-bold' : ''">
                                    <span x-text="item.division || '—'"></span>
                                </td>

                                <td class="px-3 py-3 align-top cursor-pointer text-xs font-mono"
                                    @click="final.latitude = item.latitude"
                                    :class="final.latitude === item.latitude ? 'bg-yellow-100 font-bold' : ''">
                                    <span x-text="item.latitude || '—'"></span>
                                </td>

                                <td class="px-3 py-3 align-top cursor-pointer text-xs font-mono"
                                    @click="final.longitude = item.longitude"
                                    :class="final.longitude === item.longitude ? 'bg-yellow-100 font-bold' : ''">
                                    <span x-text="item.longitude || '—'"></span>
                                </td>

                                <td class="px-3 py-3 align-top cursor-pointer text-xs"
                                    @click="final.alternative_names = item.alternative_names"
                                    :class="JSON.stringify(final.alternative_names) === JSON.stringify(item.alternative_names) ? 'bg-yellow-100 font-bold' : ''">
                                    <span x-show="item.alternative_names && item.alternative_names.length > 0">...</span>
                                    <span x-show="!item.alternative_names || item.alternative_names.length === 0">—</span>
                                </td>

                                <td class="px-3 py-3 align-top cursor-pointer text-xs"
                                    @click="final.note = item.note"
                                    :class="final.note === item.note ? 'bg-yellow-100 font-bold' : ''">
                                    <span x-show="item.note">...</span>
                                    <span x-show="!item.note">—</span>
                                </td>

                                <td class="px-3 py-3 align-top cursor-pointer text-xs font-mono"
                                    @click="final.geoname_id = item.geoname_id"
                                    :class="final.geoname_id === item.geoname_id ? 'bg-yellow-100 font-bold' : ''">
                                    <span x-text="item.geoname_id || '—'"></span>
                                </td>
                            </tr>
                        </template>

                        {{-- FINAL MERGED ROW --}}
                        <tr class="bg-orange-50 border-t-2 border-primary font-bold">
                            <td class="px-3 py-3 text-center bg-orange-100 text-primary text-xs uppercase">
                                {{ __('hiko.result_abbr') }}
                            </td>
                            <td class="px-3 py-3 align-top text-sm font-mono bg-yellow-50">
                                <span x-show="selectedIds.length >= 2">
                                    <span x-text="targetId"></span>
                                </span>
                            </td>
                            <td class="px-3 py-3 align-top text-sm bg-yellow-50" x-text="final.name"></td>
                            <td class="px-3 py-3 align-top text-sm bg-yellow-50" x-text="final.additional_name || '—'"></td>
                            <td class="px-3 py-3 align-top text-sm bg-yellow-50" x-text="final.country"></td>
                            <td class="px-3 py-3 align-top text-sm bg-yellow-50" x-text="final.division || '—'"></td>
                            <td class="px-3 py-3 align-top text-xs font-mono bg-yellow-50" x-text="final.latitude || '—'"></td>
                            <td class="px-3 py-3 align-top text-xs font-mono bg-yellow-50" x-text="final.longitude || '—'"></td>
                            <td class="px-3 py-3 align-top text-xs bg-yellow-50">
                                <span x-show="final.alternative_names && final.alternative_names.length > 0">...</span>
                                <span x-show="!final.alternative_names || final.alternative_names.length === 0">—</span>
                            </td>
                            <td class="px-3 py-3 align-top text-xs bg-yellow-50">
                                <span x-show="final.note">...</span>
                                <span x-show="!final.note">—</span>
                            </td>
                            <td class="px-3 py-3 align-top text-xs font-mono bg-yellow-50" x-text="final.geoname_id || '—'">
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-gray-50 px-4 py-3 flex justify-between items-center border-t border-gray-200">
                <div class="text-xs text-gray-500">
                    <span x-show="selectedIds.length < 2" class="text-red-600 font-bold">{{ __('hiko.select_at_least_two') }}</span>
                    <span x-show="selectedIds.length >= 2">
                        {{ __('hiko.merged_place_id') }}: <span class="font-mono font-bold" x-text="targetId"></span>
                    </span>
                </div>
                <button
                    @click="submitMerge"
                    :disabled="selectedIds.length < 2"
                    class="bg-primary text-white px-4 py-2 rounded text-sm hover:bg-black transition disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ __('hiko.merge_selected') }}
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif
    @endif
</div>
@script
<script>
    Alpine.data('mergeGroupData', (items, groupIndex) => ({
        items: items,
        selectedIds: items.map(p => p.id),
        allChecked: true,
        final: {},
        mergeConfirmMsg: "{{ __('hiko.confirm_merge') }}",

        // Calculate earliest created_at from selected IDs
        get targetId() {
            if (this.selectedIds.length === 0) return null;

            const selectedIdsString = this.selectedIds.map(String);
            const checkedItems = this.items.filter(p => selectedIdsString.includes(String(p.id)));

            // Sort by created_at (oldest is target)
            checkedItems.sort((a, b) => {
                if (a.created_at === b.created_at) return a.id - b.id;
                return (a.created_at < b.created_at) ? -1 : 1;
            });

            return checkedItems[0].id;
        },

        init() {
            this.setFinalToTarget();

            // When selection changes, we might need to update the default "Final" values
            // if the implicit target changed. (Optional, but good UX to auto-pick target's values)
            this.$watch('selectedIds', () => {
                this.allChecked = this.selectedIds.length === this.items.length;
            });
        },

        setFinalToTarget() {
            if (this.items.length > 0) {
                // Default to the oldest item
                const t = this.items[0];
                this.final = {
                    name: t.name,
                    additional_name: t.additional_name,
                    country: t.country,
                    division: t.division,
                    latitude: t.latitude,
                    longitude: t.longitude,
                    geoname_id: t.geoname_id,
                    note: t.note,
                    alternative_names: t.alternative_names || []
                };
            }
        },

        toggleAll() {
            this.selectedIds = this.allChecked ? this.items.map(p => p.id) : [];
        },

        submitMerge() {
            if (this.selectedIds.length < 2) return;
            if (!confirm(this.mergeConfirmMsg)) return;

            const payload = {
                target_id: this.targetId,
                source_ids: this.selectedIds.filter(id => String(id) !== String(this.targetId)),
                attributes: this.final
            };

            @this.mergeGroup(groupIndex, payload);
        }
    }));
</script>
@endscript
