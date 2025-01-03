<x-app-layout :title="$title">
    <x-success-alert />

    <!-- Keywords Section -->
    @can('manage-metadata')
        <div class="flex items-center space-x-4">
            <x-create-link label="{{ __('hiko.new_keyword') }}" link="{{ route('keywords.create') }}" />
            @can('manage-users')
                <x-create-link label="{{ __('hiko.new_global_keyword') }}" link="{{ route('global.keywords.create') }}" />
            @endcan
        </div>
        <a href="{{ route('keywords.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('hiko.export') }}
        </a>
    @endcan

    <livewire:keywords-table />

    <!-- Keydwords Categories Section -->
    @can('manage-metadata')
        <div class="flex items-center space-x-4 mt-8">
            <x-create-link label="{{ __('hiko.new_keywords_category') }}" link="{{ route('keywords.category.create') }}" />
            @can('manage-users')
                <x-create-link label="{{ __('hiko.new_global_keywords_category') }}" link="{{ route('global.keywords.category.create') }}" />
            @endcan
        </div>
        <a href="{{ route('keywords.category.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('hiko.export') }}
        </a>
    @endcan

    @cannot('manage-metadata')
        <p class="mt-16 font-bold">
            {{ __('hiko.keywords_category') }}
        </p>
    @endcannot

    <livewire:keyword-categories-table />
</x-app-layout>
