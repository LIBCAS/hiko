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
                <x-loading-link href="{{ route('professions.export') }}">
                    {{ __('hiko.export') }}
                </x-loading-link>
                <x-loading-link href="{{ route('professions.validation') }}">
                    {{ __('hiko.input_control') }}
                </x-loading-link>
                <x-loading-link href="{{ route('professions.local-merge') }}">
                    {{ __('hiko.local_merging') }}
                </x-loading-link>
                @can('manage-users')
                    <x-loading-link href="{{ route('professions.global-merge') }}">
                        {{ __('hiko.global_merging') }}
                    </x-loading-link>
                @endcan
            </div>
        </div>
    @endcan

    <!-- Table for displaying professions -->
    <div id="professions-wrapper">
        <livewire:professions-table />
    </div>

    <!-- Profession Categories Section -->
    <div class="flex items-center justify-between gap-4 flex-wrap mt-8 mb-6">
        @can('manage-metadata')
            <div class="flex items-center gap-4 flex-wrap">
                <x-create-link label="{{ __('hiko.new_professions_category') }}" link="{{ route('professions.category.create') }}" />
                @can('manage-users')
                    <x-create-link label="{{ __('hiko.new_global_profession_category') }}" link="{{ route('global.professions.category.create') }}" />
                @endcan
            </div>
        @else
            <p class="font-bold">{{ __('hiko.professions_category') }}</p>
        @endcan

        <div class="flex items-center gap-4 flex-wrap ml-auto">
            @can('manage-metadata')
                <x-loading-link href="{{ route('professions.category.export') }}">
                    {{ __('hiko.export') }}
                </x-loading-link>
            @endcan
            @can('view-metadata')
                <x-loading-link href="{{ route('professions.category.validation') }}">
                    {{ __('hiko.input_control') }}
                </x-loading-link>
            @endcan
        </div>
    </div>

    <div id="categories-wrapper">
        <livewire:profession-categories-table />
    </div>
</x-app-layout>
