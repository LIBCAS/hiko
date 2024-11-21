<div class="p-4 bg-white rounded shadow">
    <form wire:submit.prevent="uploadAndProcess" class="flex flex-col space-y-4">
        <!-- File Input -->
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

        <!-- Submit Button -->
        <div>
            <button type="submit" 
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none"
                x-data="{ isProcessing: @entangle('isProcessing') }"
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

        <!-- Image Preview -->
        @if ($tempImagePath && Storage::disk('local')->exists($tempImagePath))
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Preview:</label>
                <img src="{{ Storage::disk('local')->url($tempImagePath) }}" alt="Uploaded Image" class="mt-2 max-w-full h-auto rounded">
            </div>
        @endif
    </form>

    <!-- Extracted Text and Selection -->
    @if ($ocrText)
        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700">Extracted Text:</label>
            <div id="ocr-preview" 
                 class="mt-2 p-4 bg-gray-100 rounded text-sm text-gray-800 cursor-text"
                 x-data
                 x-init="() => {
                    const preview = $refs.preview;
                    preview.addEventListener('mouseup', () => {
                        const selection = window.getSelection().toString().trim();
                        if (selection.length > 0) {
                            @this.set('selectedText', selection);
                        }
                    });
                 }"
                 x-ref="preview">
                {!! nl2br(e($ocrText)) !!}
            </div>
            <textarea 
                class="mt-4 w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                wire:model="selectedText" 
                placeholder="Selected text will appear here..." 
                rows="4"></textarea>
        </div>
    @endif

    <!-- Notifications -->
    <div x-data="{ show: false, message: '', type: '' }" 
         x-show="show" 
         x-init="
            window.addEventListener('ocr-completed', event => {
                message = 'OCR completed successfully!';
                type = 'success';
                show = true;
                setTimeout(() => show = false, 3000);
            });
            window.addEventListener('ocr-failed', event => {
                message = event.detail.message;
                type = 'error';
                show = true;
                setTimeout(() => show = false, 5000);
            });
         " 
         class="fixed bottom-5 right-5 z-50">
        <div x-show="show" 
             x-transition 
             :class="type === 'success' ? 'bg-green-500' : 'bg-red-500'"
             class="text-white px-4 py-2 rounded shadow">
            <span x-text="message"></span>
        </div>
    </div>
</div>
