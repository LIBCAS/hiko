<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
            <div class="flex items-center space-x-4">
                <x-create-link label="{{ __('hiko.new_identity') }}" link="{{ route('identities.create') }}" />
                @can('manage-users')
                    <x-create-link label="{{ __('hiko.new_global_identity') }}" link="{{ route('global.identities.create') }}" />
                @endcan
            </div>
            <div class="flex items-center gap-4">
                <x-loading-link
                    href="{{ route('identities.export') }}"
                    onclick="
                        const exportUrl = new URL(this.href);
                        exportUrl.search = '';
                        exportUrl.searchParams.set('_current_filters', '1');
                        document.querySelectorAll('[data-identity-filter]').forEach((field) => {
                            exportUrl.searchParams.set(field.dataset.identityFilter, field.value ?? '');
                        });
                        this.href = exportUrl.toString();
                    ">
                    {{ __('hiko.export') }}
                </x-loading-link>
                <x-loading-link href="{{ route('identities.validation') }}">
                    {{ __('hiko.input_control') }}
                </x-loading-link>
                <x-loading-link href="{{ route('identities.local-merge') }}">
                    {{ __('hiko.local_merging') }}
                </x-loading-link>
                <x-loading-link href="{{ route('identities.global-merge') }}">
                    {{ __('hiko.global_merging') }}
                </x-loading-link>
                @can('manage-users')
                    <x-loading-link href="{{ route('identities.global-strict-merge') }}">
                        {{ __('hiko.strict_global_merging') }}
                    </x-loading-link>
                @endcan
            </div>
        </div>
    @endcan
    <livewire:identities-table :labels="$labels" />
</x-app-layout>
