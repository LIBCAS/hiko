<label class="inline-flex items-center">
    <input type="checkbox" @if ($checked) checked @endif
        class="border-gray-300 rounded shadow-sm text-primary focus:border-primary-light focus:ring focus:ring-primary-light focus:ring-opacity-50"
        name="{{ $name }}">
    <span class="ml-2 text-sm text-gray-600">{{ $label }}</span>
</label>
