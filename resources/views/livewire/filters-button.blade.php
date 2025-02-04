<div class="relative inline-block w-full" x-data="{ open: false, activeFilters: @entangle('activeFilters') }" @keydown.escape.window="open = false; document.body.classList.remove('overflow-hidden');">
    <!-- Filters Button & Applied Filters (Inline Display) -->
    <div class="flex justify-between gap-2 flex-wrap mb-6">
        <!-- Toggle Button -->
        <button 
            @click="open = !open; if (open) { document.body.classList.add('overflow-hidden'); } else { document.body.classList.remove('overflow-hidden'); }" 
            type="button" 
            class="inline-flex items-center px-4 py-2 bg-primary bg-opacity-15 border border-transparent rounded-full font-semibold text-xs text-primary hover:text-white uppercase tracking-widest hover:bg-opacity-100 active:bg-primary transition ease-in-out duration-150"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V14l4 4a1 1 0 01-1.707 1.707l-4-4H5.707a1 1 0 00-.707.293L3 12.293V4z"></path>
            </svg>
            Filters
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
                    class="inline-flex items-center rounded-full bg-gray-200 text-gray-700 text-sm py-0.5 pl-2.5 pr-1 leading-relaxed"
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
                <h2 class="text-lg font-semibold">Filters</h2>
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
