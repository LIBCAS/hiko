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
                <a href="{{ route('keywords.export') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.export') }}
                </a>
                <a href="{{ route('keywords.validation') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.input_control') }}
                </a>
                <a href="{{ route('keywords.local-merge') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.local_merging') }}
                </a>
                @can('manage-users')
                    <a href="{{ route('keywords.global-merge') }}" class="inline-block text-sm font-semibold">
                        {{ __('hiko.global_merging') }}
                    </a>
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
