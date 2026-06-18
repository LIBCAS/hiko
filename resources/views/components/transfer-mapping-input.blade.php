@props(['type', 'sourceId', 'locationType' => null, 'sourceType' => null])

@php
    $fieldName = "mappings[{$type}][{$sourceId}]";
    $oldValue = old("mappings.{$type}.{$sourceId}", '');
    preg_match('/^(local|global)-(\d+)$/', (string) $oldValue, $oldMatches);
    $initialScope = $oldMatches[1] ?? null;
    $initialId = isset($oldMatches[2]) ? (int) $oldMatches[2] : null;
    $editRoutes = [
        'identities.local' => 'identities.edit',
        'places.local' => 'places.edit',
        'places.global' => 'global.places.edit',
        'keywords.local' => 'keywords.edit',
        'keywords.global' => 'global.keywords.edit',
        'locations.local' => 'locations.edit',
        'locations.global' => 'global.locations.edit',
    ];
    $initialRoute = $initialScope ? ($editRoutes["{$type}.{$initialScope}"] ?? null) : null;
    $initialUrl = $initialRoute && $initialId ? route($initialRoute, $initialId) : '';
    $initialLabel = '';

    if ($initialScope && $initialId) {
        $table = $initialScope === 'global'
            ? 'global_' . $type
            : tenancy()->tenant->table_prefix . '__' . $type;
        $record = \Illuminate\Support\Facades\DB::connection('tenant')->table($table)->find($initialId);

        if ($record) {
            $initialLabel = $record->name;

            if ($type === 'keywords') {
                $translations = json_decode($record->name, true);
                $initialLabel = $translations[app()->getLocale()]
                    ?? $translations['cs']
                    ?? $translations['en']
                    ?? $record->name;
            } elseif ($type === 'identities') {
                $dates = trim(($record->birth_year ?? '') . ' - ' . ($record->death_year ?? ''));
                $initialLabel .= $dates !== '-' ? " ({$dates})" : '';
            } elseif ($type === 'places') {
                $initialLabel = implode(', ', array_filter([
                    $record->name,
                    $record->division ?? null,
                    $record->country ?? null,
                ]));
            }

            $initialLabel .= ' (' . __('hiko.' . $initialScope) . ')';
        }
    }
@endphp

<div
    x-data="transferMappingInput({
        url: @js(route('inter-tenant-transfers.mapping-search', ['type' => $type])),
        initialValue: @js((string) $oldValue),
        initialId: @js($initialId),
        initialLabel: @js($initialLabel),
        initialUrl: @js($initialUrl),
        locationType: @js($locationType),
        sourceType: @js($sourceType)
    })"
    class="relative min-w-64"
>
    <input type="hidden" name="{{ $fieldName }}" x-model="value">
    <input type="text"
        x-model="query"
        @focus="open = true; search()"
        @input.debounce.250ms="search()"
        @keydown.escape="open = false"
        class="w-full rounded-md border-gray-300 px-3 py-2 text-sm"
        placeholder="{{ __('hiko.search_target_entity') }}"
        autocomplete="off">
    <button x-show="value" type="button" @click="clear()"
        class="absolute right-2 top-2 text-gray-500 hover:text-black" title="{{ __('hiko.reset') }}">×</button>
    <a x-show="value && selectedUrl" :href="selectedUrl" target="_blank"
        class="mt-1 inline-block text-xs font-semibold text-primary-dark hover:underline">
        {{ __('hiko.open_target_record') }} <span x-text="'#' + selectedId"></span>
    </a>
    <div x-show="open" @click.outside="open = false"
        class="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto border border-gray-200 bg-white shadow-lg">
        <div x-show="loading" class="px-3 py-2 text-sm text-gray-500">{{ __('hiko.loading') }}</div>
        <template x-for="option in options" :key="option.value">
            <button type="button" @click="select(option)"
                class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-100">
                <span x-text="option.label"></span>
                <span class="text-gray-400" x-text="' #' + option.id"></span>
            </button>
        </template>
        <div x-show="!loading && query && options.length === 0"
            class="px-3 py-2 text-sm text-gray-500">{{ __('hiko.no_results') }}</div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function transferMappingInput(config) {
                return {
                    query: config.initialLabel || '',
                    value: config.initialValue,
                    selectedId: config.initialId || '',
                    locationType: config.locationType,
                    sourceType: config.sourceType,
                    options: [],
                    loading: false,
                    open: false,
                    selectedUrl: config.initialUrl || '',
                    requestSequence: 0,
                    abortController: null,
                    search() {
                        const query = this.query.trim();
                        if (query.length < 1) {
                            this.abortController?.abort();
                            this.options = [];
                            this.loading = false;
                            return;
                        }

                        const sequence = ++this.requestSequence;
                        this.abortController?.abort();
                        this.abortController = new AbortController();
                        this.loading = true;
                        const url = new URL(config.url, window.location.origin);
                        url.searchParams.set('search', query);
                        if (this.locationType) {
                            url.searchParams.set('location_type', this.locationType);
                        }
                        if (this.sourceType) {
                            url.searchParams.set('source_type', this.sourceType);
                        }

                        fetch(url, {
                            signal: this.abortController.signal,
                            headers: { 'Accept': 'application/json' }
                        })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`Search failed with status ${response.status}`);
                                }
                                return response.json();
                            })
                            .then(options => {
                                if (sequence === this.requestSequence) {
                                    this.options = Array.isArray(options) ? options : [];
                                    this.open = true;
                                }
                            })
                            .catch(error => {
                                if (error.name !== 'AbortError' && sequence === this.requestSequence) {
                                    this.options = [];
                                }
                            })
                            .finally(() => {
                                if (sequence === this.requestSequence) {
                                    this.loading = false;
                                }
                            });
                    },
                    select(option) {
                        this.value = option.value;
                        this.selectedId = option.id;
                        this.query = option.label;
                        this.selectedUrl = option.edit_url || '';
                        this.open = false;
                    },
                    clear() {
                        this.abortController?.abort();
                        this.value = '';
                        this.selectedId = '';
                        this.query = '';
                        this.selectedUrl = '';
                        this.options = [];
                        this.loading = false;
                    }
                };
            }
        </script>
    @endpush
@endonce
