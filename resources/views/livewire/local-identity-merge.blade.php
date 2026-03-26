<div>
    <x-success-alert />

    <div x-data="{ showGuide: false }">
        <div class="mb-6 flex justify-between">
            <a href="{{ route('identities') }}" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
                <span>←</span> {{ __('hiko.back_to_identities') }}
            </a>
            <button @click="showGuide = !showGuide" type="button" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ __('hiko.guide') }}
            </button>
        </div>

        <div x-show="showGuide" x-transition class="mb-6 bg-blue-50 border border-blue-200 rounded-md p-4 relative" style="display: none;">
            <button @click="showGuide = false" class="absolute top-2 right-9 text-blue-400 hover:text-blue-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <h3 class="text-sm font-bold text-blue-800">{{ __('hiko.how_to_merge') }}</h3>
            <ul class="list-disc list-inside text-sm text-blue-700 mt-2">
                <li>{{ __('hiko.local_identity_merge_step_1') }}</li>
                <li>{{ __('hiko.local_identity_merge_step_2') }}</li>
                <li>{{ __('hiko.local_identity_merge_step_3') }}</li>
                <li>{{ __('hiko.local_identity_merge_step_4') }}</li>
            </ul>
        </div>

        {{-- CONFIGURATION --}}
        @if(!$scanComplete)
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">{{ __('hiko.merging_criteria') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" wire:model="criteria" value="viaf_id" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                    <div class="font-medium">{{ __('hiko.viafid') }}</div>
                </label>
                <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" wire:model="criteria" value="dates" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                    <div class="font-medium">{{ __('hiko.dates') }}</div>
                </label>
                <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" wire:model="criteria" value="name_similarity" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                    <div class="w-full">
                        <div class="font-medium">{{ __('hiko.merge_by_name_similarity') }}</div>
                        <div x-show="$wire.criteria.includes('name_similarity')" class="mt-2">
                            <label class="flex items-center text-sm">
                                <span class="mr-2">{{ __('hiko.threshold') }}:</span>
                                <input type="number" wire:model="nameSimilarityThreshold" min="0" max="100" class="w-20 px-2 py-1 border rounded text-sm">
                                <span class="ml-1">%</span>
                            </label>
                        </div>
                    </div>
                </label>
            </div>
            <div class="mt-6 flex justify-center">
                <button wire:click="scan" wire:loading.attr="disabled" class="px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    <span wire:loading.remove>{{ __('hiko.search_for_duplicates') }}</span>
                    <span wire:loading>{{ __('hiko.loading') }}</span>
                </button>
            </div>
        </div>
        @endif

        {{-- RESULTS --}}
        @if($scanComplete)

        {{-- FILTERS --}}
        <div class="mb-6 p-4 bg-gray-100 rounded-md border border-gray-200 flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">{{ __('hiko.type') }}</label>
                <select wire:model.live="filters.type" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 w-40">
                    <option value="">{{ __('hiko.all') }}</option>
                    <option value="person">{{ __('hiko.person') }}</option>
                    <option value="institution">{{ __('hiko.institution') }}</option>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">{{ __('hiko.name') }}/{{ __('hiko.name_2') }}</label>
                <input type="text" wire:model.live.debounce.300ms="filters.name" class="text-sm w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="{{ __('hiko.search') }}...">
            </div>
            <div>
                <button wire:click="resetScan" class="px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    {{ __('hiko.change_criteria') }}
                </button>
            </div>
        </div>

        @if($filteredGroups->isEmpty())
        <div class="bg-white p-8 text-center rounded border border-dashed border-gray-300">
            {{ __('hiko.no_duplicates_found') }}
        </div>
        @else
        <div class="flex flex-col gap-6">
            @foreach($filteredGroups as $index => $group)
            @php
                // Determine if this group is for Institutions or Persons
                $firstItem = $group['items'][0] ?? [];
                $isInstitution = ($firstItem['type'] ?? '') === 'institution';
            @endphp

            <div wire:key="group-{{ $index }}-{{ count($group['items']) }}"
                x-data="mergeGroupData(@js($group['items']), @js($index), @js($isInstitution))"
                class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">

                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="font-bold text-gray-700">
                        {{ __('hiko.group') }} #{{ $index + 1 }}
                        <span class="ml-2 text-xs font-normal text-gray-500 bg-gray-200 px-2 py-0.5 rounded-full">
                            {{ $isInstitution ? __('hiko.institution') : __('hiko.person') }}
                        </span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50 uppercase">
                            <tr>
                                <th class="px-2 py-2 w-8"><input type="checkbox" x-model="allChecked" @change="toggleAll"></th>
                                <th class="px-2 py-2">{{ __('hiko.id') }}</th>

                                @if($isInstitution)
                                    {{-- Institution Columns --}}
                                    <th class="px-2 py-2">{{ __('hiko.name_2') }}</th> {{-- Název --}}
                                    <th class="px-2 py-2">{{ __('hiko.type') }}</th>
                                    <th class="px-2 py-2">{{ __('hiko.viafid') }}</th>
                                @else
                                    {{-- Person Columns --}}
                                    <th class="px-2 py-2">{{ __('hiko.surname_abbr') }}</th>
                                    <th class="px-2 py-2">{{ __('hiko.forename_abbr') }}</th>
                                    <th class="px-2 py-2">{{ __('hiko.type') }}</th>
                                    <th class="px-2 py-2">{{ __('hiko.nationality_abbr') }}</th>
                                    <th class="px-2 py-2">{{ __('hiko.gender_abbr') }}</th>
                                    <th class="px-2 py-2">{{ __('hiko.birth_year_abbr') }}</th>
                                    <th class="px-2 py-2">{{ __('hiko.death_year_abbr') }}</th>
                                    <th class="px-2 py-2">{{ __('hiko.viafid') }}</th>
                                    <th class="px-2 py-2">{{ __('hiko.professions') }}</th>
                                    <th class="px-2 py-2">{{ __('hiko.religions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="item in items" :key="item.id">
                                <tr :class="{'bg-blue-50': selectedIds.includes(item.id)}" class="hover:bg-gray-50">
                                    <td class="px-2 py-2 text-center align-top">
                                        <input type="checkbox" :value="item.id" x-model="selectedIds">
                                    </td>

                                    <td class="px-2 py-2 text-center align-top">
                                        <a :href="`/identities/${item.id}/edit`" x-text="item.id" target="_blank" class="text-primary hover:underline"></a>
                                    </td>

                                    @if($isInstitution)
                                        {{-- Institution Rows --}}
                                        <td class="px-2 py-2 cursor-pointer align-top"
                                            @click="final.name = item.name"
                                            :class="final.name === item.name ? 'bg-yellow-100 font-bold' : ''">
                                            <span x-text="item.name || '—'"></span>
                                        </td>
                                        <td class="px-2 py-2 cursor-pointer align-top"
                                            @click="final.type = item.type"
                                            :class="final.type === item.type ? 'bg-yellow-100 font-bold' : ''">
                                            <span x-text="item.type || '—'"></span>
                                        </td>
                                        <td class="px-2 py-2 cursor-pointer align-top"
                                            @click="final.viaf_id = item.viaf_id"
                                            :class="final.viaf_id === item.viaf_id ? 'bg-yellow-100 font-bold' : ''">
                                            <span x-text="item.viaf_id || '—'"></span>
                                        </td>
                                    @else
                                        {{-- Person Rows --}}
                                        <template x-for="field in ['surname', 'forename', 'type', 'nationality', 'gender', 'birth_year', 'death_year', 'viaf_id']">
                                            <td class="px-2 py-2 cursor-pointer align-top"
                                                @click="final[field] = item[field]"
                                                :class="final[field] === item[field] ? 'bg-yellow-100 font-bold' : ''">
                                                <span x-text="item[field] || '—'"></span>
                                            </td>
                                        </template>

                                        {{-- Professions (Select Set) --}}
                                        <td class="px-2 py-2 cursor-pointer align-top"
                                            @click="final.selected_profession_source_id = item.id"
                                            :class="final.selected_profession_source_id === item.id ? 'bg-yellow-100' : ''">
                                            <ul class="list-disc list-inside">
                                                <template x-for="prof in item.professions_list">
                                                    <li x-text="prof"></li>
                                                </template>
                                                <span x-show="item.professions_list.length === 0">—</span>
                                            </ul>
                                        </td>

                                        {{-- Religions (Select Set) --}}
                                        <td class="px-2 py-2 cursor-pointer align-top"
                                            @click="final.selected_religion_source_id = item.id"
                                            :class="final.selected_religion_source_id === item.id ? 'bg-yellow-100' : ''">
                                            <ul class="list-disc list-inside">
                                                <template x-for="rel in item.religions_list">
                                                    <li x-text="rel"></li>
                                                </template>
                                                <span x-show="item.religions_list.length === 0">—</span>
                                            </ul>
                                        </td>
                                    @endif
                                </tr>
                            </template>

                            {{-- RESULT ROW --}}
                            <tr class="bg-orange-50 font-bold border-t-2 border-primary">
                                <td class="px-2 py-2 text-center">{{ __('hiko.result_abbr') }}</td>
                                <td></td> {{-- ID placeholder --}}

                                @if($isInstitution)
                                    <td class="px-2 py-2 bg-yellow-50" x-text="final.name"></td>
                                    <td class="px-2 py-2 bg-yellow-50" x-text="final.type"></td>
                                    <td class="px-2 py-2 bg-yellow-50" x-text="final.viaf_id"></td>
                                @else
                                    <td class="px-2 py-2 bg-yellow-50" x-text="final.surname"></td>
                                    <td class="px-2 py-2 bg-yellow-50" x-text="final.forename"></td>
                                    <td class="px-2 py-2 bg-yellow-50" x-text="final.type"></td>
                                    <td class="px-2 py-2 bg-yellow-50" x-text="final.nationality"></td>
                                    <td class="px-2 py-2 bg-yellow-50" x-text="final.gender"></td>
                                    <td class="px-2 py-2 bg-yellow-50" x-text="final.birth_year"></td>
                                    <td class="px-2 py-2 bg-yellow-50" x-text="final.death_year"></td>
                                    <td class="px-2 py-2 bg-yellow-50" x-text="final.viaf_id"></td>
                                    <td class="px-2 py-2 bg-yellow-50 text-gray-500 italic text-[10px]">
                                        (ID: <span x-text="final.selected_profession_source_id"></span>)
                                    </td>
                                    <td class="px-2 py-2 bg-yellow-50 text-gray-500 italic text-[10px]">
                                        (ID: <span x-text="final.selected_religion_source_id"></span>)
                                    </td>
                                @endif
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-gray-50 px-4 py-3 flex justify-between items-center border-t border-gray-200">
                    <span class="text-xs text-gray-500" x-show="selectedIds.length < 2">{{ __('hiko.select_at_least_two') }}</span>
                    <button @click="submitMerge" :disabled="selectedIds.length < 2" class="bg-primary text-white px-4 py-2 rounded text-sm hover:bg-black transition disabled:opacity-50">
                        {{ __('hiko.merge_selected') }}
                    </button>
                </div>

                <x-local-merge-confirm-modal
                    show="confirmOpen"
                    items="confirmationItems"
                    selected-count="selectedIds.length"
                    merge-count="confirmationMergeCount"
                    move-count="confirmationMoveCount"
                    more-count="confirmationMoreCount"
                    preview-items="confirmationPreviewItems"
                    preview-note="{{ __('hiko.local_identity_merge_preview_note') }}"
                    confirm-action="executeMerge()"
                />
            </div>
            @endforeach
        </div>
        @endif
        @endif
    </div>
</div>

@script
<script>
    Alpine.data('mergeGroupData', (items, groupIndex, isInstitution) => ({
        items: items,
        selectedIds: items.map(i => i.id),
        allChecked: true,
        final: {},
        isInstitution: isInstitution,
        confirmOpen: false,
        mergeMethodLabel: "{{ __('hiko.merge') }}",

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
            this.$watch('selectedIds', () => {
                this.allChecked = this.selectedIds.length === this.items.length;
            });
        },

        get selectedItems() {
            const selectedIdsString = this.selectedIds.map(String);
            return this.items.filter(item => selectedIdsString.includes(String(item.id)));
        },

        get confirmationItems() {
            const resultId = this.targetId;
            const resultUrl = resultId ? `/identities/${resultId}/edit` : null;

            return this.selectedItems.slice(0, {{ (int) config('merge_confirmation.summary_limit', 20) }}).map(item => ({
                local: item.id,
                local_url: `/identities/${item.id}/edit`,
                method: this.mergeMethodLabel,
                result: resultId ?? '—',
                result_url: resultUrl,
            }));
        },

        get confirmationMoreCount() {
            return Math.max(this.selectedItems.length - {{ (int) config('merge_confirmation.summary_limit', 20) }}, 0);
        },

        get confirmationMergeCount() {
            return this.selectedItems.length;
        },

        get confirmationMoveCount() {
            return 0;
        },

        get confirmationPreviewItems() {
            if (this.isInstitution) {
                return [
                    { label: "{{ __('hiko.id') }}", value: this.targetId ?? '—' },
                    { label: "{{ __('hiko.name_2') }}", value: this.previewValue(this.final.name) },
                    { label: "{{ __('hiko.type') }}", value: this.previewValue(this.final.type) },
                    { label: "{{ __('hiko.viafid') }}", value: this.previewValue(this.final.viaf_id) },
                ];
            }

            return [
                { label: "{{ __('hiko.id') }}", value: this.targetId ?? '—' },
                { label: "{{ __('hiko.surname_abbr') }}", value: this.previewValue(this.final.surname) },
                { label: "{{ __('hiko.forename_abbr') }}", value: this.previewValue(this.final.forename) },
                { label: "{{ __('hiko.type') }}", value: this.previewValue(this.final.type) },
                { label: "{{ __('hiko.nationality') }}", value: this.previewValue(this.final.nationality) },
                { label: "{{ __('hiko.gender') }}", value: this.previewValue(this.final.gender) },
                { label: "{{ __('hiko.birth_year') }}", value: this.previewValue(this.final.birth_year) },
                { label: "{{ __('hiko.death_year') }}", value: this.previewValue(this.final.death_year) },
                { label: "{{ __('hiko.viafid') }}", value: this.previewValue(this.final.viaf_id) },
                { label: "{{ __('hiko.professions') }}", value: `{{ __('hiko.selected_from_record') }}`.replace(':id', this.previewValue(this.final.selected_profession_source_id)) },
                { label: "{{ __('hiko.religions') }}", value: `{{ __('hiko.selected_from_record') }}`.replace(':id', this.previewValue(this.final.selected_religion_source_id)) },
            ];
        },

        setFinalToTarget() {
            if (this.items.length > 0) {
                // Default to the oldest item
                const t = this.items[0];

                if (this.isInstitution) {
                    this.final = {
                        name: t.name,
                        type: t.type,
                        viaf_id: t.viaf_id,
                    };
                } else {
                    this.final = {
                        surname: t.surname,
                        forename: t.forename,
                        type: t.type,
                        nationality: t.nationality,
                        gender: t.gender,
                        birth_year: t.birth_year,
                        death_year: t.death_year,
                        viaf_id: t.viaf_id,
                        selected_profession_source_id: t.id,
                        selected_religion_source_id: t.id
                    };
                }
            }
        },

        toggleAll() {
            this.selectedIds = this.allChecked ? this.items.map(i => i.id) : [];
        },

        previewValue(value) {
            if (Array.isArray(value)) {
                return value.length ? value.join(', ') : '—';
            }

            if (value === null || value === undefined || value === '') {
                return '—';
            }

            return String(value);
        },

        submitMerge() {
            if (this.selectedIds.length < 2) return;
            this.confirmOpen = true;
        },

        executeMerge() {
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
