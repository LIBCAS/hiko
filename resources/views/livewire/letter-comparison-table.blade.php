<div>
    <!-- Filters for comparison type and tenant selection with Enter key search -->
    <x-filter-form wire:keydown.enter="search">
        <label>
            <span class="block text-sm">Comparison Type:</span>
            <x-select wire:model="filters.compare_type" class="block w-full px-2 text-sm lg:w-64">
                <option value="full_text">Full Text</option>
                <option value="other_columns">Other Columns</option>
            </x-select>
        </label>

        <label>
            <span class="block text-sm">Select Tenant:</span>
            <x-select wire:model="filters.tenant_to_compare" class="block w-full px-2 text-sm lg:w-64">
                <option value="">Select a Tenant</option>
                @foreach($tenants as $tenantName)
                    <option value="{{ $tenantName }}">{{ $tenantName }}</option>
                @endforeach
            </x-select>
        </label>

        <label>
            <span class="block text-sm">Order By:</span>
            <x-select wire:model.defer="filters.order" class="w-full px-2 text-sm lg:w-64">
                <option value="letter_id">Letter ID</option>
                <option value="similarity">Similarity</option>
            </x-select>
        </label>
    </x-filter-form>

    <!-- Loading indicator -->
    <div wire:loading wire:target="search" class="mt-4">
        <p class="text-blue-500">Loading comparison results, please wait...</p>
    </div>

    <!-- Display the results table -->
    @if(count($tableData['rows']) > 0)
        <x-table :tableData="$tableData" class="table-auto w-full mt-3" />
    @else
        <div class="mt-4">
            <p class="text-gray-700">No results found. Please adjust your filters and try again.</p>
        </div>
    @endif

    <!-- Pagination controls -->
    @if($pagination->total() > 0)
        <div class="w-full pl-1 mt-3">
            {{ $pagination->links() }}
        </div>
    @endif
</div>
