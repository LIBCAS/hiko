<x-app-layout :title="$title">
    <x-success-alert />

    <!-- Professions Section -->
    @can('manage-metadata')
        <div class="flex items-center space-x-4">
            <x-create-link label="{{ __('hiko.new_profession') }}" link="{{ route('professions.create') }}" />
            @can('manage-users')
                <x-create-link label="{{ __('hiko.new_global_profession') }}" link="{{ route('global.professions.create') }}" />
            @endcan
        </div>
        <a href="{{ route('professions.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('hiko.export') }}
        </a>
    @endcan

    <livewire:professions-table />

    @cannot('manage-metadata')
        <p class="mt-16 font-bold">
            {{ __('hiko.professions_category') }}
        </p>
    @endcannot

    <livewire:profession-categories-table />
</x-app-layout>
