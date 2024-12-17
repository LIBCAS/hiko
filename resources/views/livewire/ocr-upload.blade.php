<div class="p-6 bg-white rounded-lg shadow-lg" x-data="{ isProcessing: @entangle('isProcessing') }">
    <form wire:submit.prevent="uploadAndProcess" class="space-y-6">
        {{-- Language Selection --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('hiko.language') }}</label>
            <select wire:model="selectedLanguage"
                class="w-full border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 rounded-lg text-sm">
                <option value="cs">Czech</option>
                <option value="en">English</option>
                <option value="de">German</option>
                <option value="fr">French</option>
                <option value="es">Spanish</option>
            </select>
        </div>

        {{-- File Upload --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('hiko.upload_image') }}</label>
            <input type="file" wire:model="photo" accept="image/*"
                class="w-full text-gray-500 border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4
                       file:rounded file:border-0 file:bg-blue-50 file:text-blue-700
                       hover:file:bg-blue-100 transition">
            @error('photo') 
                <span class="text-red-500 text-xs">{{ $message }}</span> 
            @enderror

            {{-- Show File Name --}}
            <div class="mt-2 text-sm text-gray-600" wire:loading.remove>
                @if ($photo)
                    {{ __('hiko.file_selected') }}: {{ $photo->getClientOriginalName() }}
                @else
                    {{ __('hiko.no_file_selected') }}
                @endif
            </div>
        </div>

        {{-- Success/Error Messages --}}
        <div>
            @if (session()->has('message'))
                <div class="text-green-600 text-sm font-medium">{{ session('message') }}</div>
            @endif
            @if (session()->has('error'))
                <div class="text-red-600 text-sm font-medium">{{ session('error') }}</div>
            @endif
        </div>

        {{-- Submit Button with Loading Spinner --}}
        <div>
            <button type="submit"
                class="w-full flex justify-center items-center py-2 px-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
                wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('hiko.upload_and_process') }}</span>
                <span wire:loading>
                    <svg class="animate-spin h-5 w-5 mr-2 text-white" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    {{ __('hiko.processing') }}
                </span>
            </button>
        </div>
    </form>

    {{-- Image Preview --}}
    @if ($tempImagePath && Storage::disk('local')->exists($tempImagePath))
        <div class="mt-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('hiko.preview') }}:</label>
            <div class="relative w-full flex justify-center">
                <img src="{{ route('serve-local-file', ['path' => urlencode($tempImagePath)]) }}"
                    alt="{{ __('hiko.uploaded_image') }}"
                    class="rounded-lg shadow-lg max-w-full h-auto">
                <button wire:click="deleteTemporaryFile"
                    class="absolute top-2 right-2 px-2 py-1 bg-red-600 text-white text-sm rounded-full hover:bg-red-700 transition">
                    ✕
                </button>
            </div>
        </div>
    @endif

    {{-- OCR Text Display --}}
    @if ($ocrText)
        <div class="mt-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('hiko.recognized_text') }}:</label>
            <textarea class="w-full border-gray-300 rounded-lg text-sm p-3 focus:ring focus:ring-blue-200"
                rows="8" readonly>{{ $ocrText }}</textarea>
        </div>

        {{-- Save Button --}}
        <button wire:click="saveOcrText"
            class="mt-4 w-full px-4 py-2 bg-green-500 text-white font-semibold rounded-lg hover:bg-green-600 transition">
            {{ __('hiko.save_text') }}
        </button>
    @endif
</div>
