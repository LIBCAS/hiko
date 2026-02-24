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
                <a href="{{ route('places.export') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.export') }}
                </a>
                <a href="{{ route('places.validation') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.input_control') }}
                </a>
                <a href="{{ route('places.local-merge') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.local_merging') }}
                </a>
                <a href="{{ route('places.global-merge') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.global_merging') }}
                </a>
            </div>
        </div>
    @endcan
    <livewire:places-table />
</x-app-layout>
