@props(['disabled' => false])

<textarea x-data="{ resize: () => { $el.style.height = '84px'; $el.style.height = $el.scrollHeight + 'px' } }"
    x-init="resize()" @input="resize()" {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->merge(['class' => 'rounded-md shadow-sm border-gray-300 focus:border-primary focus:ring-1 focus:ring-primary disabled:bg-gray-100']) !!}>{{ $slot }}</textarea>
