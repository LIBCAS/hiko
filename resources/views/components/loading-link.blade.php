@props(['href'])

<a
    href="{{ $href }}"
    x-data="{ loading: false, resetTimer: null }"
    x-on:click="
        if (loading) {
            $event.preventDefault();
            return;
        }
        loading = true;
        clearTimeout(resetTimer);
        resetTimer = setTimeout(() => loading = false, 8000);
    "
    x-bind:aria-disabled="loading.toString()"
    x-bind:class="{ 'opacity-60 pointer-events-none': loading }"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-sm font-semibold']) }}>
    <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <span>{{ $slot }}</span>
</a>
