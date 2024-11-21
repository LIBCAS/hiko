<div class="p-4 bg-white rounded shadow">
    <form wire:submit.prevent="uploadAndProcess" class="flex flex-col space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Upload Image</label>
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
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none"
                :disabled="isProcessing">
                <span x-show="!isProcessing">Upload and Process</span>
                <span x-show="isProcessing" class="flex items-center">
                    <svg class="animate-spin h-5 w-5 mr-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Processing...
                </span>
            </button>
        </div>
    </form>

    @if ($tempImagePath)
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">Preview:</label>
            <img src="{{ route('serve-local-file', ['path' => $tempImagePath]) }}" alt="Uploaded Image" class="mt-2 max-w-full h-auto rounded">
            <button wire:click="deleteTemporaryFile" class="mt-2 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                Delete Image
            </button>
        </div>
    @endif

    @if ($ocrText)
        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700">Recognized Text:</label>
            <textarea class="mt-2 w-full p-2 border border-gray-300 rounded text-sm" rows="10">{{ $ocrText }}</textarea>
        </div>
    @endif
</div>
