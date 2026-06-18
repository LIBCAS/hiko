@if ($requests->isEmpty())
    <p class="text-sm text-gray-600">{{ __('hiko.no_transfer_requests') }}</p>
@else
    <div class="overflow-x-auto border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left">{{ __('hiko.id') }}</th>
                    <th class="px-3 py-2 text-left">{{ $direction === 'incoming' ? __('hiko.source_tenant') : __('hiko.target_tenant') }}</th>
                    <th class="px-3 py-2 text-left">{{ __('hiko.requested_by') }}</th>
                    <th class="px-3 py-2 text-left">{{ __('hiko.entity_type') }}</th>
                    <th class="px-3 py-2 text-left">{{ __('hiko.total') }}</th>
                    <th class="px-3 py-2 text-left">{{ __('hiko.status') }}</th>
                    <th class="px-3 py-2 text-left">{{ __('hiko.created_at') }}</th>
                    <th class="px-3 py-2 text-left">{{ __('hiko.decided_by') }}</th>
                    <th class="px-3 py-2 text-left">{{ __('hiko.decided_at') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @foreach ($requests as $request)
                    <tr>
                        <td class="px-3 py-2">
                            <a class="font-semibold text-primary-dark hover:underline"
                                href="{{ route('inter-tenant-transfers.show', $request) }}">#{{ $request->id }}</a>
                        </td>
                        <td class="px-3 py-2">
                            {{ ($direction === 'incoming' ? $request->sourceTenant : $request->targetTenant)->displayName() }}
                        </td>
                        <td class="px-3 py-2">{{ $request->requested_by_name }}</td>
                        <td class="px-3 py-2">{{ __('hiko.letters') }}</td>
                        <td class="px-3 py-2">{{ count($request->source_record_ids) }}</td>
                        <td class="px-3 py-2">{{ __('hiko.transfer_status_' . $request->status) }}</td>
                        <td class="px-3 py-2">{{ $request->created_at }}</td>
                        <td class="px-3 py-2">{{ $request->decided_by_name ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $request->decided_at ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
