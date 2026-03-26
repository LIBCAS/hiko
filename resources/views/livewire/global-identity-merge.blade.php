<div x-data="{ showConfig: true, confirmOpen: false }">
    <div x-show="showConfig" x-transition class="bg-white shadow rounded-lg p-6 mb-6 relative">
        <button @click="showConfig = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600" title="{{ __('hiko.hide_configuration') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
        </button>

        <h2 class="text-xl font-semibold mb-4">{{ __('hiko.merging_criteria') }}</h2>
        <p class="text-sm text-gray-600 mb-4">{{ __('hiko.global_identity_merging_criteria_description') }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" wire:model.live="criteria" value="name_similarity" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                <div class="w-full">
                    <div class="font-medium">{{ __('hiko.merge_by_name_similarity') }}</div>
                    <div class="text-sm text-gray-600">{{ __('hiko.merge_by_name_similarity_desc') }}</div>
                    <div x-show="$wire.criteria.includes('name_similarity')" class="mt-2" x-transition>
                        <label class="flex items-center text-sm">
                            <span class="mr-2">{{ __('hiko.threshold') }}:</span>
                            <input type="number" wire:model.live.debounce.300ms="nameSimilarityThreshold" min="50" max="100" class="w-20 px-2 py-1 border rounded text-sm">
                            <span class="ml-1">%</span>
                        </label>
                    </div>
                </div>
            </label>

            <label class="flex items-start p-3 border rounded bg-gray-50 cursor-not-allowed">
                <input type="checkbox" checked disabled class="mt-1 mr-2 rounded border-gray-300 text-primary">
                <div>
                    <div class="font-medium">{{ __('hiko.strict_type_match') }}</div>
                    <div class="text-sm text-gray-600">{{ __('hiko.strict_type_match_desc') }}</div>
                </div>
            </label>
        </div>
    </div>

    <div x-show="!showConfig" class="mb-6">
        <button @click="showConfig = true" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            {{ __('hiko.change_criteria') }}
        </button>
    </div>

    <div class="mb-6 p-4 bg-gray-100 rounded-md border border-gray-200 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">{{ __('hiko.name') }}</label>
            <input type="text" wire:model.live.debounce.500ms="filters.name" class="text-sm w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="{{ __('hiko.search') }}...">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">{{ __('hiko.type') }}</label>
            <select wire:model.live="filters.type" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 w-40">
                <option value="all">{{ __('hiko.all') }}</option>
                <option value="person">{{ __('hiko.person') }}</option>
                <option value="institution">{{ __('hiko.institution') }}</option>
            </select>
        </div>
    </div>

    <div class="mb-4 flex justify-between items-center">
        <div class="text-sm text-gray-600">{{ __('hiko.selected_count', ['count' => count($selectedIds)]) }}</div>
        <button type="button" @click="confirmOpen = true" wire:loading.attr="disabled" @disabled(count($selectedIds) === 0)
            class="px-4 py-2 bg-primary text-white rounded hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 transition-colors duration-150">
            <svg wire:loading class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            {{ __('hiko.execute_merge') }}
        </button>
    </div>

    <div class="bg-white shadow overflow-hidden border border-gray-200 sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10"><input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-primary focus:ring-primary"></th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.local_identity') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.method') }} & {{ __('hiko.reason') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.global_identity') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($previewData as $item)
                    @php
                        $localId = (int)$item['local']->id;
                        $selectedGlobalId = $selectedGlobalIds[$localId] ?? null;
                        $selectedGlobalLabel = $item['global'] ? $item['global']->name : '';
                    @endphp
                    <tr wire:key="identity-link-row-{{ $localId }}" class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap align-top">
                            <input type="checkbox" wire:model.live="selectedIds" id="identity-{{ $localId }}" value="{{ $localId }}" class="rounded border-gray-300 text-primary focus:ring-primary">
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="text-sm font-medium text-gray-900 flex gap-3">
                                <a href="{{ route('identities.edit', $localId) }}" target="_blank" class="text-primary hover:underline font-semibold px-2">{{ $localId }}</a>
                                <span>{{ $item['local']->name }}</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ __("hiko.{$item['local']->type}") }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap align-top">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('hiko.link_to') }}</span>
                            <div class="text-xs text-gray-400 mt-1 pl-1">{{ $item['reason'] }}</div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div
                                x-data="globalIdentityRowSelect({
                                    localId: {{ $localId }},
                                    type: '{{ $item['local']->type }}',
                                    searchUrl: '{{ route('ajax.global.identities') }}',
                                    initialValue: '{{ $selectedGlobalId }}',
                                    initialLabel: @js($selectedGlobalLabel),
                                })"
                                x-init="
                                    selectedValue = '{{ $selectedGlobalId }}';
                                    searchQuery = @js($selectedGlobalLabel);
                                "
                                class="relative"
                            >
                                <input type="hidden" x-model="selectedValue">
                                <input type="text" x-model="searchQuery" x-on:focus="openDropdown()" x-on:input="openDropdown()" x-on:keydown.arrow-down.prevent="highlightNext()" x-on:keydown.arrow-up.prevent="highlightPrev()" x-on:keydown.enter.prevent="selectHighlighted()" x-on:keydown.escape="isOpen = false" class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-8 text-sm" placeholder="{{ __('hiko.search') }}..." autocomplete="off">

                                <button x-show="selectedValue || searchQuery" type="button" class="absolute inset-y-0 right-0 flex items-center p-2 text-gray-400 hover:text-gray-700" x-on:click="clearSelection()">×</button>

                                <div x-show="isOpen" @click.away="isOpen = false" class="absolute z-50 mt-1 w-full rounded-md bg-white shadow-lg max-h-60 overflow-y-auto py-1 text-sm ring-1 ring-black ring-opacity-5">
                                    <div x-show="loading" class="p-2 text-center text-gray-500">{{ __('hiko.loading') }}</div>
                                    <div x-show="!loading && options.length === 0" class="p-2 text-center text-gray-500">{{ __('hiko.no_results') }}</div>
                                    <template x-for="(option, idx) in options" :key="option.value">
                                        <div x-on:mousedown.prevent="selectOption(option)" x-on:mouseenter="highlightedIndex = idx" class="cursor-pointer px-3 py-2" :class="{ 'bg-primary text-white': highlightedIndex === idx }">
                                            <span x-text="option.label"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">{{ __('hiko.no_results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $previewData->links() }}</div>

