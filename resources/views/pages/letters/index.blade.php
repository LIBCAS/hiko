<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <x-create-link label="{{ __('hiko.new_letter') }}" link="{{ route('letters.create') }}" />
    @endcan
    <div class="w-20 mt-3">
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
    <livewire:letters-table />
</x-app-layout>
