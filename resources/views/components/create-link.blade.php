<div class="relative inline-block">
    <a href="{{ $link }}" {!! $attributes->merge(['class' => 'flex items-center text-white px-6 py-3 text-sm font-semibold border border-black rounded-full bg-black hover:text-white hover:bg-primary active:bg-primary focus:text-white transition ease-in-out duration-150']) !!}>
        <x-icons.plus class="h-5 mr-1" /> {{ $label }}
    </a>
</div>
