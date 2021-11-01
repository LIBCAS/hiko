<x-app-layout :title="$title">
    <x-success-alert />
    <x-create-link label="{{ __('Nový účet') }}" link="{{ route('users.create') }}" />
    <livewire:users-table :roles="$roles"/>
</x-app-layout>
