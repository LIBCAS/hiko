<div>
<x-filter-form>
            <label>
                <span class="block text-sm">
                    CS
                </span>
                <x-input wire:model.defer="filters.cs" class="block w-full lg:w-64" type="text" />
            </label>
            <label>
                <span class="block text-sm">
                    EN
                </span>
                <x-input wire:model.defer="filters.en" class="block w-full lg:w-64" type="text" />
            </label>
            <label>
                <span class="block text-sm">
                    {{ __('hiko.order_by') }}
                </span>
                <x-select wire:model.defer="filters.order" class="w-full lg:w-64">
                    <option value="cs">
                        CS
                    </option>
                    <option value="en">
                        EN
                    </option>
                </x-select>
            </label>
        </x-filter-form>
    <x-table :tableData="$tableData" />
    <div class="w-full pl-1 mt-3">
        {{ $pagination->links() }}
    </div>
</div>
