@props([
    'show' => 'confirmOpen',
    'title' => null,
    'description' => null,
    'items' => [],
    'selectedCount' => 0,
    'mergeCount' => 0,
    'moveCount' => 0,
    'moreCount' => 0,
    'emptyMessage' => null,
])

<div
    {{ $attributes }}
    x-cloak
    x-show="{{ $show }}"
    x-on:keydown.escape.window="{{ $show }} = false"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <div class="fixed inset-0 bg-gray-900/60" @click="{{ $show }} = false"></div>

    <div class="relative min-h-screen px-4 py-8 flex items-center justify-center">
        <div
            @click.outside="{{ $show }} = false"
            class="relative w-full max-w-4xl bg-white rounded-xl shadow-2xl overflow-hidden"
        >
            <div class="px-6 py-5 border-b border-gray-200 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ $title ?? __('hiko.confirm_merge_title') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('hiko.confirm_merge_description') }}
                    </p>
                    @if($description)
                        <p class="mt-1 text-sm text-gray-600">{{ $description }}</p>
                    @endif
                </div>

                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-700"
                    @click="{{ $show }} = false"
                >
                    <span class="sr-only">{{ __('hiko.cancel') }}</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="px-6 py-5">
                <div class="mb-4 text-sm text-gray-600">
                    <b>{{ __('hiko.selected_count', ['count' => $selectedCount]) }}</b> <i>({{ __('hiko.to_be_merged_count', ['count' => $mergeCount]) }}; {{ __('hiko.to_be_moved_count', ['count' => $moveCount]) }})</i>
                </div>

                @if(!empty($items))
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('hiko.original_record') }}
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('hiko.method') }}
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('hiko.final_record') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($items as $item)
                                    <tr>
                                        <td class="px-4 py-3 align-top text-sm text-gray-900">
                                            @if(!empty($item['local_url']))
                                                <a href="{{ $item['local_url'] }}" target="_blank" class="text-primary hover:underline font-medium">
                                                    {{ $item['local'] ?? '—' }}
                                                </a>
                                            @else
                                                {{ $item['local'] ?? '—' }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 align-top text-sm text-gray-700">{{ $item['method'] ?? '—' }}</td>
                                        <td class="px-4 py-3 align-top text-sm text-gray-700">
                                            @if(!empty($item['result_url']))
                                                <a href="{{ $item['result_url'] }}" target="_blank" class="text-primary hover:underline font-medium">
                                                    {{ $item['result'] ?? '—' }}
                                                </a>
                                            @else
                                                {{ $item['result'] ?? '—' }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach

                                @if($moreCount > 0)
                                    <tr class="bg-gray-50">
                                        <td colspan="3" class="px-4 py-3 text-sm text-gray-500 italic">
                                            {{ __('hiko.merge_confirmation_more_records', ['count' => $moreCount]) }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500">
                        {{ $emptyMessage ?? __('hiko.no_results') }}
                    </p>
                @endif
            </div>

            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-100"
                    @click="{{ $show }} = false"
                >
                    {{ __('hiko.cancel') }}
                </button>

                <button
                    type="button"
                    wire:click="execute"
                    wire:loading.attr="disabled"
                    wire:target="execute"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary rounded hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    @click="{{ $show }} = false"
                >
                    <svg wire:loading wire:target="execute" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('hiko.execute_merge') }}
                </button>
            </div>
        </div>
    </div>
</div>
