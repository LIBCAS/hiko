<div class="p-6 bg-white rounded-lg shadow-lg">
    <!-- Success and Error Messages -->
    @if (session()->has('message'))
        <div class="mb-4 p-4 text-green-700 bg-green-100 rounded">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 text-red-700 bg-red-100 rounded">
            {{ session('error') }}
        </div>
    @endif

    <!-- File Upload Form -->
    <form wire:submit="uploadAndProcess" class="space-y-6" enctype="multipart/form-data">
        <!-- File Input -->
        <div class="flex flex-col space-y-1">
            <label for="photo" class="text-sm font-medium text-gray-700">
                Upload Document
            </label>
            <input
                id="photo"
                type="file"
                wire:model.live="photo"
                accept="image/*,application/pdf"
                class="block w-full text-sm text-gray-900 border border-gray-300 rounded cursor-pointer
                       focus:outline-none focus:ring focus:ring-blue-300
                       file:mr-4 file:py-2 file:px-4 file:rounded file:border-0
                       file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                aria-describedby="photoHelp"
                aria-required="true"
            />
            <small id="photoHelp" class="text-xs text-gray-500">Supported formats: JPEG, PNG, PDF. Max size: 10MB.</small>
            @error('photo')
                <span class="text-xs text-red-500">{{ $message }}</span>
            @enderror
        </div>

        <!-- Submit and Reset Buttons -->
        <div class="flex space-x-4">
            <!-- Submit Button + Loader -->
            <button
                type="submit"
                class="flex-1 relative flex items-center justify-center px-4 py-2 text-sm
                       font-medium text-white bg-blue-600 rounded
                       hover:bg-blue-700 disabled:opacity-50 transition"
                wire:loading.attr="disabled"
                wire:target="uploadAndProcess"
                aria-busy="{{ $isProcessing ? 'true' : 'false' }}"
            >
                <!-- Button Text when not processing -->
                <span wire:loading.remove wire:target="uploadAndProcess">
                    Upload and Process
                </span>

                <!-- Loader when processing -->
                <span wire:loading wire:target="uploadAndProcess" class="flex items-center">
                    <svg
                        class="animate-spin h-5 w-5 mr-2 text-white"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
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
                    Processing...
                </span>
            </button>

            <!-- Reset Button -->
            <button
                type="button"
                wire:click="resetForm"
                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded
                       hover:bg-gray-300 transition"
            >
                Reset
            </button>
        </div>
    </form>

    <!-- Processing Results -->
    @if ($metadata)
        <div class="mt-6 space-y-6">
            <!-- Recognized Text -->
            <div>
                <h2 class="mb-2 text-lg font-bold">Recognized Text</h2>
                @if (!empty($ocrText))
                    <textarea
                        class="w-full p-3 border border-gray-300 rounded
                               focus:ring focus:ring-blue-200"
                        rows="8"
                        readonly
                    >{{ $ocrText }}</textarea>
                @else
                    <p class="text-red-500">No text found.</p>
                @endif
            </div>

            <!-- Extracted Metadata -->
            <div>
                <h2 class="mb-2 text-lg font-bold">Extracted Metadata</h2>

                @if (!empty($metadata))
                    <div class="p-4 bg-gray-50 rounded shadow">
                        <ul class="space-y-2">
                            @foreach ($metadata as $key => $value)
                                @php
                                    // Skip recognized_text and full_text to avoid redundancy
                                    if (in_array($key, ['recognized_text', 'full_text'])) {
                                        continue;
                                    }

                                    // Label (fallback to formatted key)
                                    $label = ucfirst(str_replace('_', ' ', $key));

                                    // Determine if the value is empty
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
                                    <li class="flex flex-col space-y-1">
                                        <strong>{{ $label }}:</strong>
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
                                                                {{ $subValue ? 'Yes' : 'No' }}
                                                            @else
                                                                {{ $subValue }}
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                {{-- If array is flat --}}
                                                <span>{{ implode(', ', $value) }}</span>
                                            @endif
                                        @elseif (is_bool($value))
                                            <span>{{ $value ? 'Yes' : 'No' }}</span>
                                        @else
                                            <span>{{ $value }}</span>
                                        @endif
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @else
                    <p class="text-red-500">No metadata found.</p>
                @endif
            </div>
        </div>
    @endif
</div>
