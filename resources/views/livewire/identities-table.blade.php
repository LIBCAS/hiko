<div>
    <x-filter-form>
        <label>
            <span class="block text-sm">
                {{ __('hiko.name') }}
            </span>
            <x-input wire:model.live="filters.name" class="block w-full px-2 text-sm lg:w-32" type="text"/>
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.related_names') }}
            </span>
            <x-input wire:model.live="filters.related_names" class="block w-full px-2 text-sm lg:w-32" type="text"/>
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.type') }}
            </span>
            <x-select wire:model.live="filters.type" class="w-full px-2 text-sm lg:w-32">
                <option value="">---</option>
                <option value="person">
                    {{ __('hiko.person') }}
                </option>
                <option value="institution">
                    {{ __('hiko.institution') }}
                </option>
            </x-select>
        </label>
        <label>
            <span class="block text-sm">
               {{ __('hiko.profession') }}
            </span>
            <x-input wire:model.live="filters.profession" class="block w-full px-2 text-sm lg:w-32" type="text"/>
        </label>
        <label>
            <span class="block text-sm">
               {{ __('hiko.category') }}
            </span>
            <x-input wire:model.live="filters.category" class="block w-full px-2 text-sm lg:w-32" type="text"/>
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.note') }}
            </span>
            <x-input wire:model.live="filters.note" class="block w-full px-2 text-sm lg:w-32" type="text"/>
        </label>
    </x-filter-form>
    <x-table :tableData="$tableData"/>
    <div class="w-full pl-1 mt-3">
        {{ $pagination->links() }}
    </div>
</div>
