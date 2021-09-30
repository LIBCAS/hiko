<x-app-layout :title="$title">
    <x-success-alert />
    <a href="{{ route('users.create') }}" class="max-w-sm px-2 py-1 font-bold text-primary hover:underline">
        + {{ __('Nový účet') }}
    </a>
    <livewire:users-table />
</x-app-layout>
