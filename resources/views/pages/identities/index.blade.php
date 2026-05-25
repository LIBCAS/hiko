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
                <a
                    href="{{ route('identities.export') }}"
                    class="inline-block text-sm font-semibold"
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
                </a>
                <a href="{{ route('identities.validation') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.input_control') }}
                </a>
                <a href="{{ route('identities.local-merge') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.local_merging') }}
                </a>
                <a href="{{ route('identities.global-merge') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.global_merging') }}
                </a>
                @can('manage-users')
                    <a href="{{ route('identities.global-strict-merge') }}" class="inline-block text-sm font-semibold">
                        {{ __('hiko.strict_global_merging') }}
                    </a>
                @endcan
            </div>
        </div>
    @endcan
    <livewire:identities-table :labels="$labels" />
</x-app-layout>
