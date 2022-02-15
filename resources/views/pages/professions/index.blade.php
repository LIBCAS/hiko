<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <x-create-link label="{{ __('hiko.new_profession') }}" link="{{ route('professions.create') }}" />
        <a href="{{ route('professions.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('hiko.export') }}
        </a>
    @endcan
    <livewire:professions-table model="Profession" />
    @can('manage-metadata')
        <x-create-link label="{{ __('hiko.new_professions_category') }}" link="{{ route('professions.category.create') }}"
            class="mt-16" />
        <a href="{{ route('professions.category.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('hiko.export') }}
        </a>
    @endcan
    @cannot('manage-metadata')
        <p class="mt-16 font-bold">
            {{ __('hiko.professions_category') }}
        </p>
    @endcannot
    <livewire:professions-table model="ProfessionCategory" />
</x-app-layout>
