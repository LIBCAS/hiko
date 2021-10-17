<x-app-layout :title="$title">
    <x-success-alert />
    <x-create-link label="{{ __('Nový dopis') }}" link="{{ route('letters.create') }}" />
    <livewire:letters-table />
</x-app-layout>
