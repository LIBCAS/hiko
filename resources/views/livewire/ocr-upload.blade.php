<div class="p-4 bg-white rounded shadow">
    <form wire:submit.prevent="uploadAndProcess" class="flex flex-col space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('hiko.language') }}</label>
            <select wire:model="selectedLanguage" class="mt-1 block w-full text-sm border-gray-300 rounded">
                <option value="cs">Czech</option>
                <option value="en">English</option>
                <option value="de">German</option>
                <option value="fr">French</option>
                <option value="es">Spanish</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('hiko.upload_image') }}</label>
            <input type="file" wire:model="photo" class="mt-1 block w-full text-sm text-gray-500 
                file:mr-4 file:py-2 file:px-4 
                file:rounded file:border-0 
                file:text-sm file:font-semibold 
                file:bg-blue-50 file:text-blue-700 
                hover:file:bg-blue-100" accept="image/*">
            @error('photo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <button type="submit"
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm 
            text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none"
            x-data="{ isProcessing: @entangle('isProcessing') }"
            x-bind:disabled="isProcessing">
            <span x-show="!isProcessing" x-transition.opacity>{{ __('hiko.upload_and_process') }}</span>
                <span x-show="isProcessing" x-transition.opacity class="flex items-center">
                    <svg class="animate-spin h-5 w-5 mr-3 text-white" xmlns="http://www.w3.org/2000/svg" 
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" 
                            stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" 
                            d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    {{ __('hiko.processing') }}
                </span>
            </button>
            <button wire:click="saveOcrText" 
                class="mt-2 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                {{ __('hiko.save_text') }}
            </button>

        </div>
    </form>

    @if ($tempImagePath && Storage::disk('local')->exists($tempImagePath))
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">{{ __('hiko.preview') }}:</label>
            <img src="{{ route('serve-local-file', ['path' => $tempImagePath]) }}" 
                alt="{{ __('hiko.uploaded_image') }}" 
                class="mt-2 max-w-full h-auto rounded">
            <button wire:click="deleteTemporaryFile" 
                class="mt-2 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                {{ __('hiko.delete_image') }}
            </button>
        </div>
    @endif

    @if ($ocrText)
        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700">{{ __('hiko.recognized_text') }}:</label>
            <textarea class="mt-2 w-full p-2 border border-gray-300 rounded text-sm" rows="10" readonly>{{ $ocrText }}</textarea>

            <!-- Save OCR Text Button -->
            <button wire:click="saveOcrText"
                class="mt-4 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                {{ __('hiko.save_text') }}
            </button>
        </div>
    @endif
</div>
 