<div>
    <div class="mb-6 bg-white shadow rounded-lg p-6">
        @if(!$candidateScanComplete)
            <h2 class="text-xl font-semibold mb-4">{{ __('hiko.similarity_candidate_scan') }}</h2>
            <p class="text-sm text-gray-600 mb-4">{{ __('hiko.strict_global_similarity_scan_description') }}</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" wire:model.live="candidateCriteria" value="name_similarity" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                    <div class="w-full">
                        <div class="font-medium">{{ __('hiko.merge_by_name_similarity') }}</div>
                        <div class="text-sm text-gray-600">{{ __('hiko.strict_global_name_similarity_desc') }}</div>
                        @if(in_array('name_similarity', $candidateCriteria, true))
                            <div class="mt-2">
                                <label class="flex items-center text-sm">
                                    <span class="mr-2">{{ __('hiko.threshold') }}:</span>
                                    <input type="number" wire:model.blur="candidateNameSimilarityThreshold" min="0" max="100" class="w-20 px-2 py-1 border rounded text-sm">
                                    <span class="ml-1">%</span>
                                </label>
                            </div>
                        @endif
                    </div>
                </label>

                <label class="flex items-start p-3 border rounded hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" wire:model.live="candidateCriteria" value="date_similarity" class="mt-1 mr-2 rounded border-gray-300 text-primary">
                    <div class="w-full">
                        <div class="font-medium">{{ __('hiko.merge_by_date_similarity') }}</div>
                        <div class="text-sm text-gray-600">{{ __('hiko.strict_global_date_similarity_desc') }}</div>
                        @if(in_array('date_similarity', $candidateCriteria, true))
                            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="text-sm">
                                    <span class="block mb-1">{{ __('hiko.birth_year') }} <span class="lowercase">{{ __('hiko.tolerance') }}</span></span>
                                    <select wire:model.blur="candidateBirthYearTolerance" class="w-full px-2 py-1 border rounded text-sm">
                                        @foreach($candidateYearToleranceOptions as $option)
                                            <option value="{{ $option }}">{{ (int)$option === 0 ? __('hiko.exact') : '±' . $option }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="text-sm">
                                    <span class="block mb-1">{{ __('hiko.death_year') }} <span class="lowercase">{{ __('hiko.tolerance') }}</span></span>
                                    <select wire:model.blur="candidateDeathYearTolerance" class="w-full px-2 py-1 border rounded text-sm">
                                        @foreach($candidateYearToleranceOptions as $option)
                                            <option value="{{ $option }}">{{ (int)$option === 0 ? __('hiko.exact') : '±' . $option }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                        @endif
                    </div>
                </label>
            </div>

            <div class="mt-6 flex justify-center">
                <button wire:click="scanCandidates" wire:loading.attr="disabled" wire:target="scanCandidates" class="px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 disabled:opacity-25 disabled:cursor-not-allowed transition ease-in-out duration-150 flex items-center gap-2">
                    <svg wire:loading wire:target="scanCandidates" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ __('hiko.search_for_duplicates') }}</span>
                </button>
            </div>
        @else
            <div class="mb-4 flex items-center justify-between">
                <button wire:click="resetCandidateScan" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
                    ← {{ __('hiko.change_criteria') }}
                </button>
                <div class="flex items-center gap-3 text-sm text-gray-600">
                    <span>{{ __('hiko.candidate_groups_page', ['page' => $candidatePage]) }}</span>
                    <span>{{ __('hiko.groups_found', ['count' => count($candidateGroups)]) }}</span>
                </div>
            </div>

            @if(empty($candidateGroups))
                <div class="p-8 text-center rounded border border-dashed border-gray-300">
                    {{ __('hiko.no_duplicates_found') }}
                </div>
            @else
                <div class="flex flex-col gap-6">
                    @foreach($candidateGroups as $index => $group)
                        <div wire:key="strict-global-candidate-group-{{ $index }}" class="overflow-hidden border border-gray-200 rounded-lg">
                            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex flex-wrap gap-3 justify-between items-center">
                                <h3 class="font-bold text-gray-700">
                                    {{ __('hiko.group') }} #{{ $index + 1 }}
                                    <span class="ml-2 text-xs font-normal text-gray-500">{{ __('hiko.reason') }}: {{ $group['reason'] }}</span>
                                </h3>
                                <button type="button" wire:click="previewCandidateGroup(@js($group['ids']))" class="px-3 py-2 bg-primary text-white rounded text-sm hover:bg-black transition">
                                    {{ __('hiko.preview_merge') }}
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr class="text-xs text-gray-500 uppercase">
                                            <th class="px-3 py-2 text-left">ID</th>
                                            <th class="px-3 py-2 text-left">{{ __('hiko.name') }}</th>
                                            <th class="px-3 py-2 text-left">{{ __('hiko.type') }}</th>
                                            <th class="px-3 py-2 text-left">{{ __('hiko.dates') }}</th>
                                            <th class="px-3 py-2 text-left">{{ __('hiko.nationality') }}</th>
                                            <th class="px-3 py-2 text-left">{{ __('hiko.gender') }}</th>
                                            <th class="px-3 py-2 text-left">{{ __('hiko.admin_notes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($group['items'] as $item)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-3 align-top text-sm font-mono">
                                                    <a href="{{ route('global.identities.edit', $item['id']) }}" target="_blank" class="text-primary hover:underline">{{ $item['id'] }}</a>
                                                </td>
                                                <td class="px-3 py-3 align-top text-sm font-medium text-gray-900">{{ $item['name'] }}</td>
                                                <td class="px-3 py-3 align-top text-sm text-gray-700">{{ __("hiko.{$item['type']}") }}</td>
                                                <td class="px-3 py-3 align-top text-sm text-gray-700">{{ trim(($item['birth_year'] ?? '') . ' - ' . ($item['death_year'] ?? ''), ' -') ?: '—' }}</td>
                                                <td class="px-3 py-3 align-top text-sm text-gray-700">{{ $item['nationality'] ?: '—' }}</td>
                                                <td class="px-3 py-3 align-top text-sm text-gray-700">{{ $item['gender'] ?: '—' }}</td>
                                                <td class="px-3 py-3 align-top text-sm text-gray-700">
                                                    @php($adminReferences = $this->adminNoteReferences($item['admin_notes'] ?? null))
                                                    @if($adminReferences)
                                                        <ul class="list-disc list-outside ml-5 text-gray-600 space-y-1">
                                                            @foreach($adminReferences as $reference)
                                                                <li>
                                                                    <button type="button" wire:click="showLocalIdentityPreview('{{ $reference['reference'] }}')" class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">
                                                                        {{ $reference['reference'] }}
                                                                    </button>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <button type="button" wire:click="previousCandidatePage" @disabled(!$candidateHasPreviousPage)
                        class="px-3 py-2 text-sm font-medium bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        ← {{ __('hiko.previous') }}
                    </button>

                    <div class="text-sm text-gray-600">{{ __('hiko.candidate_groups_page', ['page' => $candidatePage]) }}</div>

                    <button type="button" wire:click="nextCandidatePage" @disabled(!$candidateHasNextPage)
                        class="px-3 py-2 text-sm font-medium bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('hiko.next') }} →
                    </button>
                </div>
            @endif
        @endif
    </div>

    @if(!$candidateScanComplete)
        <div class="mb-6 p-4 bg-gray-100 rounded-md border border-gray-200 flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">ID</label>
                <input type="text" wire:model.live.debounce.500ms="filters.ids" class="text-sm w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="{{ __('hiko.ids_filter_placeholder_ex') }}">
            </div>

            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">{{ __('hiko.name') }}</label>
                <input type="text" wire:model.live.debounce.500ms="filters.name" class="text-sm w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="{{ __('hiko.search') }}...">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">{{ __('hiko.type') }}</label>
                <select wire:model.live="filters.type" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 w-44">
                    <option value="all">{{ __('hiko.all') }}</option>
                    <option value="person">{{ __('hiko.person') }}</option>
                    <option value="institution">{{ __('hiko.institution') }}</option>
                </select>
            </div>

            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">{{ __('hiko.admin_notes') }}</label>
                <input type="text" wire:model.live.debounce.500ms="filters.admin_notes" class="text-sm w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="{{ __('hiko.search') }}...">
            </div>

            <button type="button" wire:click="resetFilters" class="px-4 py-2 text-sm font-medium bg-white border border-gray-300 rounded hover:bg-gray-50">
                {{ __('hiko.reset_filters') }}
            </button>
        </div>

        <div class="mb-6 bg-white shadow overflow-hidden border border-gray-200 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10"></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.type') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.dates') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.nationality') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.gender') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.related_names') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.professions') }} | {{ __('hiko.attached_category') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.admin_notes') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($identities as $identity)
                        <tr wire:key="strict-global-identity-{{ $identity->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <label class="inline-flex min-h-10 min-w-10 cursor-pointer items-center justify-center rounded hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="selectedIds" value="{{ $identity->id }}" class="h-5 w-5 rounded border-gray-300 text-primary focus:ring-primary">
                                </label>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-mono">
                                <a href="{{ route('global.identities.edit', $identity->id) }}" target="_blank" class="text-primary hover:underline">{{ $identity->id }}</a>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ $identity->name }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ __("hiko.{$identity->type}") }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ trim("{$identity->birth_year} - {$identity->death_year}") }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $identity->nationality ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $identity->gender ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{!! $this->formatRelatedNamesList($identity->related_names) !!}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{!! $this->formatProfessionsList($identity) !!}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                @php($adminReferences = $this->adminNoteReferences($identity->admin_notes))
                                @if($adminReferences)
                                    <ul class="list-disc list-outside ml-5 text-gray-600 space-y-1">
                                        @foreach($adminReferences as $reference)
                                            <li>
                                                <button type="button"
                                                    wire:click="showLocalIdentityPreview('{{ $reference['reference'] }}')"
                                                    class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">
                                                    {{ $reference['reference'] }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    {!! $this->formatAdminNotes($identity->admin_notes) !!}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-8 text-center text-gray-500">{{ __('hiko.no_results') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mb-6 flex justify-between items-center">
            <div class="text-sm text-gray-600">{{ __('hiko.selected_count', ['count' => count($selectedIds)]) }}</div>
            <button type="button" wire:click="preview" @disabled(count($selectedIds) < 2)
                class="px-4 py-2 bg-primary text-white rounded hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed">
                {{ __('hiko.preview_merge') }}
            </button>
        </div>

        <div class="mt-12">{{ $identities->links() }}</div>
    @endif

    @if($localIdentityPreview)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900 bg-opacity-60 p-4">
            <div class="w-full max-w-3xl rounded-lg bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('hiko.local_identity_preview') }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $localIdentityPreview['reference'] }}</p>
                    </div>
                    <button type="button" wire:click="closeLocalIdentityPreview" class="p-2 text-gray-400 hover:text-gray-700" aria-label="{{ __('hiko.close') }}">✕</button>
                </div>

                <div class="grid grid-cols-1 gap-x-6 gap-y-4 px-6 py-5 sm:grid-cols-2">
                    @foreach([
                        __('hiko.name') => $localIdentityPreview['name'] ?? null,
                        __('hiko.type') => isset($localIdentityPreview['type']) ? __("hiko.{$localIdentityPreview['type']}") : null,
                        __('hiko.surname') => $localIdentityPreview['surname'] ?? null,
                        __('hiko.forename') => $localIdentityPreview['forename'] ?? null,
                        __('hiko.general_name_modifier') => $localIdentityPreview['general_name_modifier'] ?? null,
                        __('hiko.dates') => trim(($localIdentityPreview['birth_year'] ?? '') . ' – ' . ($localIdentityPreview['death_year'] ?? ''), ' –'),
                        __('hiko.nationality') => $localIdentityPreview['nationality'] ?? null,
                        __('hiko.gender') => $localIdentityPreview['gender'] ?? null,
                        'VIAF' => $localIdentityPreview['viaf_id'] ?? null,
                    ] as $label => $value)
                        <div>
                            <div class="text-xs font-bold uppercase text-gray-500">{{ $label }}</div>
                            <div class="mt-1 text-sm text-gray-900">{{ filled($value) ? $value : '—' }}</div>
                        </div>
                    @endforeach

                    <div class="sm:col-span-2">
                        <div class="text-xs font-bold uppercase text-gray-500">{{ __('hiko.related_names') }}</div>
                        <div class="mt-1 text-sm text-gray-900">{!! $this->formatRelatedNamesList($localIdentityPreview['related_names'] ?? []) !!}</div>
                    </div>

                    <div class="sm:col-span-2">
                        <div class="text-xs font-bold uppercase text-gray-500">{{ __('hiko.alternative_names') }}</div>
                        <div class="mt-1 text-sm text-gray-900">
                            {{ collect($localIdentityPreview['alternative_names'] ?? [])->map(fn($value) => is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))->filter()->implode(', ') ?: '—' }}
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <div class="text-xs font-bold uppercase text-gray-500">{{ __('hiko.related_identity_resources') }}</div>
                        <div class="mt-1 space-y-1 text-sm text-gray-900">
                            @forelse($localIdentityPreview['related_identity_resources'] ?? [] as $resource)
                                <div>
                                    @php($resourceLink = is_array($resource) ? trim((string)($resource['link'] ?? '')) : '')
                                    @if($resourceLink !== '' && (str_starts_with($resourceLink, 'https://') || str_starts_with($resourceLink, 'http://')))
                                        <a href="{{ $resource['link'] }}" target="_blank" rel="noopener" class="text-primary hover:underline">
                                            {{ $resource['title'] ?? $resource['link'] }}
                                        </a>
                                    @else
                                        {{ is_array($resource) ? json_encode($resource, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $resource }}
                                    @endif
                                </div>
                            @empty
                                —
                            @endforelse
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <div class="text-xs font-bold uppercase text-gray-500">{{ __('hiko.note') }}</div>
                        <div class="mt-1 whitespace-pre-wrap text-sm text-gray-900">{{ $localIdentityPreview['note'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <button type="button" wire:click="closeLocalIdentityPreview" class="rounded border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">
                        {{ __('hiko.close') }}
                    </button>
                    <a href="{{ $localIdentityPreview['edit_url'] }}" target="_blank" rel="noopener"
                        class="rounded bg-primary px-4 py-2 text-sm text-white hover:bg-black">
                        {{ __('hiko.open_local_identity') }}
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
