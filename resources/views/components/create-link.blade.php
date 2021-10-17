<div>
    <a href="{{ $link }}" {!! $attributes->merge(['class' => 'inline-flex items-center font-bold text-primary hover:underline']) !!}>
        <x-heroicon-o-plus class="h-5 mr-1" /> {{ $label }}
    </a>
</div>
