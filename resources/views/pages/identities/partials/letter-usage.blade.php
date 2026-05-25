@php
    $letters = ($letters ?? collect())->sortBy('id')->values();
    $roles = [
        'author' => __('hiko.identity_letters_as_author'),
        'recipient' => __('hiko.identity_letters_as_recipient'),
        'mentioned' => __('hiko.identity_letters_as_mentioned'),
    ];
@endphp

<div class="bg-white p-6 shadow rounded-md">
    @if ($letters->count())
        <h2 class="text-l font-semibold">{{ __('hiko.letters') }}: {{ $letters->unique('id')->count() }}</h2>

        @foreach ($roles as $role => $label)
            @php
                $roleLetters = $letters
                    ->filter(fn($letter) => $letter->pivot?->role === $role)
                    ->unique('id')
                    ->values();
            @endphp

            <div class="{{ $loop->first ? 'mt-4' : 'mt-6' }}">
                <h3 class="text-sm font-semibold">{{ $label }}: {{ $roleLetters->count() }}</h3>

                @if ($roleLetters->count())
                    <ul class="list-disc px-3 py-3">
                        @foreach ($roleLetters as $letter)
                            <li class="text-sm">
                                {{ $letter->id }} - <a href="{{ route('letters.edit', $letter->id) }}"
                                    class="border-b text-primary-dark border-primary-light hover:border-primary-dark">{{ $letter->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    @else
        <h2 class="text-l font-semibold">{{ __('hiko.no_attached_letters') }}</h2>
    @endif
</div>
