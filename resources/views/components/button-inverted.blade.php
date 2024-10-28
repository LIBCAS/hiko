<button {{ $attributes->merge(['type' => 'submit', 'class' => 'px-4 py-2 bg-white border rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-opacity-75 active:bg-gray-200 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
