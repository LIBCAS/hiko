<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
            <div class="flex items-center space-x-4">
                <x-create-link label="{{ __('hiko.new_place') }}" link="{{ route('places.create') }}" />
                @can('manage-users')
                    <x-create-link label="{{ __('hiko.new_global_place') }}" link="{{ route('global.places.create') }}" />
                @endcan
            </div>
            <div class="flex items-center gap-4">
                <x-loading-link href="{{ route('places.export') }}">
                    {{ __('hiko.export') }}
                </x-loading-link>
                <x-loading-link href="{{ route('places.validation') }}">
                    {{ __('hiko.input_control') }}
                </x-loading-link>
                <x-loading-link href="{{ route('places.local-merge') }}">
                    {{ __('hiko.local_merging') }}
                </x-loading-link>
                <x-loading-link href="{{ route('places.global-merge') }}">
                    {{ __('hiko.global_merging') }}
                </x-loading-link>
            </div>
        </div>
    @endcan
    <livewire:places-table />
</x-app-layout>
