<button {{ $attributes->merge(['type' => 'submit', 'class' => 'px-4 py-2 border rounded-md font-semibold text-xs text-red-700 uppercase tracking-widest disabled:opacity-25 transition ease-in-out duration-150 w-full border-red-700 hover:bg-red-700 hover:text-white']) }}>
    {{ $slot }}
</button>
