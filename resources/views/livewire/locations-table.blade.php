<div>
    <x-filter-form>
        <label>
            <span class="block text-sm">
                {{ __('hiko.name') }}
            </span>
            <x-input wire:model.defer="filters.name" class="block w-full px-2 text-sm lg:w-64" type="text" />
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.type') }}
            </span>
            <x-select wire:model.defer="filters.type" class="w-full px-2 text-sm lg:w-64">
                <option value="">---</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}">
                        {{ __("hiko.{$type}") }}
                    </option>
                @endforeach
            </x-select>
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.order_by') }}
            </span>
            <x-select wire:model.defer="filters.order" class="w-full px-2 text-sm lg:w-64">
                <option value="type">
                    {{ __('hiko.type') }}
                </option>
                <option value="name">
                    {{ __('hiko.name') }}
                </option>
            </x-select>
        </label>
    </x-filter-form>
    <x-table :tableData="$tableData" />
    <div class="w-full pl-1 mt-3">
        {{ $pagination->links() }}
    </div>
</div>
