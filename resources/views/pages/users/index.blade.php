<x-app-layout :title="$title">
    <x-success-alert />
        <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
            <x-create-link label="{{ __('hiko.new_account') }}" link="{{ route('users.create') }}" />
        </div>
    <livewire:users-table :roles="$roles" />
</x-app-layout>
