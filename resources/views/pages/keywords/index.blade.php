<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <x-create-link label="{{ __('hiko.new_keyword') }}" link="{{ route('keywords.create') }}" />
        <a href="{{ route('keywords.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('hiko.export') }}
        </a>
    @endcan
    <livewire:keywords-table />
    @can('manage-metadata')
        <x-create-link label="{{ __('hiko.new_keyword_category') }}" link="{{ route('keywords.category.create') }}"
            class="mt-16" />
        <a href="{{ route('keywords.category.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('hiko.export') }}
        </a>
    @endcan
    @cannot('manage-metadata')
        <p class="mt-16 font-bold">
            {{ __('hiko.keyword_categories') }}
        </p>
    @endcannot
    <livewire:names-table model="KeywordCategory" routePrefix="keywords.category" />
</x-app-layout>
