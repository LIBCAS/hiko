<div>
    <x-filter-form>
        <div class="relative">
            <button wire:click="mergeAll"
                wire:loading.attr="disabled"
                wire:target="mergeAll"
                class="flex items-center text-black px-6 py-3 text-sm font-semibold border border-black rounded-full bg-transparent hover:text-white hover:bg-black active:bg-black active:text-white focus:text-black transition ease-in-out duration-150">
                
                <!-- Button Text -->
                {{ __('hiko.merge') }}
        
                <!-- Loading Animation Inside the Button -->
                <span wire:loading wire:target="mergeAll" class="ml-2">
                    <svg class="w-5 h-5 animate-spin text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </span>
            </button>
        
            <!-- Success Message -->
            @if (session()->has('success'))
                <div class="bg-green-100 text-green-700 px-6 py-3 mt-2 rounded">
                    {{ session('success') }}
                </div>
            @endif
        
            <!-- Warning Message -->
            @if (session()->has('warning'))
                <div class="bg-yellow-100 text-yellow-700 px-6 py-3 mt-2 rounded">
                    {{ session('warning') }}
                </div>
            @endif
        
            <!-- Error Message -->
            @if (session()->has('error'))
                <div class="bg-red-100 text-red-700 px-6 py-3 mt-2 rounded">
                    {{ session('error') }}
                </div>
            @endif
        </div> 
        <label>
            <span class="block text-sm">
                CS
            </span>
            <x-input wire:model.live="filters.cs" class="block w-full px-2 text-sm lg:w-64" type="text" />
        </label>
        <label>
            <span class="block text-sm">
                EN
            </span>
            <x-input wire:model.live="filters.en" class="block w-full px-2 text-sm lg:w-64" type="text" />
        </label>
        <label>
            <span class="block text-sm">
                {{__('hiko.source')}}
            </span>
            <x-select wire:model.live="filters.source" class="block w-full px-2 text-sm lg:w-36">
                <option value="all">*</option>
                <option value="local">{{__('hiko.local')}}</option>
                <option value="global">{{__('hiko.global')}}</option>
            </x-select>
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.order_by') }}
            </span>
            <x-select wire:model.live="filters.order" class="w-full px-2 text-sm lg:w-24">
                <option value="cs">CS</option>
                <option value="en">EN</option>
            </x-select>
        </label>
    </x-filter-form>
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
