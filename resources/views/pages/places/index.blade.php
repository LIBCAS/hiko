<x-app-layout :title="$title">
    <x-success-alert />
    <x-create-link label="{{ __('Nové místo') }}" link="{{ route('places.create') }}" />
    <a href="{{ route('places.export') }}" class="inline-block mt-3 text-sm font-semibold">
        {{ __('Exportovat') }}
    </a>
    <livewire:places-table />
</x-app-layout>
