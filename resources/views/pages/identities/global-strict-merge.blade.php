<x-app-layout :title="$title">
    <x-success-alert />
    <x-page-lock
        scope="global"
        resource-type="identity_strict_global_merge"
        :redirect-url="route('identities')"
        :read-only-on-deny="true" />

    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('identities') }}" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
            <span>←</span> {{ __('hiko.back_to_identities') }}
        </a>
    </div>

    <livewire:global-identity-strict-merge />
</x-app-layout>
