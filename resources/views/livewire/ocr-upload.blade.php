<div class="p-6 bg-white rounded-lg shadow-lg" x-data="{ isProcessing: false }">
    <!-- File Upload Form -->
    <form wire:submit.prevent="uploadAndProcess" class="space-y-6">
        <!-- File Upload -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('hiko.upload_image') }}</label>
            <input type="file" wire:model="photo" accept="image/*"
                class="w-full text-gray-500 border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4
                       file:rounded file:border-0 file:bg-blue-50 file:text-blue-700
                       hover:file:bg-blue-100 transition" />
            @error('photo') 
                <span class="text-red-500 text-xs">{{ $message }}</span> 
            @enderror
        </div>

        <!-- Submit Button with Loader -->
        <div>
            <button type="submit"
                class="w-full flex justify-center items-center py-2 px-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
                wire:loading.attr="disabled" @click="isProcessing = true">
                <span x-show="!isProcessing">{{ __('hiko.upload_and_process') }}</span>
                <span x-show="isProcessing">
                    <svg class="animate-spin h-5 w-5 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    {{ __('hiko.processing') }}
                </span>
            </button>
        </div>
    </form>

    <!-- Results Section -->
    @if ($metadata)
        <div class="mt-6 space-y-4">
            <!-- Recognized Text -->
            <div>
                <h2 class="font-bold text-lg mb-2">{{ __('hiko.recognized_text') }}</h2>
                @if (!empty($ocrText))
                    <textarea class="w-full border-gray-300 rounded-lg p-3 focus:ring focus:ring-blue-200" rows="8" readonly>{{ $ocrText }}</textarea>
                @else
                    <p class="text-red-500">{{ __('hiko.no_text_found') }}</p>
                @endif
            </div>

            <!-- Extracted Metadata -->
            <div>
                <h2 class="font-bold text-lg mb-2">{{ __('hiko.extracted_metadata') }}</h2>
                @if (!empty($metadata))
                    <div class="bg-gray-50 p-4 rounded-lg shadow">
                        <ul class="space-y-1">
                            @foreach ($metadata as $key => $value)
                                @if (is_array($value))
                                    <li><strong>{{ __('hiko.' . $key) }}:</strong> {{ implode(', ', $value) }}</li>
                                @elseif (!is_null($value))
                                    <li><strong>{{ __('hiko.' . $key) }}:</strong> {{ $value }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @else
                    <p class="text-red-500">{{ __('hiko.no_metadata_found') }}</p>
                @endif
            </div>
        </div>
    @endif
</div>
