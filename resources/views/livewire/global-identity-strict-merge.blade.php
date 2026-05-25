<div>
    <div class="mb-6 p-4 bg-gray-100 rounded-md border border-gray-200 flex flex-wrap gap-4 items-end">
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

    <div class="mb-4 flex justify-between items-center">
        <div class="text-sm text-gray-600">{{ __('hiko.selected_count', ['count' => count($selectedIds)]) }}</div>
        <button type="button" wire:click="preview" @disabled(count($selectedIds) < 2)
            class="px-4 py-2 bg-primary text-white rounded hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed">
            {{ __('hiko.preview_merge') }}
        </button>
    </div>

    <div class="bg-white shadow overflow-hidden border border-gray-200 sm:rounded-lg">
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
                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-pre-wrap">{!! $this->formatAdminNotes($identity->admin_notes) !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-8 text-center text-gray-500">{{ __('hiko.no_results') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $identities->links() }}</div>
</div>
