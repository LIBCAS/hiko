@if (session()->has('success'))
    <div x-data="{ show: true }" x-show="show" style="display:block"
        class="max-w-sm px-6 py-3 mb-4 text-white bg-green-700 rounded-md" x-init="(() => {
            window.scrollTo(0, 0);
            setTimeout(() => {show = false}, 2000);
        })">
        {{ session('success') }}
    </div>
@endif