<x-global-merge-confirm-modal
    wire:key="global-identity-confirm-{{ md5(json_encode($confirmationItems)) }}-{{ count($selectedIds) }}-{{ $confirmationMoreCount }}"
    show="confirmOpen"
    :items="$confirmationItems"
    :selected-count="count($selectedIds)"
    :merge-count="$confirmationMergeCount"
    :move-count="$confirmationMoveCount"
    :more-count="$confirmationMoreCount"
/>
</div>

@push('scripts')
<script>
window.globalIdentityRowSelect = function(params) {
    return {
        localId: params.localId,
        type: params.type,
        searchUrl: params.searchUrl,
        searchQuery: params.initialLabel || '',
        selectedValue: params.initialValue || '',
        options: [],
        isOpen: false,
        loading: false,
        highlightedIndex: 0,
        debounceTimeout: null,

        openDropdown() {
            this.isOpen = true;
            this.debouncedFetch(this.searchQuery);
        },

        debouncedFetch(query) {
            clearTimeout(this.debounceTimeout);
            this.debounceTimeout = setTimeout(() => this.fetchOptions(query), 200);
        },

        fetchOptions(query = '') {
            this.loading = true;
            const url = new URL(this.searchUrl, window.location.origin);
            url.searchParams.set('search', query || '');
            url.searchParams.set('type', this.type);

            fetch(url.toString())
                .then((response) => response.json())
                .then((data) => {
                    this.options = Array.isArray(data) ? data : [];
                    this.highlightedIndex = 0;
                    this.loading = false;
                })
                .catch(() => {
                    this.options = [];
                    this.loading = false;
                });
        },

        selectOption(option) {
            this.selectedValue = option.value;
            this.searchQuery = option.label;
            this.isOpen = false;
            this.$wire.setSelectedGlobalIdentity(this.localId, option.value);
        },

        async clearSelection() {
            this.selectedValue = '';
            this.searchQuery = '';
            await this.$wire.setSelectedGlobalIdentity(this.localId, null);
            this.openDropdown();
        },

        highlightNext() {
            if (this.highlightedIndex < this.options.length - 1) this.highlightedIndex++;
        },

        highlightPrev() {
            if (this.highlightedIndex > 0) this.highlightedIndex--;
        },

        selectHighlighted() {
            if (this.options.length > 0 && this.highlightedIndex >= 0) {
                this.selectOption(this.options[this.highlightedIndex]);
            }
        },
    };
};
</script>
@endpush
