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
                {{ __('hiko.role') }}
            </span>
            <x-select wire:model.defer="filters.role" class="w-full px-2 text-sm lg:w-64">
                <option value="">---</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}">
                        {{ __("hiko.{$role}") }}
                    </option>
                @endforeach
            </x-select>
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.status') }}
            </span>
            <x-select wire:model.defer="filters.status" class="w-full px-2 text-sm lg:w-64">
                <option value="">---</option>
                <option value="1">
                    {{ __('hiko.active') }}
                </option>
                <option value="0">
                    {{ __('hiko.inactive') }}
                </option>
            </x-select>
        </label>
    </x-filter-form>
    <x-table :tableData="$tableData" />
    <div class="w-full pl-1 mt-3">
        {{ $pagination->links() }}
    </div>
</div>
