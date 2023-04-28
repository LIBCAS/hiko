<nav x-data="{ open: false }" class="container mx-auto">
    <div class="flex flex-wrap justify-between py-3">
        <div class="flex items-center">
            <a href="{{ route('letters') }}" class="font-semibold">
                {{ config('app.name') }}
            </a>
        </div>
        <div class="hidden space-x-6 sm:flex sm:items-center sm:ml-6">
            <x-dropdown label="" icon="icons.translate" :alignRight="true">
                <div class="py-1 bg-white ring-1 ring-black ring-opacity-5">
                    <a href="{{ route('lang', 'cs') }}"
                        class="block w-full px-2 py-1 text-sm text-left text-gray-700 hover:bg-gray-100">
                        CS
                    </a>
                </div>
                <div class="py-1 bg-white ring-1 ring-black ring-opacity-5">
                    <a href="{{ route('lang', 'en') }}"
                        class="block w-full px-2 py-1 text-sm text-left text-gray-700 hover:bg-gray-100">
                        EN
                    </a>
                </div>
            </x-dropdown>
            <x-dropdown label="{{ Auth::user()->name }}" :alignRight="true">
                <div class="py-1 bg-white ring-1 ring-black ring-opacity-5">
                    <a href="{{ route('account') }}"
                        class="block w-full px-2 py-1 text-sm text-left text-gray-700 hover:bg-gray-100">
                        {{ __('hiko.settings') }}
                    </a>
                </div>
                <div class="py-1 bg-white ring-1 ring-black ring-opacity-5">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="w-full px-2 py-1 text-sm text-left text-gray-700 hover:bg-gray-100">
                            {{ __('hiko.logout') }}
                        </button>
                    </form>
                </div>
            </x-dropdown>
        </div>
        <button @click="open = ! open"
            class="p-2 text-gray-500 rounded-md sm:hidden hover:text-gray-700 hover:bg-gray-100 focus:text-gray-500">
            <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round"
                    stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                    stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <div class="hidden space-x-4 space-y-1 sm:block">
        @foreach ($menuItems as $item)
            @can($item['ability'])
                <a href="{{ route($item['route']) }}"
                    class="inline-flex items-center text-sm border-b-2 focus:border-primary-dark hover:border-primary pb-1 px-1 @if ($item['active']) border-primary-light @else
                    border-transparent @endif">
                    <x-dynamic-component :component="$item['icon']" class="w-4 h-4 mr-2" />
                    <span>
                        {{ $item['name'] }}
                    </span>
                </a>
            @endcan
        @endforeach
    </div>
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @foreach ($menuItems as $item)
                @can($item['ability'])
                    <a href="{{ route($item['route']) }}"
                        class="block py-2 pl-3 pr-4 border-l-4  hover:bg-primary hover:bg-opacity-10 @if ($item['active']) border-primary-light bg-primary bg-opacity-5
                @else border-transparent @endif">
                        {{ $item['name'] }}
                    </a>
                @endcan
            @endforeach
            <a href="{{ route('account') }}"
                class="block py-2 pl-3 pr-4 border-l-4 hover:bg-primary hover:bg-opacity-10 @if (request()->routeIs('account')) border-primary-light bg-primary bg-opacity-5 @else border-transparent @endif ">
                {{ __('hiko.settings') }}
            </a>
        </div>
        <div class="pt-4 pb-1 border-t border-gray-200">
            <span> {{ Auth::user()->name }} </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full py-1 text-sm text-left">
                    {{ __('hiko.logout') }}
                </button>
            </form>
        </div>
    </div>
</nav>
