<div>
    <x-filter-form>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <label>
                <span class="block text-sm">{{ __('hiko.name') }}</span>
                <x-input wire:model.live.debounce.1000ms="filters.name" class="block w-full px-2 text-sm" type="text" />
            </label>
            <label>
                <span class="block text-sm">{{ __('hiko.related_names') }}</span>
                <x-input wire:model.live.debounce.1000ms="filters.related_names" class="block w-full px-2 text-sm" type="text" />
            </label>
            <label>
                <span class="block text-sm">{{ __('hiko.type') }}</span>
                <x-select wire:model.live.debounce.1000ms="filters.type" class="w-full px-2 text-sm">
                    <option value="">---</option>
                    <option value="person">{{ __('hiko.person') }}</option>
                    <option value="institution">{{ __('hiko.institution') }}</option>
                </x-select>
            </label>
            <label>
                <span class="block text-sm">{{ __('hiko.profession') }}</span>
                <x-input wire:model.live.debounce.1000ms="filters.profession" class="block w-full px-2 text-sm" type="text" />
            </label>
            <label>
                <span class="block text-sm">{{ __('hiko.category') }}</span>
                <x-input wire:model.live.debounce.1000ms="filters.category" class="block w-full px-2 text-sm" type="text" />
            </label>
            <label>
                <span class="block text-sm">{{ __('hiko.religion') }}</span>
                <x-input wire:model.live.debounce.1000ms="filters.religion" class="block w-full px-2 text-sm" type="text" />
            </label>
            <label>
                <span class="block text-sm">{{ __('hiko.note') }}</span>
                <x-input wire:model.live.debounce.1000ms="filters.note" class="block w-full px-2 text-sm" type="text" />
            </label>
            <label>
                <span class="block text-sm">{{ __('hiko.global_identity') }}</span>
                <x-input wire:model.live.debounce.1000ms="filters.global_identity" class="block w-full px-2 text-sm" type="text" />
            </label>
            {{-- Source Filter --}}
            <label>
                <span class="block text-sm">{{ __('hiko.source') }}</span>
                <x-select wire:model.live.debounce.1000ms="filters.source" class="w-full px-2 text-sm">
                    <option value="all">{{ __('hiko.all') }}</option>
                    <option value="local">{{ __('hiko.local') }}</option>
                    <option value="global">{{ __('hiko.global') }}</option>
                </x-select>
            </label>
        </div>
    </x-filter-form>

    <x-table :tableData="$tableData" />

    <div class="w-full pl-1 mt-3">
        {{ $pagination->links() }}
    </div>
</div>
