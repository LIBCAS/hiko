@props([
    'show' => 'confirmOpen',
    'title' => null,
    'items' => 'confirmationItems',
    'selectedCount' => 'selectedIds.length',
    'mergeCount' => 'confirmationMergeCount',
    'moveCount' => 'confirmationMoveCount',
    'moreCount' => 'confirmationMoreCount',
    'previewTitle' => null,
    'previewItems' => 'confirmationPreviewItems',
    'previewNote' => null,
    'confirmAction' => 'executeMerge()',
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
                    <b>{{ __('hiko.selected') }}: <span x-text="{{ $selectedCount }}"></span></b> <i>({{ __('hiko.to_be_merged') }}: <span x-text="{{ $mergeCount }}"></span>; {{ __('hiko.to_be_moved') }}: <span x-text="{{ $moveCount }}"></span>)</i>
                </div>

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
                            <template x-for="item in {{ $items }}" :key="`${item.local}-${item.method}-${item.result ?? 'empty'}`">
                                <tr>
                                    <td class="px-4 py-3 align-top text-sm text-gray-900">
                                        <template x-if="item.local_url">
                                            <a :href="item.local_url" target="_blank" class="text-primary hover:underline font-medium" x-text="item.local || '—'"></a>
                                        </template>
                                        <template x-if="!item.local_url">
                                            <span x-text="item.local || '—'"></span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 align-top text-sm text-gray-700" x-text="item.method || '—'"></td>
                                    <td class="px-4 py-3 align-top text-sm text-gray-700">
                                        <template x-if="item.result_url">
                                            <a :href="item.result_url" target="_blank" class="text-primary hover:underline font-medium" x-text="item.result || '—'"></a>
                                        </template>
                                        <template x-if="!item.result_url">
                                            <span x-text="item.result || '—'"></span>
                                        </template>
                                    </td>
                                </tr>
                            </template>

                            <tr x-show="{{ $moreCount }} > 0" class="bg-gray-50">
                                <td colspan="3" class="px-4 py-3 text-sm text-gray-500 italic">
                                    <span x-text="`{{ __('hiko.merge_confirmation_more_records', ['count' => '__COUNT__']) }}`.replace('__COUNT__', {{ $moreCount }})"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 border border-gray-200 rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">
                            {{ $previewTitle ?? __('hiko.final_merged_record') }}
                        </h3>
                    </div>

                    <dl class="divide-y divide-gray-200">
                        <template x-for="item in {{ $previewItems }}" :key="item.label">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 px-4 py-3">
                                <dt class="text-sm font-medium text-gray-500" x-text="item.label"></dt>
                                <dd class="text-sm text-gray-900 md:col-span-2" x-text="item.value || '—'"></dd>
                            </div>
                        </template>
                    </dl>

                    @if($previewNote)
                        <div class="px-4 py-3 bg-blue-50 border-t border-blue-100 text-sm text-blue-800">
                            {{ $previewNote }}
                        </div>
                    @endif
                </div>
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
                    class="px-4 py-2 text-sm font-medium text-white bg-primary rounded hover:bg-black"
                    @click="{{ $confirmAction }}; {{ $show }} = false"
                >
                    {{ __('hiko.execute_merge') }}
                </button>
            </div>
        </div>
    </div>
</div>
