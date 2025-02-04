<div>
    <a href="{{ $link }}" {!! $attributes->merge(['class' => 'flex items-center font-bold focus:border-black px-6 py-4 mb-4 rounded-full bg-primary hover:bg-black text-white hover:text-white']) !!}>
        <x-icons.plus class="h-5 mr-1" /> {{ $label }}
    </a>
</div>
