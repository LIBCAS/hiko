<x-app-layout :title="$title">
    <x-success-alert />
    <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
        <div class="flex items-center gap-4">
            @can('manage-metadata')
                <x-create-link label="{{ __('hiko.new_letter') }}" link="{{ route('letters.create') }}" />
            @endcan

            <livewire:filters-button />

            <x-dropdown label="{{ __('hiko.export') }}" class="font-semibold" :alignRight="false">
                <div class="py-1 bg-white ring-1 ring-black ring-opacity-5">
                    <a href="{{ route('letters.export') }}" id="export-url"
                        class="block w-full px-2 py-1 text-sm text-left text-gray-700 hover:bg-gray-100">
                        {{ __('hiko.export_selected') }}
                    </a>
                    @if ($mainCharacter)
                        <a href="{{ route('letters.export.palladio.character', ['role' => 'author']) }}"
                            class="block w-full px-2 py-1 text-sm text-left text-gray-700 hover:bg-gray-100">
                            {{ __('hiko.letters_from', ['name' => $mainCharacter]) }}
                        </a>
                        <a href="{{ route('letters.export.palladio.character', ['role' => 'recipient']) }}"
                            class="block w-full px-2 py-1 text-sm text-left text-gray-700 hover:bg-gray-100">
                            {{ __('hiko.letters_to', ['name' => $mainCharacter]) }}
                        </a>
                    @endif
                </div>
            </x-dropdown>
        </div>
        <div>
            <livewire:sorting-form :sortingOptions="[
                'id' => __('hiko.id'),
                'date_computed' => __('hiko.by_letter_date'),
                'updated_at' => __('hiko.by_update'),
                'author' => __('hiko.by_author'),
                'recipient' => __('hiko.by_recipient'),
                'origin' => __('hiko.by_origin'),
                'destination' => __('hiko.by_destination'),
                'abstract' => __('hiko.by_abstract'),
                'media' => __('hiko.by_media'),
            ]" />       
        </div>
    </div>

    <livewire:letters-table />

    @push('scripts')
        <script>
            Livewire.on('filtersChanged', filters => {
                updateExportUrl(filters, document.getElementById('export-url'));
            });
        </script>
    @endpush
</x-app-layout>
