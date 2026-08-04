<x-app-layout :title="$title">
    <x-success-alert />

    @if(session('error'))
        <div class="p-4 mb-6 text-sm text-red-800 bg-red-100 rounded-lg">{{ session('error') }}</div>
    @endif

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h1 class="mb-6 text-2xl font-semibold text-gray-900">{{ __('hiko.data_tools') }}</h1>

        <div class="mb-6 overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="p-4">
                <h2 class="text-lg font-medium text-gray-900">{{ __('hiko.metadata_digest_title') }}</h2>
                <p class="max-w-3xl mt-1 text-sm text-gray-500">{{ __('hiko.metadata_digest_description') }}</p>
            </div>

            @if(!$digestEnabled)
                <div class="px-4 py-3 text-sm text-amber-800 border-t border-amber-200 bg-amber-50">
                    {{ __('hiko.metadata_digest_disabled') }}
                </div>
            @endif

            @if(auth()->user()->role === 'admin')
                <form method="POST" action="{{ route('data.metadata-digest.store') }}" class="px-4 py-5 border-t border-gray-200 sm:p-6">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="period_start" class="block text-sm font-medium text-gray-700">{{ __('hiko.metadata_digest_from') }}</label>
                            <input id="period_start" name="period_start" type="datetime-local"
                                value="{{ old('period_start', $periodStart->format('Y-m-d\\TH:i')) }}"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary" required>
                            @error('period_start')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="period_end" class="block text-sm font-medium text-gray-700">{{ __('hiko.metadata_digest_to') }}</label>
                            <input id="period_end" name="period_end" type="datetime-local"
                                value="{{ old('period_end', $periodEnd->format('Y-m-d\\TH:i')) }}"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary" required>
                            @error('period_end')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="mt-4">
                        <x-button-simple :disabled="!$digestEnabled">{{ __('hiko.metadata_digest_send_to_me') }}</x-button-simple>
                    </div>
                </form>
            @endif

            <div class="border-t border-gray-200">
                <div class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase bg-gray-50">
                    {{ __('hiko.metadata_digest_history') }}
                </div>
                <ul class="divide-y divide-gray-200">
                    @forelse($deliveries as $delivery)
                        <li class="flex items-center justify-between px-4 py-4 text-sm">
                            <div>
                                <span class="font-semibold">#{{ $delivery->run->id }}</span>
                                <span class="ml-2">{{ __('hiko.metadata_digest_status_' . $delivery->status) }}</span>
                                <span class="ml-2 text-gray-500">
                                    {{ $delivery->run->period_start->format('d.m.Y H:i') }}–{{ $delivery->run->period_end->format('d.m.Y H:i') }}
                                </span>
                                @if($delivery->error_message)
                                    <p class="mt-1 text-red-600">{{ $delivery->error_message }}</p>
                                @endif
                            </div>
                            <span class="text-gray-500">{{ $delivery->created_at->format('d.m.Y H:i') }}</span>
                        </li>
                    @empty
                        <li class="px-4 py-4 text-sm text-center text-gray-500">{{ __('hiko.metadata_digest_no_history') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="mb-6 overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="flex items-center justify-between p-4">
                <div>
                    <h2 class="text-lg font-medium text-gray-900">{{ __('hiko.inter_tenant_transfers') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('hiko.inter_tenant_transfers_data_description') }}</p>
                </div>
                <a href="{{ route('inter-tenant-transfers.index') }}" class="font-semibold text-primary hover:underline">{{ __('hiko.open') }}</a>
            </div>
        </div>

        @if(!app()->environment('production'))
            <livewire:db-sync-tool />
        @endif
    </div>
</x-app-layout>
