<x-app-layout title="{{ __('hiko.merge_letters') }}">
    <div class="max-w-7xl mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">{{ __('hiko.merge_letters') }}</h1>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('letters.merge') }}" method="post" id="mergeForm">
            @csrf
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-center">{{ __('hiko.primary') }}</th>
                            <th class="px-4 py-2 text-center">{{ __('hiko.merge') }}</th>
                            <th class="px-4 py-2">{{ __('hiko.id') }}</th>
                            <th class="px-4 py-2">{{ __('hiko.name_2') }}</th>
                            <th class="px-4 py-2">{{ __('hiko.date') }}</th>
                            <th class="px-4 py-2">{{ __('hiko.signature') }}</th>
                            <th class="px-4 py-2">{{ __('hiko.author') }}</th>
                            <th class="px-4 py-2">{{ __('hiko.recipient') }}</th>
                            <th class="px-4 py-2">{{ __('hiko.status') }}</th>
                            <th class="px-4 py-2">{{ __('hiko.approval') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($letters as $letter)
                            <tr>
                                <td class="px-4 py-2 text-center">
                                    <input type="radio" name="primary_id" value="{{ $letter->id }}" class="h-4 w-4 text-indigo-600">
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input type="radio" name="secondary_id" value="{{ $letter->id }}" class="h-4 w-4 text-indigo-600">
                                </td>
                                <td class="px-4 py-2">{{ $letter->id }}</td>
                                <td class="px-4 py-2">{{ $letter->name }}</td>
                                <td class="px-4 py-2">{{ $letter->pretty_date }}</td>
                                <td class="px-4 py-2">
                                    @if(is_array($letter->copies) && count($letter->copies))
                                        {{ implode(', ', array_column($letter->copies, 'signature')) }}
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    @php
                                        $authors = $letter->identities->where('pivot.role', 'author')->pluck('name')->toArray();
                                    @endphp
                                    {{ implode(', ', $authors) }}
                                </td>
                                <td class="px-4 py-2">
                                    @php
                                        $recipients = $letter->identities->where('pivot.role', 'recipient')->pluck('name')->toArray();
                                    @endphp
                                    {{ implode(', ', $recipients) }}
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $letter->status === 'publish' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($letter->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    @if($letter->approval === \App\Models\Letter::APPROVED)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            {{ __('hiko.approved') }}
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            {{ __('hiko.not_approved') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination links -->
            <div class="w-full pl-1 mt-3">
                {{ $pagination->links() }}
            </div>

            <div class="mt-6">
                <x-button-simple>{{ __('hiko.merge') }}</x-button-simple>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.getElementById('mergeForm').addEventListener('submit', function(e) {
                const primary = document.querySelector('input[name="primary_id"]:checked');
                const secondary = document.querySelector('input[name="secondary_id"]:checked');

                if (!primary || !secondary) {
                    e.preventDefault();
                    alert("{{ __('hiko.merge_field_required') }}");
                    return;
                }

                if (primary.value === secondary.value) {
                    e.preventDefault();
                    alert("{{ __('hiko.merge_same_not_allowed') }}");
                    return;
                }

                if (!confirm("{{ __('hiko.confirm_merge') }}")) {
                    e.preventDefault();
                    return;
                }
            });
        </script>
    @endpush
</x-app-layout>
