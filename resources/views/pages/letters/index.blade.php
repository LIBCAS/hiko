<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <x-create-link label="{{ __('hiko.new_letter') }}" link="{{ route('letters.create') }}" />
    @endcan
    <p>
        <a href="{{ route('letters.preview') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('hiko.preview_all_letters') }}
        </a>
    </p>
    <div class="w-20 mt-3">
        <x-dropdown label="{{ __('hiko.export') }}" class="font-semibold" :alignRight="false">
            <div class="py-1 bg-white ring-1 ring-black ring-opacity-5">
                <a href="{{ route('letters.export') }}"
                    class="block w-full px-2 py-1 text-sm text-left text-gray-700 hover:bg-gray-100">
                    {{ __('hiko.basic_export') }}
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
