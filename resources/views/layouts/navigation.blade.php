<nav x-data="{ open: false }" class="container mx-auto">
    <div class="flex flex-wrap justify-between py-3">
        <div class="flex items-center">
            <a href="{{ route('letters') }}" class="font-semibold">
                {{ config('app.name') }}
            </a>
        </div>
        <div class="hidden sm:flex sm:items-center sm:ml-6">
            <div class="relative" x-data="{ open: false }" @click.away="open = false" @close.stop="open = false">
                <button @click="open = ! open" class="flex items-center text-sm">
                    {{ Auth::user()->name }}
                    <div class="ml-1">
                        <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 z-50 mt-2 origin-top-right shadow-lg w-44" style="display: none;"
                    @click="open = false">
                    <div class="py-1 bg-white rounded-md ring-1 ring-black ring-opacity-5">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="w-full px-2 py-1 text-sm text-left text-gray-700 hover:bg-gray-100">
                                {{ __('Odhlásit se') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <button @click="open = ! open"
            class="p-2 text-gray-500 rounded-md sm:hidden hover:text-gray-700 hover:bg-gray-100 focus:text-gray-500">
            <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round"
                    stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
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
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
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
        </div>
        <div class="pt-4 pb-1 border-t border-gray-200">
            <span> {{ Auth::user()->name }} </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full py-1 text-sm text-left">
                    {{ __('Odhlásit se') }}
                </button>
            </form>
        </div>
    </div>
</nav>
