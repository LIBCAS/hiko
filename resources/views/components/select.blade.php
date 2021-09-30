@props(['disabled' => false])

<select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'rounded-md shadow-sm border-gray-300 focus:border-primary focus:ring-primary-light focus:ring-opacity-50 w-full px-2']) !!}>
    {{ $slot }}
</select>
