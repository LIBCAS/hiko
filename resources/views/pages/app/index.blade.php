<x-app-layout :title="$title">
    <x-success-alert />

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">{{ __('hiko.application_info') }}</h1>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="p-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ __('hiko.specifications') }}
                </h3>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-200">
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">{{ __('hiko.app_version') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ config('hiko.version') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        @can('manage-users')
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                <div class="p-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        {{ __('hiko.application_name_settings') }}
                    </h3>
                </div>
                <form method="POST" action="{{ route('app.application-name.update') }}" class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="application_name_cs" class="block text-sm font-medium text-gray-700">
                                {{ __('hiko.application_name_cs') }}
                            </label>
                            <input
                                id="application_name_cs"
                                name="application_name_cs"
                                type="text"
                                value="{{ old('application_name_cs', $applicationNames['cs'] ?? $tenant->displayName('cs')) }}"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary"
                                required
                            >
                            @error('application_name_cs')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="application_name_en" class="block text-sm font-medium text-gray-700">
                                {{ __('hiko.application_name_en') }}
                            </label>
                            <input
                                id="application_name_en"
                                name="application_name_en"
                                type="text"
                                value="{{ old('application_name_en', $applicationNames['en'] ?? $tenant->displayName('en')) }}"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary"
                                required
                            >
                            @error('application_name_en')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <x-button-simple>
                        {{ __('hiko.save') }}
                    </x-button-simple>
                </form>
            </div>

            @if(!app()->environment('production'))
                <livewire:db-sync-tool />
            @endif
        @endcan
    </div>
</x-app-layout>
