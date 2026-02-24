<div class="space-y-6">
    <div class="bg-white p-6 shadow rounded">
        <h3 class="text-lg font-bold mb-4">{{ __('hiko.consistency_check') }}</h3>
        <p class="text-sm text-gray-600 mb-4">
            {{ __('hiko.consistency_check_description') }}
        </p>

        <div class="flex gap-4 items-center mb-6">
            <label class="flex items-center gap-2">
                <span class="text-sm font-semibold">{{ __('hiko.source') }}:</span>
                <select wire:model="scope" class="border rounded px-3 py-2 text-sm">
                    <option value="all">{{ __('hiko.all') }}</option>
                    <option value="local">{{ __('hiko.local') }}</option>
                    <option value="global">{{ __('hiko.global') }}</option>
                </select>
            </label>

            <button wire:click="scan" wire:loading.attr="disabled" class="px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 disabled:opacity-25 disabled:cursor-not-allowed transition ease-in-out duration-150 flex items-center gap-2">
                <svg wire:loading class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove>{{ __('hiko.run_process') }}</span>
                <span wire:loading>{{ __('hiko.validating') }}...</span>
            </button>
        </div>

        @if(!empty($issues))
            <div class="overflow-x-auto border rounded">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase">
                        <tr>
                            <th class="px-4 py-3">{{ __('hiko.location') }}</th>
                            <th class="px-4 py-3">{{ __('hiko.type') }}</th>
                            <th class="px-4 py-3">{{ __('hiko.source') }}</th>
                            <th class="px-4 py-3">{{ __('hiko.validation_error') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('hiko.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($issues as $issue)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $issue['name'] }}</td>
                                <td class="px-4 py-3">{{ $issue['type'] }}</td>
                                <td class="px-4 py-3">{{ $issue['source'] }}</td>
                                <td class="px-4 py-3 text-red-600">{{ $issue['error'] }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ $issue['type'] === 'global' ? route('global.locations.edit', $issue['id']) : route('locations.edit', $issue['id']) }}"
                                       target="_blank"
                                       class="text-primary hover:underline font-semibold">
                                        {{ __('hiko.edit') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-sm text-gray-500">
                {{ __('hiko.total_issues_found', ['count' => count($issues)]) }}
            </div>
        @elseif($isScanning === false && count($issues) === 0)
            <div class="text-center py-8 bg-gray-50 rounded border border-dashed border-gray-300">
                <p class="text-gray-500 italic">{{ __('hiko.validation_not_run_or_no_issues') }}</p>
            </div>
        @endif
    </div>
</div>
