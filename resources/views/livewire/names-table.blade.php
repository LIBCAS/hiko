<div>
    <x-filter-form>
        <label>
            <span class="block text-sm">
                CS
            </span>
            <x-input wire:model.defer="filters.cs" class="block w-full px-2 text-sm lg:w-64" type="text" />
        </label>
        <label>
            <span class="block text-sm">
                EN
            </span>
            <x-input wire:model.defer="filters.en" class="block w-full px-2 text-sm lg:w-64" type="text" />
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.order_by') }}
            </span>
            <x-select wire:model.defer="filters.order" class="w-full px-2 text-sm lg:w-64">
                <option value="cs">
                    CS
                </option>
                <option value="en">
                    EN
                </option>
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
