<div x-data="{ open: false, opening: false }" @letter-filters-applied.window="open = false"
    @letter-filters-ready.window="open = true; opening = false"
    @keydown.escape.window="if (open) { open = false; $dispatch('cancel-letter-filters'); }">
    <div class="flex flex-wrap items-center gap-3">
        <button type="button" @click="opening = true; $dispatch('edit-letter-filters')" :disabled="opening" :aria-busy="opening"
            :aria-expanded="open" aria-controls="letter-filters-dialog"
            class="flex items-center px-6 py-3 text-sm font-semibold text-black border border-black rounded-full hover:text-white hover:bg-black transition">
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v2l-6 7v7l-4-2v-5L4 6z" /></svg>
            {{ __('hiko.filters') }}@if(count($activeFilters)) <span class="ml-2">({{ count($activeFilters) }})</span> @endif
            <span x-show="opening" class="ml-2" aria-hidden="true">…</span>
        </button>
        @foreach($activeFilters as $key => $filter)
            <span wire:key="filter-badge-{{ $key }}" class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-700 bg-gray-100 rounded-full">
                <span>{{ $filter['label'] }}: <span class="font-medium">{{ $filter['value'] }}</span></span>
                <button type="button" wire:click="removeFilter('{{ $key }}')" aria-label="{{ __('hiko.clear_filters') }}: {{ $filter['label'] }}"
                    class="p-1 rounded-full hover:bg-gray-300 focus:ring-2 focus:ring-primary">
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 5 10 10M15 5 5 15" /></svg>
                </button>
            </span>
        @endforeach
    </div>
    <div id="letter-filters-dialog" role="dialog" aria-modal="true" aria-labelledby="letter-filters-title"
        x-show="open" x-trap.inert.noscroll="open" style="display: none;"
        class="fixed inset-0 z-50 bg-white" x-transition.opacity>
        <livewire:filters-form />
    </div>
</div>
