<div>
    <x-filter-form>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4">

            <!-- Bulk Merge -->
            <button
                wire:click="previewMerge"
                wire:loading.attr="disabled"
                wire:target="previewMerge"
                class="flex items-center justify-center text-black px-4 py-3 text-sm font-semibold border border-black rounded-full bg-transparent hover:text-white hover:bg-black active:bg-black active:text-white focus:text-black transition ease-in-out duration-150"
            >
                {{ __('hiko.preview_merge') }}
                <span wire:loading wire:target="previewMerge" class="ml-2">
                    <svg class="w-5 h-5 animate-spin text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </button>

            <!-- Manual Merge Button -->
            <button
                wire:click="openManualMerge"
                wire:loading.attr="disabled"
                wire:target="openManualMerge"
                class="flex items-center justify-center text-black px-4 py-3 text-sm font-semibold border border-black rounded-full bg-transparent hover:text-white hover:bg-black active:bg-black active:text-white focus:text-black transition ease-in-out duration-150"
            >
                {{ __('hiko.manual_merge') }}
                <span wire:loading wire:target="openManualMerge" class="ml-2">
                    <svg class="w-5 h-5 animate-spin text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </button>
        </div>

        <!-- Selected professions info -->
        @if($selectedProfessionOne)
        <div class="mt-2 px-4 py-2 bg-indigo-50 border border-indigo-200 rounded-md">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-indigo-800">{{ __('hiko.selected_for_merge') }}:</h3>
                    <div class="mt-1 text-indigo-700">
                        <span class="px-2 py-1 bg-white rounded text-xs border border-indigo-300">
                            {{ $selectedProfessionOneDetails ?
                                ($selectedProfessionOneDetails['cs'] . ' / ' . $selectedProfessionOneDetails['en']) :
                                __('hiko.loading_details') }}
                        </span>
                    </div>
                </div>
                @if($selectedProfessionTwo)
                <div>
                    <h3 class="text-sm font-medium text-indigo-800">{{ __('hiko.will_merge_with') }}:</h3>
                    <div class="mt-1 text-indigo-700">
                        <span class="px-2 py-1 bg-white rounded text-xs border border-indigo-300">
                            {{ $selectedProfessionTwoDetails ?
                                ($selectedProfessionTwoDetails['cs'] . ' / ' . $selectedProfessionTwoDetails['en']) :
                                __('hiko.loading_details') }}
                        </span>
                    </div>
                </div>
                @else
                <div>
                    <h3 class="text-sm font-medium text-indigo-800">{{ __('hiko.select_second_profession') }}</h3>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Message Notifications -->
        <div class="mt-2 space-y-2">
            <!-- Success Message -->
            @if (session()->has('success'))
                <div class="bg-green-100 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            <!-- Warning Message -->
            @if (session()->has('warning'))
                <div class="bg-yellow-100 text-yellow-700 px-4 py-3 rounded">
                    {{ session('warning') }}
                </div>
            @endif
            <!-- Error Message -->
            @if (session()->has('error'))
                <div class="bg-red-100 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif
        </div>

        <!-- Filter Inputs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
            <label>
                <span class="block text-sm">CS</span>
                <x-input wire:model.live.debounce.1000ms="filters.cs" class="block w-full px-2 text-sm" type="text" />
            </label>

            <label>
                <span class="block text-sm">EN</span>
                <x-input wire:model.live.debounce.1000ms="filters.en" class="block w-full px-2 text-sm" type="text" />
            </label>

            <label>
                <span class="block text-sm">{{__('hiko.source')}}</span>
                <x-select wire:model.live.debounce.1000ms="filters.source" class="block w-full px-2 text-sm">
                    <option value="all">*</option>
                    <option value="local">{{__('hiko.local')}}</option>
                    <option value="global">{{__('hiko.global')}}</option>
                </x-select>
            </label>

            <label>
                <span class="block text-sm">{{ __('hiko.order_by') }}</span>
                <x-select wire:model.live.debounce.1000ms="filters.order" class="block w-full px-2 text-sm">
                    <option value="cs">CS</option>
                    <option value="en">EN</option>
                </x-select>
            </label>
        </div>
    </x-filter-form>

    <!-- Table Section -->
    @if(!empty($tableData['rows']))
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <x-table :tableData="$tableData" class="table-auto w-full mt-3" />
        </div>
        <div class="w-full pl-1 mt-3">
            {{ $pagination->links() }}
        </div>
    @else
        <div class="mt-4">
            <p class="text-gray-700">{{ __('hiko.compare_no_results') }}</p>
        </div>
    @endif

    <!-- Manual Merge Modal -->
    <div x-data="{ showModal: @entangle('showManualMerge') }" x-show="showModal" x-cloak class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                x-on:click="$wire.closeManualMerge()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all my-4 w-full mx-2 max-h-[85vh] md:max-h-[90vh] sm:my-6 md:my-8 sm:align-middle sm:max-w-xl md:max-w-2xl lg:max-w-4xl xl:max-w-6xl"
                x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[70vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="flex justify-between items-center border-b pb-3">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            {{ __('hiko.manual_merge') }}
                        </h3>
                        <button type="button" wire:click="closeManualMerge" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Progress indicator -->
                    <div class="mt-3 mb-4">
                        <div class="relative bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300 ease-in-out"
                                style="width: {{ $selectedLocalProfession && $selectedGlobalProfession ? '100%' : ($selectedLocalProfession ? '50%' : '0%') }}"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span class="{{ $selectedLocalProfession ? 'text-indigo-600 font-medium' : '' }}">{{ __('hiko.select_local') }}</span>
                            <span class="{{ $selectedLocalProfession && $selectedGlobalProfession ? 'text-indigo-600 font-medium' : '' }}">{{ __('hiko.select_global') }}</span>
                        </div>
                    </div>

                    <!-- Merge Options -->
                    <div class="mb-4 bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <h4 class="font-medium text-gray-700 mb-2">{{ __('hiko.merge_options') }}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="flex items-start">
                                <input id="manual-merge-identities" wire:model.live="mergeOptions.mergeIdentities" type="checkbox"
                                    class="h-4 w-4 mt-1 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <label for="manual-merge-identities" class="ml-2 block text-sm text-gray-700">
                                    {{ __('hiko.merge_identities') }}
                                </label>
                            </div>
                            <div class="flex items-start">
                                <input id="manual-merge-categories" wire:model.live="mergeOptions.mergeCategories" type="checkbox"
                                    class="h-4 w-4 mt-1 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <label for="manual-merge-categories" class="ml-2 block text-sm text-gray-700">
                                    {{ __('hiko.merge_categories') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Selection grids -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Local professions column -->
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2 flex items-center">
                                <span class="inline-flex items-center justify-center mr-2 h-5 w-5 text-xs bg-blue-100 text-blue-700 rounded-full">1</span>
                                {{ __('hiko.local_professions') }}
                            </h4>

                            <!-- Search filter for local professions -->
                            <div class="mb-2">
                                <input type="text"
                                wire:model.debounce.300ms="localProfessionSearch"
                                wire:keydown.debounce.300ms="search"
                                placeholder="{{ __('hiko.search_local_professions') }}"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                wire:loading.class="opacity-75"
                                wire:target="search">
                                <div wire:loading wire:target="search" class="text-xs text-gray-500 mt-1">
                                    {{ __('hiko.filtering') }}
                                </div>
                            </div>

                            <div class="border rounded-md h-96 overflow-y-auto">
                                <ul class="divide-y divide-gray-200">
                                    @forelse($unmergedProfessionsToDisplay as $profession)
                                    <li wire:click="selectLocalProfession({{ $profession['id'] }})"
                                        class="px-4 py-3 hover:bg-blue-50 cursor-pointer transition-colors duration-150
                                            {{ $selectedLocalProfession == $profession['id'] ? 'bg-blue-50 border-l-4 border-blue-500' : '' }}">
                                        <div class="font-medium">CS: {{ $profession['cs'] }}</div>
                                        <div>EN: {{ $profession['en'] }}</div>
                                        @if(!empty($profession['category_name']))
                                        <div class="text-xs text-gray-500 mt-1">
                                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2 py-0.5 rounded">
                                                {{ $profession['category_name'] }}
                                            </span>
                                        </div>
                                        @endif
                                    </li>
                                    @empty
                                    <li class="px-4 py-3 text-gray-500 text-center">
                                        {{ __('hiko.no_local_professions') }}
                                    </li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                        <!-- Global professions column -->
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2 flex items-center">
                                <span class="inline-flex items-center justify-center mr-2 h-5 w-5 text-xs bg-red-100 text-red-700 rounded-full">2</span>
                                {{ __('hiko.global_professions') }}
                            </h4>

                            <!-- Search filter for global professions -->
                            <div class="mb-2">
                                <input type="text"
                                    wire:model.debounce.300ms="globalProfessionSearch"
                                    wire:keydown.debounce.300ms="search"
                                    placeholder="{{ __('hiko.search_global_professions') }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500
                                        {{ !$selectedLocalProfession ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    {{ !$selectedLocalProfession ? 'disabled' : '' }}
                                    wire:loading.class="opacity-75"
                                    wire:target="search">
                                <div wire:loading wire:target="search" class="text-xs text-gray-500 mt-1">
                                    {{ __('hiko.filtering') }}
                                </div>
                            </div>

                            <div class="border rounded-md h-96 overflow-y-auto">
                                <ul class="divide-y divide-gray-200">
                                    @if($selectedLocalProfession)
                                        @forelse($globalProfessionsToDisplay as $profession)
                                        <li wire:click="selectGlobalProfession({{ $profession['id'] }})"
                                        class="px-4 py-3 hover:bg-red-50 cursor-pointer transition-colors duration-150
                                        {{ $selectedGlobalProfession == $profession['id'] ? 'bg-red-50 border-l-4 border-red-700' : '' }}">
                                            <div class="font-medium">CS: {{ $profession['cs'] }}</div>
                                            <div>EN: {{ $profession['en'] }}</div>

                                            <!-- Category and similarity information -->
                                            <div class="flex flex-wrap gap-2 mt-2">
                                                @if(isset($profession['csSimilarity']) || isset($profession['enSimilarity']))
                                                <div class="flex space-x-2 text-xs">
                                                    <span class="bg-gray-100 text-gray-800 px-1.5 py-0.5 rounded
                                                        {{ $profession['csSimilarity'] > 85 ? 'bg-green-100 text-green-800' : '' }}">
                                                        CS: {{ number_format($profession['csSimilarity'], 1) }}%
                                                    </span>
                                                    <span class="bg-gray-100 text-gray-800 px-1.5 py-0.5 rounded
                                                        {{ $profession['enSimilarity'] > 85 ? 'bg-green-100 text-green-800' : '' }}">
                                                        EN: {{ number_format($profession['enSimilarity'], 1) }}%
                                                    </span>
                                                </div>
                                                @endif

                                                @if(!empty($profession['category_name']))
                                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2 py-0.5 rounded
                                                    {{ isset($profession['categoryMatch']) && $profession['categoryMatch'] ? 'bg-red-100 text-red-800' : '' }}">
                                                    {{ $profession['category_name'] }}
                                                </span>
                                                @endif
                                            </div>
                                        </li>
                                        @empty
                                        <li class="px-4 py-3 text-gray-500 text-center">
                                            {{ __('hiko.no_global_professions') }}
                                        </li>
                                        @endforelse
                                    @else
                                    <li class="px-4 py-3 text-gray-500 text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m-8 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        {{ __('hiko.select_local_first') }}
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col-reverse sm:flex-row-reverse space-y-3 space-y-reverse sm:space-y-0 sm:space-x-3 sm:space-x-reverse">
                    <button type="button" wire:click="performManualMerge" wire:loading.attr="disabled" wire:target="performManualMerge"
                        @if(!$selectedLocalProfession || !$selectedGlobalProfession) disabled @endif
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-black text-white text-base font-medium hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black sm:w-auto sm:text-sm transition-colors duration-150
                            {{ (!$selectedLocalProfession || !$selectedGlobalProfession) ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m-8 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        {{ __('hiko.merge_selected') }}
                        <span wire:loading wire:target="performManualMerge" class="ml-2">
                            <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                    <button type="button" wire:click="closeManualMerge"
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm transition-colors duration-150">
                        {{ __('hiko.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Merge Preview Modal -->
    <div x-data="{ showModal: @entangle('showPreview') }" x-show="showModal" x-cloak class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                x-on:click="$wire.closePreview()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all my-4 w-full mx-2 max-h-[85vh] md:max-h-[90vh] sm:my-6 md:my-8 sm:align-middle sm:max-w-xl md:max-w-2xl lg:max-w-4xl xl:max-w-6xl"
                x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[70vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="flex justify-between items-center border-b pb-3">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            {{ __('hiko.merge_preview') }}
                        </h3>
                        <button type="button" wire:click="closePreview" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Merge Options -->
                    <div class="mt-4 bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <!-- Similarity Threshold Input -->
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-medium text-gray-700 whitespace-nowrap">{{ __('hiko.similarity_threshold') }}:</span>
                                <input type="range" min="80" max="100" step="1" wire:model.live.debounce.1000ms="similarityThreshold" class="w-24 sm:w-32">
                                <span class="text-sm font-medium text-gray-700 whitespace-nowrap">{{ $similarityThreshold }}%</span>
                            </div>
                            <!-- Merge Categories Checkbox -->
                            <div class="flex items-start">
                                <input id="preview-merge-categories" wire:model.live="mergeOptions.mergeCategories" type="checkbox"
                                    class="h-4 w-4 mt-1 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <label for="preview-merge-categories" class="ml-2 block text-sm text-gray-700">
                                    {{ __('hiko.merge_categories') }}
                                </label>
                            </div>
                            <!-- Merge Identities Checkbox -->
                            <div class="flex items-start">
                                <input id="preview-merge-identities" wire:model.live="mergeOptions.mergeIdentities" type="checkbox"
                                    class="h-4 w-4 mt-1 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <label for="preview-merge-identities" class="ml-2 block text-sm text-gray-700">
                                    {{ __('hiko.merge_identities') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Summary -->
                    <div class="mt-4 flex flex-wrap items-center gap-2 bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ $mergeStats['merged'] }} {{ __('hiko.will_merge') }}
                        </span>
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            {{ $mergeStats['skipped'] }} {{ __('hiko.will_skip') }}
                        </span>
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            {{ $mergeStats['total'] }} {{ __('hiko.total') }}
                        </span>
                    </div>

                    <!-- Preview Table -->
                    <div class="mt-4">
                        <div class="overflow-x-auto -mx-4 sm:mx-0">
                            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.local_profession') }}</th>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.global_profession') }}</th>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.similarity') }}</th>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($previewData as $item)
                                    <tr class="{{ $item['willMerge'] ? 'bg-green-50' : '' }}">
                                        <td class="px-3 sm:px-6 py-4 text-sm text-gray-500">
                                            <div class="truncate max-w-xs">CS: {{ $item['localCs'] }}</div>
                                            <div class="truncate max-w-xs">EN: {{ $item['localEn'] }}</div>
                                            @if(!empty($item['localCategoryName']))
                                            <div class="mt-1">
                                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-0.5 rounded">
                                                    {{ $item['localCategoryName'] }}
                                                </span>
                                            </div>
                                            @endif
                                        </td>
                                        <td class="px-3 sm:px-6 py-4 text-sm text-gray-500">
                                            @if($item['globalId'])
                                            <div class="truncate max-w-xs">CS: {{ $item['globalCs'] }}</div>
                                            <div class="truncate max-w-xs">EN: {{ $item['globalEn'] }}</div>
                                            @if(!empty($item['globalCategoryName']))
                                            <div class="mt-1">
                                                <span class="bg-red-50 text-red-700 text-xs font-medium px-2 py-0.5 rounded">
                                                    {{ $item['globalCategoryName'] }}
                                                </span>
                                            </div>
                                            @endif
                                            @else
                                            <span class="text-red-500">{{ __('hiko.not_found') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 sm:px-6 py-4 text-sm text-gray-500">
                                            @if($item['globalId'])
                                            <div class="flex items-center space-x-1" x-tooltip.raw="{{ __('hiko.cs_similarity_tooltip') }}">
                                                <span class="{{ $item['csSimilarity'] > $similarityThreshold ? 'text-green-600 font-medium' : '' }}">
                                                    CS: {{ number_format($item['csSimilarity'], 1) }}%
                                                </span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div class="flex items-center space-x-1" x-tooltip.raw="{{ __('hiko.en_similarity_tooltip') }}">
                                                <span class="{{ $item['enSimilarity'] > $similarityThreshold ? 'text-green-600 font-medium' : '' }}">
                                                    EN: {{ number_format($item['enSimilarity'], 1) }}%
                                                </span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            @if(isset($item['categoryInfo']))
                                            <div class="text-xs mt-1 {{ $item['categoryMatch'] ? 'text-green-600' : 'text-gray-500' }}">
                                                {{ $item['categoryInfo'] }}
                                            </div>
                                            @endif
                                            @else
                                            -
                                            @endif
                                        </td>
                                        <td class="px-3 sm:px-6 py-4 text-sm">
                                            @if($item['willMerge'])
                                            <div>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 flex items-center inline-flex">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    {{ __('hiko.will_merge') }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $item['mergeReason'] }}
                                            </div>
                                            @else
                                            <div>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 flex items-center inline-flex">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    {{ __('hiko.will_skip') }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $item['mergeReason'] }}
                                            </div>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="px-3 sm:px-6 py-4 text-sm text-center text-gray-500">
                                            {{ __('hiko.no_professions_to_preview') }}
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col-reverse sm:flex-row-reverse space-y-3 space-y-reverse sm:space-y-0 sm:space-x-3 sm:space-x-reverse">
                    <button type="button" wire:click="mergeAll" wire:loading.attr="disabled" wire:target="mergeAll"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-black text-base font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black sm:w-auto sm:text-sm transition-colors duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m-8 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        {{ __('hiko.perform_merge') }}
                        <span wire:loading wire:target="mergeAll" class="ml-2">
                            <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                    <button type="button" wire:click="closePreview"
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm transition-colors duration-150">
                        {{ __('hiko.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    Livewire.on('alert', data => {
        const { type, message } = data[0];
        if (type && message) {
            alert(`[${type.toUpperCase()}] ${message}`);
        }
    })
</script>
@endpush
