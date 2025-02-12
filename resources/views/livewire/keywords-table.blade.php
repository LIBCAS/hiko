<div>
    <x-filter-form>
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
