<x-app-layout :title="$title">
    <x-success-alert />
    <x-page-lock
        scope="tenant"
        resource-type="identity_local_merge"
        :redirect-url="route('identities')"
        :read-only-on-deny="true" />

    <livewire:local-identity-merge />
</x-app-layout>
