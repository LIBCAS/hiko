@props(['disabled' => false])

<select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'rounded-md shadow-sm border-gray-300 w-full px-2 disabled:bg-gray-100']) !!}>
    {{ $slot }}
</select>
