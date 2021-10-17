@props(['disabled' => false])

<textarea x-data="{ resize: () => { $el.style.height = '120px'; $el.style.height = $el.scrollHeight + 'px' } }"
    x-init="resize()" @input="resize()" {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->merge(['class' => 'rounded-md shadow-sm border-gray-300 focus:border-primary']) !!}>{{ $slot }}</textarea>
