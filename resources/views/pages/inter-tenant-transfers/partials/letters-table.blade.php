@php
    $identities = $payload['dependencies']['identities']->keyBy('id');
    $globalIdentities = $payload['global_dependencies']['identities']->keyBy('id');
    $identityRows = $payload['identity_rows']->groupBy('letter_id');
@endphp
<div class="overflow-x-auto border border-gray-200">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-3 py-2 text-left">{{ __('hiko.id') }}</th>
                <th class="px-3 py-2 text-left">{{ __('hiko.date') }}</th>
                <th class="px-3 py-2 text-left">{{ __('hiko.author') }}</th>
                <th class="px-3 py-2 text-left">{{ __('hiko.recipient') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @foreach ($payload['letters'] as $letter)
                @php
                    $rows = $identityRows->get($letter->id, collect());
                    $names = fn ($role) => $rows->where('role', $role)
                        ->map(fn ($row) => $row->identity_id
                            ? optional($identities->get($row->identity_id))->name
                            : optional($globalIdentities->get($row->global_identity_id))->name)
                        ->filter()->implode('; ');
                    $sourceUrl = $sourceDomain
                        ? request()->getScheme() . '://' . $sourceDomain . route('letters.show', $letter->id, false)
                        : null;
                @endphp
                <tr>
                    <td class="px-3 py-2">
                        @if ($sourceUrl)
                            <a href="{{ $sourceUrl }}" target="_blank" class="font-semibold text-primary-dark hover:underline">#{{ $letter->id }}</a>
                        @else
                            #{{ $letter->id }}
                        @endif
                    </td>
                    <td class="px-3 py-2">
                        {{ implode('-', array_filter([$letter->date_year, $letter->date_month, $letter->date_day])) ?: '—' }}
                    </td>
                    <td class="px-3 py-2">{{ $names('author') ?: '—' }}</td>
                    <td class="px-3 py-2">{{ $names('recipient') ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
