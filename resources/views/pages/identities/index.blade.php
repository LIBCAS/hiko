<x-app-layout :title="$title">
    <x-success-alert />
    <a href="{{ route('identities.create') }}" class="max-w-sm px-2 py-1 mb-6 font-bold text-primary hover:underline">
        + {{ __('Nová osoba / instituce') }}
    </a>
    <livewire:identities-table :labels="$labels" />
</x-app-layout>
