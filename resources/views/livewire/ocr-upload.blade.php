<div
    class="p-6 bg-white rounded-lg shadow-lg"
    x-data="{ isProcessing: @entangle('isProcessing') }"  {{-- Livewire v3: two-way binding for isProcessing --}}
>
    <!-- File Upload Form -->
    <form wire:submit.prevent="uploadAndProcess" class="space-y-6">
        <!-- File Input -->
        <div class="flex flex-col space-y-1">
            <label for="photo" class="text-sm font-medium text-gray-700">
                {{ __('hiko.upload_image') }}
            </label>
            <input
                id="photo"
                type="file"
                wire:model="photo"
                accept="image/*,application/pdf"
                class="block w-full text-sm text-gray-900 border border-gray-300 rounded cursor-pointer
                       focus:outline-none focus:ring focus:ring-blue-300
                       file:mr-4 file:py-2 file:px-4 file:rounded file:border-0
                       file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
            @error('photo')
                <span class="text-xs text-red-500">{{ $message }}</span>
            @enderror
        </div>

        <!-- Submit Button + Loader -->
        <div>
            <button
                type="submit"
                class="relative w-full flex items-center justify-center px-4 py-2 text-sm
                       font-medium text-white bg-blue-600 rounded
                       hover:bg-blue-700 disabled:opacity-50 transition"
                wire:loading.attr="disabled"
                wire:target="uploadAndProcess,photo"
                @click="isProcessing = true"
            >
                <!-- Button Text when not processing -->
                <span x-show="!isProcessing" x-transition>
                    {{ __('hiko.upload_and_process') }}
                </span>

                <!-- Loader when processing -->
                <span x-show="isProcessing" x-transition class="flex items-center">
                    <svg
                        class="animate-spin h-5 w-5 mr-2 text-white"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v8H4z"
                        ></path>
                    </svg>
                    {{ __('hiko.processing') }}
                </span>
            </button>
        </div>
    </form>

    <!-- Processing Results -->
    @if ($metadata)
        <div class="mt-6 space-y-6">
            <!-- Recognized Text -->
            <div>
                <h2 class="mb-2 text-lg font-bold">{{ __('hiko.recognized_text') }}</h2>
                @if (!empty($ocrText))
                    <textarea
                        class="w-full p-3 border border-gray-300 rounded
                               focus:ring focus:ring-blue-200"
                        rows="8"
                    >{{ $ocrText }}</textarea>
                @else
                    <p class="text-red-500">{{ __('hiko.no_text_found') }}</p>
                @endif
            </div>

            <!-- Extracted Metadata -->
            <div>
                <h2 class="mb-2 text-lg font-bold">{{ __('hiko.extracted_metadata') }}</h2>

                @if (!empty($metadata))
                    <div class="p-4 bg-gray-50 rounded shadow">
                        <ul class="space-y-2">
                            @foreach ($metadata as $key => $value)
                                @php
                                    // Skip recognized_text and full_text to avoid redundancy
                                    if (in_array($key, ['recognized_text', 'full_text'])) {
                                        continue;
                                    }

                                    // 1. Label (translation) - fallback to formatted key
                                    $label = __('hiko.'.$key);
                                    if ($label === 'hiko.'.$key) {
                                        // No translation found, fallback
                                        $label = ucfirst(str_replace('_', ' ', $key));
                                    }

                                    // 2. Determine if the value is empty
                                    $isEmpty = false;
                                    if (is_null($value)) {
                                        $isEmpty = true;
                                    } elseif (is_string($value) && trim($value) === '') {
                                        $isEmpty = true;
                                    } elseif (is_array($value) && count($value) === 0) {
                                        $isEmpty = true;
                                    }
                                @endphp

                                @if (!$isEmpty)
                                    <li class="flex items-start space-x-2">
                                        @if (is_array($value))
                                            {{-- Check if the array is associative or indexed --}}
                                            @php
                                                $isAssoc = array_keys($value) !== range(0, count($value) - 1);
                                            @endphp

                                            @if ($isAssoc)
                                                <ul class="ml-4 list-disc">
                                                    @foreach ($value as $subKey => $subValue)
                                                        <li>
                                                            <strong>{{ ucfirst(str_replace('_', ' ', $subKey)) }}:</strong>
                                                            @if (is_array($subValue))
                                                                {{ implode(', ', $subValue) }}
                                                            @elseif (is_bool($subValue))
                                                                {{ $subValue ? __('hiko.yes') : __('hiko.no') }}
                                                            @else
                                                                {{ $subValue }}
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                {{-- If array is flat --}}
                                                {{ implode(', ', $value) }}
                                            @endif
                                        @elseif (is_bool($value))
                                            {{ $value ? __('hiko.yes') : __('hiko.no') }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </li>
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
