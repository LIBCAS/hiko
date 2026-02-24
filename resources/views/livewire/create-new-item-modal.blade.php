<div x-data="{ showModal: false }" x-init="showModal = false">
    <button type="button" @click="showModal = true" class="text-sm font-bold text-primary hover:underline open-modal-button" onclick="hideHeaderFooterInIframe()">
        {{ $text }}
    </button>

    <div x-show="showModal" class="fixed inset-0 z-50 overflow-auto bg-gray-500 bg-opacity-75 modal-window" x-cloak>
        <div class="h-[90vh] bg-white m-8 shadow z-60 rounded overflow-hidden" @click.outside="showModal = false">
            <button type="button" @click="showModal = false; $dispatch('modal-closed')" class="absolute top-9 right-9 p-4 text-gray-500 hover:text-gray-700 z-70 modal-close">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <iframe src="{{ $route }}" frameborder="0" class="w-full h-full"></iframe>
        </div>
    </div>
</div>
