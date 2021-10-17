<x-app-layout :title="$title">
    <x-success-alert />
    <x-create-link label="{{ __('Nová osoba / instituce') }}" link="{{ route('identities.create') }}" />
    <a href="{{ route('identities.export') }}" class="inline-block mt-3 text-sm font-semibold">
        {{ __('Exportovat') }}
    </a>
    <livewire:identities-table :labels="$labels" />
</x-app-layout>
