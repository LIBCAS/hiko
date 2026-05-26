<x-app-layout :title="$title">
    <x-success-alert />

    <!-- Keywords Section -->
    @can('manage-metadata')
        <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
            <div class="flex items-center space-x-4">
                <x-create-link label="{{ __('hiko.new_keyword') }}" link="{{ route('keywords.create') }}" />
                @can('manage-users')
                    <x-create-link label="{{ __('hiko.new_global_keyword') }}" link="{{ route('global.keywords.create') }}" />
                @endcan
            </div>
            <div class="flex items-center gap-4">
                <x-loading-link href="{{ route('keywords.export') }}">
                    {{ __('hiko.export') }}
                </x-loading-link>
                <x-loading-link href="{{ route('keywords.validation') }}">
                    {{ __('hiko.input_control') }}
                </x-loading-link>
                <x-loading-link href="{{ route('keywords.local-merge') }}">
                    {{ __('hiko.local_merging') }}
                </x-loading-link>
                @can('manage-users')
                    <x-loading-link href="{{ route('keywords.global-merge') }}">
                        {{ __('hiko.global_merging') }}
                    </x-loading-link>
                @endcan
            </div>
        </div>
    @endcan

    <livewire:keywords-table />

    <!-- Keydwords Categories Section -->
    @can('manage-metadata')
        <div class="flex items-center space-x-4 mt-8">
            <x-create-link label="{{ __('hiko.new_keyword_category') }}" link="{{ route('keywords.category.create') }}" />
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
