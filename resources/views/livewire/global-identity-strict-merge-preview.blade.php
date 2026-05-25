@php
    $service = app(\App\Services\GlobalIdentityStrictMergeService::class);
@endphp

<div>
    @if($records->count() < 2)
        <div class="bg-white border border-gray-200 rounded-md p-6 text-sm text-gray-600">
            {{ __('hiko.strict_global_merge_select_at_least_two') }}
        </div>
    @elseif($typeError)
        <div class="bg-white border border-red-200 rounded-md p-6 text-sm text-red-700">
            {{ $typeError }}
        </div>
    @else
        <p class="mb-3 text-sm text-gray-600">
            {{ __('hiko.strict_global_merge_table_a_help') }}
        </p>
        <div class="mb-6 bg-white shadow overflow-hidden border border-gray-200 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.surviving_record') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.type') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.dates') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.admin_notes') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($records as $record)
                        <tr>
                            <td class="px-4 py-3 cursor-pointer" wire:click="$set('survivorId', {{ $record->id }})">
                                <label class="inline-flex min-h-10 min-w-10 cursor-pointer items-center justify-center rounded hover:bg-gray-100">
                                    <input type="radio" wire:model.live="survivorId" value="{{ $record->id }}" class="h-5 w-5 border-gray-300 text-primary focus:ring-primary">
                                </label>
                            </td>
                            <td class="px-4 py-3 text-sm font-mono">
                                <a href="{{ route('global.identities.edit', $record->id) }}" target="_blank" class="text-primary hover:underline">{{ $record->id }}</a>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $record->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ __("hiko.{$record->type}") }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ trim("{$record->birth_year} - {$record->death_year}") }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 whitespace-pre-wrap">{!! $service->formatAdminNotes($record->admin_notes) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mb-3 text-sm text-gray-600">
            {{ __('hiko.strict_global_merge_table_b_help') }}
        </p>
        <div class="bg-white shadow overflow-hidden border border-gray-200 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-56">{{ __('hiko.field') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.choose_values') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('hiko.final_values') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($fields as $field)
                        @php
                            $isScalar = in_array($field, \App\Services\GlobalIdentityStrictMergeService::SCALAR_FIELDS, true);
                            $options = $isScalar
                                ? $service->scalarOptions($records, $field)
                                : $service->multiOptions($records, $field);
                        @endphp
                        <tr>
                            <td class="px-4 py-3 align-top text-sm font-medium text-gray-900">{{ __("hiko.{$field}") }}</td>
                            <td class="px-4 py-3 align-top">
                                <table class="min-w-full divide-y divide-gray-100 border border-gray-200 rounded">
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($options as $option)
                                            <tr>
                                                <td class="px-2 py-2 align-top w-12">
                                                    @if($isScalar)
                                                        <label class="inline-flex min-h-10 min-w-10 cursor-pointer items-center justify-center rounded hover:bg-gray-100">
                                                            <input type="radio" wire:model.live="scalarSelections.{{ $field }}" value="{{ $option['key'] }}" class="h-5 w-5 border-gray-300 text-primary focus:ring-primary">
                                                        </label>
                                                    @else
                                                        <label class="inline-flex min-h-10 min-w-10 cursor-pointer items-center justify-center rounded hover:bg-gray-100">
                                                            <input type="checkbox" wire:model.live="multiSelections.{{ $field }}" value="{{ $option['key'] }}" class="h-5 w-5 rounded border-gray-300 text-primary focus:ring-primary">
                                                        </label>
                                                    @endif
                                                </td>
                                                <td class="px-2 py-2 align-top text-sm whitespace-nowrap">
                                                    {!! $service->formatIds($option['ids']) !!}
                                                </td>
                                                <td class="px-2 py-2 align-top text-sm text-gray-700">
                                                    {!! $option['html'] ?? e($option['label']) !!}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="px-2 py-2 text-sm text-gray-500">—</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-gray-700 whitespace-pre-wrap break-words">
                                {!! $service->finalPreviewValue($records, $field, $scalarSelections, $multiSelections) !!}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="button" wire:click="execute" wire:loading.attr="disabled" @disabled($survivorId === 0)
                class="px-4 py-2 bg-primary text-white rounded hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                <svg wire:loading wire:target="execute" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ __('hiko.execute_merge') }}
            </button>
        </div>
    @endif
</div>
