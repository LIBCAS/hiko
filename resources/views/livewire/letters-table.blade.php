<div>
    <x-table :tableData="$tableData" />
    <div class="w-full pl-1 mt-3">
        {{ $pagination->links() }}
    </div>
</div>

@push('scripts')
    <script>
    Livewire.on('filtersChanged', filters => {
        updateExportUrl(filters, document.getElementById('export-url'));
    })
    </script>
@endpush
