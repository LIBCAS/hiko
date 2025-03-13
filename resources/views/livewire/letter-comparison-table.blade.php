<div>
    <x-filter-form>
        <label>
            <span class="block text-sm">{{ __('hiko.compare_comparison_type') }}</span>
            <x-select wire:model.defer="filters.compare_type" class="block w-full px-2 text-sm lg:w-64">
                <option value="full_text">{{ __('hiko.full_text') }}</option>
                <option value="other_columns">{{ __('hiko.compare_other_columns') }}</option>
            </x-select>
        </label>

        <label>
            <span class="block text-sm">{{ __('hiko.compare_select_tenant') }}</span>
            <x-select wire:model.defer="filters.tenant_to_compare" class="block w-full px-2 text-sm lg:w-64">
                <option value="">{{ __('hiko.compare_select_a_tenant') }}</option>
                @foreach($tenants as $tenantName)
                    <option value="{{ $tenantName }}">{{ $tenantName }}</option>
                @endforeach
            </x-select>
        </label>

        <label>
            <span class="block text-sm">{{ __('hiko.compare_order_by') }}</span>
            <x-select wire:model.defer="filters.order" class="w-full px-2 text-sm lg:w-64">
                <option value="similarity">{{ __('hiko.compare_similarity') }}</option>
                <option value="date_computed">{{ __('hiko.compare_date_computed') }}</option>
                <option value="letter_id">{{ __('hiko.compare_letter_id') }}</option>
            </x-select>
        </label>
    </x-filter-form>

    <div wire:loading class="mt-4">
        <p class="text-blue-500">Loading...</p>
    </div>

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
