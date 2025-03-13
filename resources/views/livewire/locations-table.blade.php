<div>
    <x-filter-form>
        <label>
            <span class="block text-sm">
                {{ __('hiko.name') }}
            </span>
            <x-input wire:model.live="filters.name" class="block w-full px-2 text-sm lg:w-64" type="text" />
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.type') }}
            </span>
            <x-select wire:model.live="filters.type" class="w-full px-2 text-sm lg:w-64">
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
            <x-select wire:model.live="filters.order" class="w-full px-2 text-sm lg:w-64">
                <option value="type">
                    {{ __('hiko.type') }}
                </option>
                <option value="name">
                    {{ __('hiko.name') }}
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
