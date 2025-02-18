@props(['disabled' => false])

<select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'rounded-md shadow-sm border-gray-300 w-full disabled:bg-gray-100']) !!}>
    {{ $slot }}
</select>
