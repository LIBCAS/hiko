<form wire:submit="search" class="flex flex-col h-full text-gray-900">
    <header class="flex items-center justify-between flex-shrink-0 gap-4 px-4 py-3 border-b sm:px-6">
        <div>
            <h2 id="letter-filters-title" class="text-xl font-semibold">{{ __('hiko.filters') }}</h2>
            <p class="text-sm text-gray-600">{{ __('hiko.filter_draft_help') }}</p>
        </div>
        <button type="button" @click="open = false; $dispatch('cancel-letter-filters')"
            aria-label="{{ __('hiko.cancel') }}" class="p-2 rounded-md hover:bg-gray-100 focus:ring-2 focus:ring-primary">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
        </button>
    </header>
    <div class="flex flex-col flex-1 min-h-0 md:flex-row">
        <nav aria-label="{{ __('hiko.filters') }}" class="flex flex-shrink-0 gap-1 p-3 overflow-auto border-b md:block md:w-56 md:border-b-0 md:border-r">
            @foreach($sections as $sectionKey => $section)
                @php($activeCount = count(array_intersect(array_keys($section['fields']), array_keys($activeDraft))))
                <a href="#letter-filter-{{ $sectionKey }}" @click.prevent="document.getElementById('letter-filter-{{ $sectionKey }}').scrollIntoView({ block: 'start' })"
                    class="block px-3 py-2 text-sm whitespace-nowrap rounded-md hover:bg-gray-100 focus:ring-2 focus:ring-primary">
                    {{ __('hiko.' . $section['label']) }}@if($activeCount) <span class="ml-1 font-semibold text-primary">({{ $activeCount }})</span> @endif
                </a>
            @endforeach
        </nav>
        <div class="flex-1 min-w-0 p-4 space-y-6 overflow-y-auto sm:p-6" id="letter-filter-scroll">
            @if($errors->any())
                <div role="alert" class="p-3 text-red-800 border border-red-300 rounded-md">
                    <x-form-errors />
                </div>
            @endif
            <div class="max-w-lg">
                <label for="letter-filter-match" class="block mb-1 font-semibold">{{ __('hiko.filter_match_mode') }}</label>
                <x-select id="letter-filter-match" wire:model="draft.match" class="w-full text-sm">
                    <option value="all">{{ __('hiko.match_all_filters') }}</option>
                    <option value="any">{{ __('hiko.match_any_filter') }}</option>
                </x-select>
            </div>
            @foreach($sections as $sectionKey => $section)
                <section id="letter-filter-{{ $sectionKey }}" class="pt-4 border-t scroll-mt-4" aria-labelledby="letter-filter-heading-{{ $sectionKey }}">
                    <h3 id="letter-filter-heading-{{ $sectionKey }}" class="mb-4 text-lg font-semibold">{{ __('hiko.' . $section['label']) }}</h3>
                    @if($sectionKey === 'date')
                        <p class="mb-4 text-sm text-gray-600">{{ __('hiko.filter_date_help') }}</p>
                    @elseif(in_array($sectionKey, ['author', 'recipient', 'origin', 'destination', 'manifestations', 'resources']))
                        <p class="mb-4 text-sm text-gray-600">{{ __('hiko.filter_' . $sectionKey . '_help') }}</p>
                    @endif
                    <div class="grid items-start grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">
                        @foreach($section['fields'] as $field => [$type, $label])
                            @include('livewire.partials.letter-filter-control')
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
    <footer class="flex flex-wrap items-center justify-between flex-shrink-0 gap-3 px-4 py-3 border-t bg-gray-50 sm:px-6">
        <button type="button" wire:click="resetFilters" class="px-4 py-2 text-sm text-red-700 border border-red-700 rounded-md hover:bg-red-50">{{ __('hiko.reset') }}</button>
        <div class="flex gap-3">
            <button type="button" @click="open = false; $dispatch('cancel-letter-filters')" class="px-4 py-2 text-sm border border-gray-400 rounded-md hover:bg-gray-100">{{ __('hiko.cancel') }}</button>
            <x-button-simple type="submit" wire:loading.attr="disabled" wire:target="search" class="px-4 py-2 !mt-0">
                {{ __('hiko.filter_apply') }}
                <span wire:loading wire:target="search" class="ml-2" aria-hidden="true">…</span>
            </x-button-simple>
        </div>
    </footer>
</form>
