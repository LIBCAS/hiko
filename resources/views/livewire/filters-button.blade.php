<div class="relative inline-block" x-data="{ open: false, activeFilters: @entangle('activeFilters') }" @keydown.escape.window="open = false; document.body.classList.remove('overflow-hidden');">
    <!-- Filters Button & Applied Filters (Inline Display) -->
    <div class="flex items-center gap-4">
        <!-- Toggle Button -->
        <button 
            @click="open = !open; if (open) { document.body.classList.add('overflow-hidden'); } else { document.body.classList.remove('overflow-hidden'); }" 
            type="button" 
            class="flex items-center text-black px-6 py-3 text-sm font-semibold border border-black rounded-full bg-transparent hover:text-white hover:bg-black active:bg-black active:text-white focus:text-black transition ease-in-out duration-150"
        >
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-filter h-5 mr-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-4.414 4.414v7l-6 2v-8.5l-4.48 -4.928a2 2 0 0 1 -.52 -1.345v-2.227z" /></svg>
            {{ __('hiko.filters') }}
        </button>

        <!-- Applied Filters (Inline Display) -->
        <div class="flex flex-wrap gap-2" wire:ignore.self>
            <template x-for="(filter, key) in activeFilters" :key="key">
                <div 
                    x-data="{ visible: true }" 
                    x-show="visible"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="inline-flex items-center rounded-full text-gray-700 text-sm py-2 px-3 leading-relaxed"
                >
                    <span class="mr-1" x-text="filter.label"></span>
                    <span class="font-medium" x-text="filter.value"></span>

                    <!-- Close Button (Removes Only This Filter) -->
                    <button 
                        @click="visible = false; $nextTick(() => { $wire.removeFilter(key) })" 
                        type="button" 
                        class="ml-1 inline-flex items-center rounded-full hover:bg-gray-300 focus:outline-none focus:bg-gray-300"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Overlay -->
    <div 
        class="fixed inset-0 bg-black opacity-20 z-40 transition-opacity duration-300" 
        x-show="open" 
        x-transition:enter="transition ease-out duration-300" 
        x-transition:enter-start="opacity-0" 
        x-transition:enter-end="opacity-20" 
        x-transition:leave="transition ease-in duration-200" 
        x-transition:leave-start="opacity-20" 
        x-transition:leave-end="opacity-0"
        @click="open = false; document.body.classList.remove('overflow-hidden');"
    ></div>

    <!-- Sidebar -->
    <div 
        class="fixed top-0 left-0 h-screen bg-white p-4 z-50 shadow-2xl transform transition-transform duration-300 overflow-y-auto"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        style="display: none;"
        @click.outside="if (!$event.target.closest('.sidebar-content')) { open = false; document.body.classList.remove('overflow-hidden'); }"
    >
        <!-- Sidebar Content -->
        <div class="sidebar-content">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">{{ __('hiko.filters') }}</h2>
                <button @click="open = false; document.body.classList.remove('overflow-hidden');" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Filters Content -->
            <div class="overflow-y-auto h-[calc(100vh-8rem)] overflow-x-hidden" wire:ignore.self>
                <livewire:filters-form />
            </div>
        </div>
    </div>
</div>
