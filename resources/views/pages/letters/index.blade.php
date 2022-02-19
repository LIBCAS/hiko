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
    <livewire:letters-table />
</x-app-layout>
