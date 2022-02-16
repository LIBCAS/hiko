<div>
    <x-filter-form>
        <label>
            <span class="block text-sm">
                ID
            </span>
            <x-input wire:model.defer="filters.id" class="block w-full lg:w-64" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.from') }}</span>
            <x-input wire:model.defer="filters.after" class="block w-full lg:w-64" type="date" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.to') }}</span>
            <x-input wire:model.defer="filters.before" class="block w-full lg:w-64" type="date" />
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.status') }}
            </span>
            <x-select wire:model.defer="filters.status" class="w-full lg:w-64">
                <option value="">
                    ---
                </option>
                <option value="publish">
                    {{ __('hiko.publish') }}
                </option>
                <option value='draft'>
                    {{ __('hiko.draft') }}
                </option>
            </x-select>
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.order_by') }}
            </span>
            <x-select wire:model.defer="filters.order" class="w-full lg:w-64">
                <option value="id">
                    ID
                </option>
                <option value='date_computed'>
                    {{ __('hiko.by_date') }}
                </option>
                <option value='status'>
                    {{ __('hiko.status') }}
                </option>
            </x-select>
        </label>
        <div>
            <span class="block text-sm">
                {{ __('hiko.order_direction') }}
            </span>
            <div class="flex flex-col">
                <x-radio name="direction" wire:model.defer="filters.direction" aria-label="{{ __('hiko.ascending')}}" title="{{ __('hiko.ascending')}}" label="ASC" value="asc" />
                <x-radio name="direction" wire:model.defer="filters.direction" aria-label="{{ __('hiko.descending')}}" title="{{ __('hiko.descending')}}"  label="DESC" value="desc" />
            </div>
        </div>
        </label>
    </x-filter-form>
    <x-table :tableData="$tableData" />
    <div class="w-full pl-1 mt-3">
        {{ $pagination->links() }}
    </div>
</div>
