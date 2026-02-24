<x-app-layout :title="$title">
    <x-success-alert />

    <!-- Professions Section -->
    @can('manage-metadata')
        <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
            <div class="flex items-center space-x-4">
                <x-create-link
                    label="{{ __('hiko.new_profession') }}"
                    link="{{ route('professions.create') }}"
                />
                @can('manage-users')
                    <x-create-link
                        label="{{ __('hiko.new_global_profession') }}"
                        link="{{ route('global.professions.create') }}"
                    />
                @endcan
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('professions.export') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.export') }}
                </a>
                <a href="{{ route('professions.validation') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.input_control') }}
                </a>
                <a href="{{ route('professions.local-merge') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.local_merging') }}
                </a>
                @can('manage-users')
                    <a href="{{ route('professions.global-merge') }}" class="inline-block text-sm font-semibold">
                        {{ __('hiko.global_merging') }}
                    </a>
                @endcan
            </div>
        </div>
    @endcan

    <!-- Table for displaying professions -->
    <div id="professions-wrapper">
        <livewire:professions-table />
    </div>

    <!-- Profession Categories Section -->
    @can('manage-metadata')
        <div class="flex items-center space-x-4 mt-8">
            <x-create-link label="{{ __('hiko.new_professions_category') }}" link="{{ route('professions.category.create') }}" />
            @can('manage-users')
                <x-create-link label="{{ __('hiko.new_global_profession_category') }}" link="{{ route('global.professions.category.create') }}" />
            @endcan
        </div>
        <a href="{{ route('professions.category.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('hiko.export') }}
        </a>
    @endcan

    @cannot('manage-metadata')
        <p class="mt-16 font-bold">
            {{ __('hiko.professions_category') }}
        </p>
    @endcannot

    <div id="categories-wrapper">
        <livewire:profession-categories-table />
    </div>
</x-app-layout>
