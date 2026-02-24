<div>
    @if(!$scanComplete)
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">{{ __('hiko.merging_criteria') }}</h2>
        <p class="text-sm text-gray-600 mb-4">{{ __('hiko.local_keyword_merging_criteria_description') }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" wire:model="criteria" value="name_similarity" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                <div class="w-full">
                    <div class="font-medium">{{ __('hiko.merge_by_name_similarity') }}</div>
                    <div class="text-sm text-gray-600">{{ __('hiko.merge_by_name_similarity_desc') }}</div>
                    <div x-show="$wire.criteria.includes('name_similarity')" class="mt-2" x-transition>
                        <label class="flex items-center text-sm">
                            <span class="mr-2">{{ __('hiko.threshold') }}:</span>
                            <input type="number" wire:model="nameSimilarityThreshold" min="0" max="100" class="w-20 px-2 py-1 border rounded text-sm">
                            <span class="ml-1">%</span>
                        </label>
                    </div>
                </div>
            </label>

            <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" wire:model="criteria" value="same_category" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                <div>
                    <div class="font-medium">{{ __('hiko.same_category') }}</div>
                    <div class="text-sm text-gray-600">{{ __('hiko.same_category_desc') }}</div>
                </div>
            </label>
        </div>

        <div class="mt-6 flex justify-center">
            <button wire:click="scan" wire:loading.attr="disabled" class="px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 disabled:opacity-25 disabled:cursor-not-allowed transition ease-in-out duration-150 flex items-center gap-2">
                <svg wire:loading class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ __('hiko.search_for_duplicates') }}</span>
            </button>
        </div>
    </div>
    @endif

    @if($scanComplete)
    <div class="mb-4 flex items-center justify-between">
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
            x-data="mergeKeywordGroupData(@js($group['items']), @js($index))"
            class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">

            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-bold text-gray-700">
                    {{ __('hiko.group') }} #{{ $index + 1 }}
                    <span class="ml-2 text-xs font-normal text-gray-500">
                       {{ __('hiko.reason') }}: {{ $group['reason'] }}
                    </span>
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
                            <th class="px-3 py-2 text-left">CS</th>
                            <th class="px-3 py-2 text-left">EN</th>
                            <th class="px-3 py-2 text-left">{{ __('hiko.category') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('hiko.letters') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="item in items" :key="item.id">
                            <tr :class="{'bg-blue-50': selectedIds.includes(item.id)}" class="hover:bg-gray-50">
                                <td class="px-3 py-3 text-center align-top">
                                    <input type="checkbox" :value="item.id" x-model="selectedIds" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </td>
                                <td class="px-3 py-3 align-top text-sm font-mono">
                                    <a :href="`/keywords/${item.id}/edit`" x-text="item.id" target="_blank" class="text-primary hover:underline"></a>
                                </td>
                                <td class="px-3 py-3 align-top cursor-pointer text-sm" @click="final.cs = item.cs" :class="final.cs === item.cs ? 'bg-yellow-100 font-bold' : ''">
                                    <span x-text="item.cs"></span>
                                </td>
                                <td class="px-3 py-3 align-top cursor-pointer text-sm" @click="final.en = item.en" :class="final.en === item.en ? 'bg-yellow-100 font-bold' : ''">
                                    <span x-text="item.en"></span>
                                </td>
                                <td class="px-3 py-3 align-top cursor-pointer text-sm" @click="final.keyword_category_id = item.keyword_category_id" :class="final.keyword_category_id === item.keyword_category_id ? 'bg-yellow-100 font-bold' : ''">
                                    <span x-text="item.keyword_category_label"></span>
                                </td>
                                <td class="px-3 py-3 align-top text-sm text-gray-500" x-text="item.letters_count || 0"></td>
                            </tr>
                        </template>

                        <tr class="bg-orange-50 border-t-2 border-primary font-bold">
                            <td class="px-3 py-3 text-center bg-orange-100 text-primary text-xs uppercase">{{ __('hiko.result_abbr') }}</td>
                            <td class="px-3 py-3 align-top text-sm font-mono bg-yellow-50"><span x-show="selectedIds.length >= 2" x-text="targetId"></span></td>
                            <td class="px-3 py-3 align-top text-sm bg-yellow-50" x-text="final.cs"></td>
                            <td class="px-3 py-3 align-top text-sm bg-yellow-50" x-text="final.en"></td>
                            <td class="px-3 py-3 align-top text-sm bg-yellow-50" x-text="finalCategoryLabel"></td>
                            <td class="px-3 py-3 align-top text-sm bg-yellow-50"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-gray-50 px-4 py-3 flex justify-between items-center border-t border-gray-200">
                <div class="text-xs text-gray-500">
                    <span x-show="selectedIds.length < 2" class="text-red-600 font-bold">{{ __('hiko.select_at_least_two') }}</span>
                    <span x-show="selectedIds.length >= 2">{{ __('hiko.merged_keyword_id') }}: <span class="font-mono font-bold" x-text="targetId"></span></span>
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
    Alpine.data('mergeKeywordGroupData', (items, groupIndex) => ({
        items: items,
        selectedIds: items.map(i => i.id),
        allChecked: true,
        final: {},
        mergeConfirmMsg: "{{ __('hiko.confirm_merge') }}",

        get targetId() {
            if (this.selectedIds.length === 0) return null;

            const selectedIdsString = this.selectedIds.map(String);
            const checkedItems = this.items.filter(i => selectedIdsString.includes(String(i.id)));

            checkedItems.sort((a, b) => {
                if (!a.created_at || !b.created_at || a.created_at === b.created_at) {
                    return a.id - b.id;
                }
                return (a.created_at < b.created_at) ? -1 : 1;
            });

            return checkedItems[0].id;
        },

        get finalCategoryLabel() {
            const category = this.items.find(i => i.keyword_category_id === this.final.keyword_category_id);
            return category ? category.keyword_category_label : '—';
        },

        init() {
            this.setFinalToTarget();
            this.$watch('selectedIds', () => {
                this.allChecked = this.selectedIds.length === this.items.length;
                this.setFinalToTarget();
            });
        },

        setFinalToTarget() {
            if (this.selectedIds.length > 0) {
                const selectedIdsString = this.selectedIds.map(String);
                const checkedItems = this.items.filter(i => selectedIdsString.includes(String(i.id)));

                if (checkedItems.length > 0) {
                    checkedItems.sort((a, b) => {
                        if (!a.created_at || !b.created_at || a.created_at === b.created_at) {
                            return a.id - b.id;
                        }
                        return (a.created_at < b.created_at) ? -1 : 1;
                    });

                    const t = checkedItems[0];
                    this.final = {
                        cs: t.cs,
                        en: t.en,
                        keyword_category_id: t.keyword_category_id,
                    };
                }
            }
        },

        toggleAll() {
            this.selectedIds = this.allChecked ? this.items.map(i => i.id) : [];
        },

        submitMerge() {
            if (this.selectedIds.length < 2) return;
            if (!confirm(this.mergeConfirmMsg)) return;

            const payload = {
                target_id: this.targetId,
                source_ids: this.selectedIds.filter(id => String(id) !== String(this.targetId)),
                attributes: this.final,
            };

            @this.mergeGroup(groupIndex, payload);
        }
    }));
</script>
@endscript
