@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'rounded-md shadow-sm border-gray-300 focus:border-primary focus:ring-1 focus:ring-primary disabled:bg-gray-100']) !!}>
