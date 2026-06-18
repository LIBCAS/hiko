@props([
    'type',
    'sourceId',
    'transferId',
    'defaultValue' => null,
    'copyEnabled' => false,
    'locationType' => null,
    'sourceType' => null,
])

@php
    $fieldName = "mappings[{$type}][{$sourceId}]";
    $fieldKey = "mappings.{$type}.{$sourceId}";
    $oldInput = session()->getOldInput();
    $hasOldValue = \Illuminate\Support\Arr::has($oldInput, $fieldKey);
    $selectedValue = $hasOldValue ? old($fieldKey, '') : ($defaultValue ?? '');
    preg_match('/^(local|global)-(\d+)$/', (string) $selectedValue, $oldMatches);
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
        initialValue: @js((string) $selectedValue),
        initialId: @js($initialId),
        initialLabel: @js($initialLabel),
        initialUrl: @js($initialUrl),
        locationType: @js($locationType),
        sourceType: @js($sourceType),
        copyEnabled: @js((bool) $copyEnabled),
        csrfToken: @js(csrf_token()),
        copyPreviewUrl: @js($copyEnabled ? route('inter-tenant-transfers.copy-dependency.preview', [
            'transfer' => $transferId,
            'type' => $type,
            'sourceId' => $sourceId,
        ]) : null),
        copyUrl: @js($copyEnabled ? route('inter-tenant-transfers.copy-dependency.store', [
            'transfer' => $transferId,
            'type' => $type,
            'sourceId' => $sourceId,
        ]) : null)
    })"
    class="relative min-w-64"
>
    <input type="hidden" name="{{ $fieldName }}" x-model="value">
    <div class="flex items-start gap-2">
        <div class="relative flex-1">
            <input type="text"
                x-model="query"
                @focus="open = true; search()"
                @input.debounce.250ms="search()"
                @keydown.escape="open = false"
                class="w-full rounded-md border-gray-300 px-3 py-2 pr-8 text-sm"
                placeholder="{{ __('hiko.search_target_entity') }}"
                autocomplete="off">
            <button x-show="value" type="button" @click="clear()"
                class="absolute right-2 top-2 text-gray-500 hover:text-black" title="{{ __('hiko.reset') }}">×</button>
        </div>
        <button x-show="copyEnabled" type="button" @click="prepareCopy()" :disabled="copyLoading"
            class="shrink-0 border border-primary px-3 py-2 text-sm font-semibold text-primary-dark hover:bg-primary hover:text-white disabled:opacity-50">
            {{ __('hiko.create_copy') }}
        </button>
    </div>
    <p x-show="copyError && !copyModal" x-text="copyError" class="mt-1 text-xs text-red-700"></p>
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

    <template x-teleport="body">
        <div x-show="copyModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background-color: rgba(28, 25, 23, 0.55);"
            @keydown.escape.window="if (copyModal && !copyLoading) closeCopyModal()">
            <div @click.outside="if (!copyLoading) closeCopyModal()"
                class="w-full rounded bg-white p-6 shadow-xl"
                style="max-width: 32rem;">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('hiko.create_copy') }}</h3>
                <p class="mt-3 text-sm leading-6 text-gray-700">
                    <template x-if="copyMessageParts.length > 0">
                        <span>
                            <template x-for="(part, index) in copyMessageParts" :key="index">
                                <span>
                                    <span x-show="part.type === 'text'" x-text="part.text"></span>
                                    <a x-show="part.type === 'link'" :href="part.url" target="_blank"
                                        class="font-semibold text-primary-dark underline hover:text-primary"
                                        x-text="part.text"></a>
                                </span>
                            </template>
                        </span>
                    </template>
                    <span x-show="copyMessageParts.length === 0" x-text="copyPreview?.message"></span>
                </p>

                <label x-show="copyCategoryOptions.length > 0" class="mt-4 block text-sm">
                    <span class="font-semibold text-gray-900">{{ __('hiko.keyword_category') }}</span>
                    <select x-model="copyCategoryId" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">{{ __('hiko.select') }}</option>
                        <template x-for="category in copyCategoryOptions" :key="category.id">
                            <option :value="String(category.id)" x-text="category.label + ' #' + category.id"></option>
                        </template>
                    </select>
                </label>

                <p x-show="copyError" x-text="copyError" class="mt-3 text-sm text-red-700"></p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="closeCopyModal()" :disabled="copyLoading"
                        class="rounded border border-gray-400 bg-white px-4 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-100 disabled:opacity-50">
                        {{ __('hiko.cancel') }}
                    </button>
                    <button type="button" @click="confirmCopy()" :disabled="copyConfirmDisabled"
                        class="rounded bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-50">
                        <span x-show="!copyLoading">{{ __('hiko.confirm') }}</span>
                        <span x-show="copyLoading">{{ __('hiko.copying') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
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
                    copyEnabled: config.copyEnabled,
                    options: [],
                    loading: false,
                    open: false,
                    selectedUrl: config.initialUrl || '',
                    requestSequence: 0,
                    abortController: null,
                    copyModal: false,
                    copyLoading: false,
                    copyPreview: null,
                    copyCategoryId: '',
                    copyError: '',
                    get copyCategoryOptions() {
                        return Array.isArray(this.copyPreview?.category_options)
                            ? this.copyPreview.category_options
                            : [];
                    },
                    get copyMessageParts() {
                        return Array.isArray(this.copyPreview?.message_parts)
                            ? this.copyPreview.message_parts
                            : [];
                    },
                    get copyConfirmDisabled() {
                        return Boolean(
                            this.copyLoading
                            || (this.copyCategoryOptions.length > 0 && !this.copyCategoryId)
                        );
                    },
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
                    async prepareCopy() {
                        if (!this.copyEnabled || this.copyLoading) return;

                        this.copyLoading = true;
                        this.copyError = '';
                        try {
                            const response = await fetch(config.copyPreviewUrl, {
                                credentials: 'same-origin',
                                headers: { 'Accept': 'application/json' }
                            });
                            const data = await this.responseData(response);
                            if (!response.ok) {
                                throw new Error(data.message || @js(__('hiko.copy_failed')));
                            }

                            this.copyPreview = data;
                            this.copyCategoryId = '';
                            this.copyModal = true;
                        } catch (error) {
                            this.copyError = error.message || @js(__('hiko.copy_failed'));
                        } finally {
                            this.copyLoading = false;
                        }
                    },
                    async confirmCopy() {
                        if (this.copyConfirmDisabled) return;

                        this.copyLoading = true;
                        this.copyError = '';
                        try {
                            const response = await fetch(config.copyUrl, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': config.csrfToken
                                },
                                body: JSON.stringify({
                                    category_id: this.copyCategoryId || null
                                })
                            });
                            const data = await this.responseData(response);
                            if (!response.ok) {
                                throw new Error(data.message || @js(__('hiko.copy_failed')));
                            }
                            if (!data.option) {
                                throw new Error(@js(__('hiko.copy_failed')));
                            }

                            this.select(data.option);
                            this.closeCopyModal();
                        } catch (error) {
                            this.copyError = error.message || @js(__('hiko.copy_failed'));
                        } finally {
                            this.copyLoading = false;
                        }
                    },
                    async responseData(response) {
                        const contentType = response.headers.get('content-type') || '';
                        if (contentType.includes('application/json')) {
                            return await response.json();
                        }

                        return { message: await response.text() };
                    },
                    closeCopyModal() {
                        this.copyModal = false;
                        this.copyPreview = null;
                        this.copyCategoryId = '';
                        this.copyError = '';
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
