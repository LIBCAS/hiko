<div x-data="{ showConfig: true, confirmOpen: false }">
    <div x-show="showConfig" x-transition class="bg-white shadow rounded-lg p-6 mb-6 relative">
        <button @click="showConfig = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600" title="{{ __('hiko.hide_configuration') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
        </button>

        <h2 class="text-xl font-semibold mb-4">{{ __('hiko.merging_criteria') }}</h2>
        <p class="text-sm text-gray-600 mb-4">{{ __('hiko.global_profession_merging_criteria_description') }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" wire:model.live="criteria" value="name_similarity" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                <div class="w-full">
                    <div class="font-medium">{{ __('hiko.merge_by_name_similarity') }}</div>
                    <div class="text-sm text-gray-600">{{ __('hiko.merge_by_name_similarity_desc') }}</div>
                    <div x-show="$wire.criteria.includes('name_similarity')" class="mt-2" x-transition>
                        <label class="flex items-center text-sm">
                            <span class="mr-2">{{ __('hiko.threshold') }}:</span>
                            <input type="number" wire:model.live.debounce.300ms="nameSimilarityThreshold" min="50" max="100" class="w-20 px-2 py-1 border rounded text-sm">
                            <span class="ml-1">%</span>
                        </label>
                    </div>
                </div>
            </label>
        </div>
    </div>

    <div x-show="!showConfig" class="mb-6">
        <button @click="showConfig = true" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            {{ __('hiko.change_criteria') }}
        </button>
    </div>

    <div class="mb-6 p-4 bg-gray-100 rounded-md border border-gray-200 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">{{ __('hiko.name') }}</label>
            <input type="text" wire:model.live.debounce.500ms="filters.name" class="text-sm w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="{{ __('hiko.search') }}...">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">{{ __('hiko.method') }}</label>
            <select wire:model.live="filters.strategy" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 w-40">
                <option value="all">{{ __('hiko.all') }}</option>
                <option value="merge">{{ __('hiko.merge_only') }}</option>
                <option value="move">{{ __('hiko.move_only') }}</option>
            </select>
        </div>
    </div>

    <div class="mb-4 flex justify-between items-center">
        <div class="text-sm text-gray-600">{{ __('hiko.selected_count', ['count' => count($selectedIds)]) }}</div>
        <button type="button" @click="confirmOpen = true" wire:loading.attr="disabled" @disabled(count($selectedIds) === 0) class="px-4 py-2 bg-primary text-white rounded hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 transition-colors duration-150">
            <svg wire:loading class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            {{ __('hiko.execute_merge') }}
        </button>
    </div>

    <div class="bg-white shadow overflow-hidden border border-gray-200 sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10"><input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-primary focus:ring-primary"></th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.local_profession') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.method') }} & {{ __('hiko.reason') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.global_profession') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($previewData as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap align-top">
                            @if(($item['selectable'] ?? true))
                                <input type="checkbox" wire:model.live="selectedIds" id="profession-{{ $item['local']->id }}" value="{{ $item['local']->id }}" class="rounded border-gray-300 text-primary focus:ring-primary">
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="text-sm font-medium text-gray-900 flex gap-3"><a :href="`/professions/{{ $item['local']->id }}/edit`" target="_blank" class="text-primary hover:underline font-semibold px-2">{{ $item['local']->id }}</a><span>{{ $item['local']->getTranslation('name', app()->getLocale()) }}</span></div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ optional($item['local']->profession_category)->getTranslation('name', app()->getLocale()) ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap align-top">
                            @if($item['strategy'] === 'merge')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ __('hiko.merge') }}</span>
                                <div class="text-xs text-gray-400 mt-1 pl-1">{{ $item['reason'] }}</div>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('hiko.move') }}</span>
                                <div class="text-xs mt-1 pl-1 {{ ($item['selectable'] ?? true) ? 'text-gray-400' : 'text-red-600 font-semibold' }}">{{ $item['reason'] }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top">
                            @if($item['global'])
                                <div class="text-sm text-gray-900 font-medium flex gap-3"><a :href="`/global-professions/{{ $item['global']->id }}/edit`" target="_blank" class="text-primary hover:underline font-semibold px-2">{{ $item['global']->id }}</a><span>{{ $item['global']->getTranslation('name', app()->getLocale()) }}</span></div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ optional($item['global']->profession_category)->getTranslation('name', app()->getLocale()) ?? '—' }}</div>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">{{ __('hiko.no_results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $previewData->links() }}</div>

<x-global-merge-confirm-modal
    wire:key="global-profession-confirm-{{ md5(json_encode($confirmationItems)) }}-{{ count($selectedIds) }}-{{ $confirmationMoreCount }}"
    show="confirmOpen"
    :items="$confirmationItems"
    :selected-count="count($selectedIds)"
    :merge-count="$confirmationMergeCount"
    :move-count="$confirmationMoveCount"
    :more-count="$confirmationMoreCount"
/>
</div>
