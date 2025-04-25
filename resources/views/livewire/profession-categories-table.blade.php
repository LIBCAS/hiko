<div>
    <x-filter-form>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- CS Input with Live Search -->
            <label>
                <span class="block text-sm">CS</span>
                <div class="relative">
                    <x-input wire:model.live.debounce.300ms="filters.cs" class="block w-full px-2 text-sm" type="text" />
                    <div wire:loading.delay wire:target="filters.cs" class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
            </label>

            <!-- EN Input with Live Search -->
            <label>
                <span class="block text-sm">EN</span>
                <div class="relative">
                    <x-input wire:model.live.debounce.300ms="filters.en" class="block w-full px-2 text-sm" type="text" />
                    <div wire:loading.delay wire:target="filters.en" class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
            </label>

            <!-- Source Select -->
            <label>
                <span class="block text-sm">{{__('hiko.source')}}</span>
                <x-select wire:model.live.debounce.1000ms="filters.source" class="block w-full px-2 text-sm">
                    <option value="all">*</option>
                    <option value="local">{{__('hiko.local')}}</option>
                    <option value="global">{{__('hiko.global')}}</option>
                </x-select>
            </label>

            <!-- Order By Select -->
            <label>
                <span class="block text-sm">{{ __('hiko.order_by') }}</span>
                <x-select wire:model.live.debounce.1000ms="filters.order" class="w-full px-2 text-sm">
                    <option value="cs">CS</option>
                    <option value="en">EN</option>
                </x-select>
            </label>
        </div>
    </x-filter-form>

    <!-- Loading Indicator -->
    <div wire:loading.delay class="w-full text-center py-2">
        <div class="inline-flex items-center px-4 py-2 text-xs font-medium leading-4 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ __('Searching...') }}
        </div>
    </div>

    <!-- Results -->
    <div wire:loading.remove>
        @if(!empty($tableData['rows']))
            <x-table :tableData="$tableData" class="table-auto w-full mt-3" />
            <div class="w-full pl-1 mt-3">
                {{ $pagination->links() }}
            </div>
        @else
            <div class="mt-4">
                <p class="text-gray-700">{{ __('hiko.compare_no_results') }}</p>
            </div>
        @endif
    </div>
</div>
