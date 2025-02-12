<div>
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

@push('scripts')
    <script>
    Livewire.on('filtersChanged', filters => {
        updateExportUrl(filters, document.getElementById('export-url'));
    })
    </script>
@endpush
