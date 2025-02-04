<label class="inline-flex items-center">
    <input type="radio" {{ $attributes->merge(['class' => 'border-gray-300 shadow-sm text-primary focus:border-primary-light focus:ring focus:ring-primary-light focus:ring-opacity-50']) }}>
    @if(isset($label))
        <span class="ml-2 text-sm text-gray-600">{{ $label }}</span>
    @endif
</label>
