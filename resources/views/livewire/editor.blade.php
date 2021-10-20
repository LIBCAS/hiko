<div>
    <div x-data="editor(`{{ $letter->content }}`)" class="max-w-md">
        <div class="p-1 prose bg-white border rounded border-primary">
            <div class="sticky top-0 z-20 flex flex-wrap justify-between bg-white border-b">
                <div class="flex flex-wrap space-x-1">
                    <button x-on:click="toggleBold()" type="button" aria-label="{{ __('Tučně') }}"
                        title="{{ __('Tučně') }}" class="text-gray-600 hover:bg-gray-100"
                        :class="{ 'text-purple-500' : isActive('bold', updatedAt) }">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M17.061 11.22A4.46 4.46 0 0 0 18 8.5C18 6.019 15.981 4 13.5 4H6v15h8c2.481 0 4.5-2.019 4.5-4.5a4.48 4.48 0 0 0-1.439-3.28zM13.5 7c.827 0 1.5.673 1.5 1.5s-.673 1.5-1.5 1.5H9V7h4.5zm.5 9H9v-3h5c.827 0 1.5.673 1.5 1.5S14.827 16 14 16z">
                            </path>
                        </svg>
                    </button>
                    <button x-on:click="toggleItalic()" type="button" aria-label="{{ __('Kurzíva') }}"
                        title="{{ __('Kurzíva') }}" class="text-gray-600 hover:bg-gray-100"
                        :class="{ 'text-purple-500' :  isActive('italic', updatedAt) }">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path d="M19 7V4H9v3h2.868L9.012 17H5v3h10v-3h-2.868l2.856-10z"></path>
                        </svg>
                    </button>
                    <button x-on:click="toggleStrike()" type="button" aria-label="{{ __('Přeškrtnutí') }}"
                        title="{{ __('Přeškrtnutí') }}" class="text-gray-600 hover:bg-gray-100"
                        :class="{ 'text-purple-500' :  isActive('strike', updatedAt) }">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M20 11h-8c-4 0-4-1.816-4-2.5C8 7.882 8 6 12 6c2.8 0 2.99 1.678 3 2.014L16 8h1c0-1.384-1.045-4-5-4-5.416 0-6 3.147-6 4.5 0 .728.148 1.667.736 2.5H4v2h16v-2zm-8 7c-3.793 0-3.99-1.815-4-2H6c0 .04.069 4 6 4 5.221 0 6-2.819 6-4.5 0-.146-.009-.317-.028-.5h-2.006c.032.2.034.376.034.5 0 .684 0 2.5-4 2.5z">
                            </path>
                        </svg>
                    </button>
                    <button x-on:click="toggleUnderline()" type="button" aria-label="{{ __('Podtržení') }}"
                        title="{{ __('Podtržení') }}" class="text-gray-600 hover:bg-gray-100"
                        :class="{ 'text-purple-500' :  isActive('underline', updatedAt) }">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M5 18h14v2H5zM6 4v6c0 3.309 2.691 6 6 6s6-2.691 6-6V4h-2v6c0 2.206-1.794 4-4 4s-4-1.794-4-4V4H6z">
                            </path>
                        </svg>
                    </button>
                    <button x-on:click="toggleHeading(1)" type="button" aria-label="{{ __('Nadpis 1') }}"
                        title="{{ __('Nadpis 1') }}" class="inline-flex items-center text-gray-600 hover:bg-gray-100"
                        :class="{ 'text-purple-500' : isActive('heading', { level: 1 }, updatedAt) }">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" style="fill:currentColor">
                            <path d="M18 20V4h-3v6H9V4H6v16h3v-7h6v7z" />
                        </svg>
                        <span class="-ml-1 text-xl font-semibold pr-0.5">1</span>
                    </button>
                    <button x-on:click="toggleHeading(2)" type="button" aria-label="{{ __('Nadpis 2') }}"
                        title="{{ __('Nadpis 2') }}"
                        class="inline-flex items-center text-gray-600 text-gray hover:bg-gray-100"
                        :class="{ 'text-purple-500' : isActive('heading', { level: 2 }, updatedAt) }">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" style="fill:currentColor">
                            <path d="M18 20V4h-3v6H9V4H6v16h3v-7h6v7z" />
                        </svg>
                        <span class="-ml-1 text-xl font-semibold pr-0.5">2</span>
                    </button>
                    <button x-on:click="toggleHeading(3)" type="button" aria-label="{{ __('Nadpis 3') }}"
                        title="{{ __('Nadpis 3') }}"
                        class="inline-flex items-center text-gray-600 text-gray hover:bg-gray-100"
                        :class="{ 'text-purple-500' : isActive('heading', { level: 3 }, updatedAt) }">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" style="fill:currentColor">
                            <path d="M18 20V4h-3v6H9V4H6v16h3v-7h6v7z" />
                        </svg>
                        <span class="-ml-1 text-xl font-semibold pr-0.5">3</span>
                    </button>
                    <button x-on:click="toggleHeading(4)" type="button" aria-label="{{ __('Nadpis 4') }}"
                        title="{{ __('Nadpis 4') }}"
                        class="inline-flex items-center text-gray-600 text-gray hover:bg-gray-100"
                        :class="{ 'text-purple-500' : isActive('heading', { level: 4 }, updatedAt) }">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" style="fill:currentColor">
                            <path d="M18 20V4h-3v6H9V4H6v16h3v-7h6v7z" />
                        </svg>
                        <span class="-ml-1 text-xl font-semibold pr-0.5">4</span>
                    </button>
                    <button x-on:click="toggleBlockquote()" type="button" aria-label="{{ __('Citace') }}"
                        title="{{ __('Citace') }}" class="text-gray-600 hover:bg-gray-100"
                        :class="{ 'text-purple-500' : isActive('blockquote', updatedAt) }">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M6.5 10c-.223 0-.437.034-.65.065.069-.232.14-.468.254-.68.114-.308.292-.575.469-.844.148-.291.409-.488.601-.737.201-.242.475-.403.692-.604.213-.21.492-.315.714-.463.232-.133.434-.28.65-.35l.539-.222.474-.197-.485-1.938-.597.144c-.191.048-.424.104-.689.171-.271.05-.56.187-.882.312-.318.142-.686.238-1.028.466-.344.218-.741.4-1.091.692-.339.301-.748.562-1.05.945-.33.358-.656.734-.909 1.162-.293.408-.492.856-.702 1.299-.19.443-.343.896-.468 1.336-.237.882-.343 1.72-.384 2.437-.034.718-.014 1.315.028 1.747.015.204.043.402.063.539l.025.168.026-.006A4.5 4.5 0 1 0 6.5 10zm11 0c-.223 0-.437.034-.65.065.069-.232.14-.468.254-.68.114-.308.292-.575.469-.844.148-.291.409-.488.601-.737.201-.242.475-.403.692-.604.213-.21.492-.315.714-.463.232-.133.434-.28.65-.35l.539-.222.474-.197-.485-1.938-.597.144c-.191.048-.424.104-.689.171-.271.05-.56.187-.882.312-.317.143-.686.238-1.028.467-.344.218-.741.4-1.091.692-.339.301-.748.562-1.05.944-.33.358-.656.734-.909 1.162-.293.408-.492.856-.702 1.299-.19.443-.343.896-.468 1.336-.237.882-.343 1.72-.384 2.437-.034.718-.014 1.315.028 1.747.015.204.043.402.063.539l.025.168.026-.006A4.5 4.5 0 1 0 17.5 10z">
                            </path>
                        </svg>
                    </button>
                    <button x-on:click="toggleBulletList()" type="button" aria-label="{{ __('Seznam') }}"
                        title="{{ __('Seznam') }}" class="text-gray-600 hover:bg-gray-100"
                        :class="{ 'text-purple-500' :  isActive('bulletList', updatedAt) }">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path d="M4 6h2v2H4zm0 5h2v2H4zm0 5h2v2H4zm16-8V6H8.023v2H18.8zM8 11h12v2H8zm0 5h12v2H8z">
                            </path>
                        </svg>
                    </button>
                    <button x-on:click="toggleOrderedList()" type="button" aria-label="{{ __('Číslovaný seznam') }}"
                        title="{{ __('Číslovaný seznam') }}" class="text-gray-600 hover:bg-gray-100"
                        :class="{ 'text-purple-500' :  isActive('orderedList', updatedAt) }">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M5.282 12.064c-.428.328-.72.609-.875.851-.155.24-.249.498-.279.768h2.679v-.748H5.413c.081-.081.152-.151.212-.201.062-.05.182-.142.361-.27.303-.218.511-.42.626-.604.116-.186.173-.375.173-.578a.898.898 0 0 0-.151-.512.892.892 0 0 0-.412-.341c-.174-.076-.419-.111-.733-.111-.3 0-.537.038-.706.114a.889.889 0 0 0-.396.338c-.094.143-.159.346-.194.604l.894.076c.025-.188.074-.317.147-.394a.375.375 0 0 1 .279-.108c.11 0 .2.035.272.108a.344.344 0 0 1 .108.258.55.55 0 0 1-.108.297c-.074.102-.241.254-.503.453zm.055 6.386a.398.398 0 0 1-.282-.105c-.074-.07-.128-.195-.162-.378L4 18.085c.059.204.142.372.251.506.109.133.248.235.417.306.168.069.399.103.692.103.3 0 .541-.047.725-.14a1 1 0 0 0 .424-.403c.098-.175.146-.354.146-.544a.823.823 0 0 0-.088-.393.708.708 0 0 0-.249-.261 1.015 1.015 0 0 0-.286-.11.943.943 0 0 0 .345-.299.673.673 0 0 0 .113-.383.747.747 0 0 0-.281-.596c-.187-.159-.49-.238-.909-.238-.365 0-.648.072-.847.219-.2.143-.334.353-.404.626l.844.151c.023-.162.067-.274.133-.338s.151-.098.257-.098a.33.33 0 0 1 .241.089c.059.06.087.139.087.238 0 .104-.038.193-.117.27s-.177.112-.293.112a.907.907 0 0 1-.116-.011l-.045.649a1.13 1.13 0 0 1 .289-.056c.132 0 .237.041.313.126.077.082.115.199.115.352 0 .146-.04.266-.119.354a.394.394 0 0 1-.301.134zm.948-10.083V5h-.739a1.47 1.47 0 0 1-.394.523c-.168.142-.404.262-.708.365v.754a2.595 2.595 0 0 0 .937-.48v2.206h.904zM9 6h11v2H9zm0 5h11v2H9zm0 5h11v2H9z">
                            </path>
                        </svg>
                    </button>
                    <button x-on:click="setHorizontalRule()" type="button"
                        aria-label="{{ __('Horizontální rozdělení') }}" title="{{ __('Horizontální rozdělení') }}"
                        class="text-gray-600 hover:bg-gray-100">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path d="M10 10h4v4h-4zm6 0h4v4h-4zM4 10h4v4H4z"></path>
                        </svg>
                    </button>
                </div>
                <div class="flex flex-wrap space-x-1">
                    <button x-on:click="undo()" type="button" aria-label="{{ __('Zpět') }}"
                        title="{{ __('Zpět') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M9 10h6c1.654 0 3 1.346 3 3s-1.346 3-3 3h-3v2h3c2.757 0 5-2.243 5-5s-2.243-5-5-5H9V5L4 9l5 4v-3z">
                            </path>
                        </svg>
                    </button>
                    <button x-on:click="redo()" type="button" aria-label="{{ __('Znovu') }}"
                        title="{{ __('Znovu') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M9 18h3v-2H9c-1.654 0-3-1.346-3-3s1.346-3 3-3h6v3l5-4-5-4v3H9c-2.757 0-5 2.243-5 5s2.243 5 5 5z">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
            <div x-ref="editorReference" class="outline-none"></div>
        </div>
        <x-button-simple type="button" class="w-full" x-on:click="$wire.save(editor.getHTML())"
            wire:loading.attr="disabled">
            {{ __('Uložit') }}
        </x-button-simple>
        <div wire:loading>
            Ukládám...
        </div>
    </div>
    @push('scripts')
        <script src="{{ asset('dist/editor.js') }}"></script>
    @endpush
</div>
