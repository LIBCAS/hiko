<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-4 flex justify-between items-center">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                {{ __('hiko.database_sync') }}
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                {{ __('hiko.sync_description') }}
            </p>
        </div>
        <div>
            <button
                wire:click="sync"
                wire:loading.attr="disabled"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg wire:loading wire:target="sync" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="sync">{{ __('hiko.run_sync') }}</span>
                <span wire:loading wire:target="sync">{{ __('hiko.syncing_please_wait') }}</span>
            </button>
        </div>
    </div>

    <div class="border-t border-gray-200">
        <div class="px-4 py-3 bg-gray-50 text-xs font-medium text-gray-500 uppercase tracking-wider">
            {{ __('hiko.sync_history') }}
        </div>
        <ul class="divide-y divide-gray-200">
            @forelse($history as $log)
                <li class="px-4 py-4 flex items-center justify-between hover:bg-gray-50">
                    <div class="flex items-center">
                        <div>
                            <div>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $log->status === 'completed' ? 'bg-green-100 text-green-800' : ($log->status === 'running' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst(__('hiko.' . $log->status)) }}
                                </span>
                                <span class="ml-3 text-sm text-gray-900">{{ $log->user_email }}</span>
                                <span class="ml-3 text-sm text-gray-500">{{ $log->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                            @if($log->message)
                                <div class="mt-1 text-sm text-gray-500 normal-case break-words">
                                    {{ $log->message }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $log->duration_seconds ? $log->duration_seconds . 's' : '-' }}
                    </div>
                </li>
            @empty
                <li class="px-4 py-4 text-sm text-gray-500 text-center">
                    {{ __('hiko.no_sync_history_found') }}
                </li>
            @endforelse
        </ul>
    </div>
</div>
