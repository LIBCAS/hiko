<div class="p-6 bg-white rounded-lg shadow-lg" x-data="dropzoneComponent()">
    <!-- Success and Error Messages -->
    @if (session()->has('message'))
        <div
            class="mb-4 p-4 text-green-700 bg-green-100 rounded transition-opacity duration-500 ease-in-out opacity-100">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 text-red-700 bg-red-100 rounded transition-opacity duration-500 ease-in-out opacity-100">
            {{ session('error') }}
        </div>
    @endif

    <!-- File Upload Form -->
    <form wire:submit.prevent="uploadAndProcess" class="space-y-6" enctype="multipart/form-data">
        <!-- Dropzone -->
        <div class="flex flex-col space-y-1">
            <label for="photos" class="text-lg font-semibold text-gray-700">
                {{ __('hiko.upload_document') }}
            </label>
            <div
                class="flex justify-center items-center px-4 py-6 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer 
                transition-all duration-300 ease-in-out"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="isDragging = false; handleFiles($event)"
                :class="{
                    'border-blue-500 bg-gray-50': isDragging,
                    'hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500': !isDragging // Add hover/focus only when not dragging
                }"
                @click="$refs.fileInput.click()"
                x-ref="dropzoneArea"
            >
                <div class="text-center mx-auto">
                    <!-- Cloud Icon - change depending on drag -->
                    <svg class="mx-auto" width="48" height="48" viewBox="0 0 24 24" style="fill:rgb(109 40 217);transform: ;msFilter:;"><path d="M13 19v-4h3l-4-5-4 5h3v4z"></path><path d="M7 19h2v-2H7c-1.654 0-3-1.346-3-3 0-1.404 1.199-2.756 2.673-3.015l.581-.102.192-.558C8.149 8.274 9.895 7 12 7c2.757 0 5 2.243 5 5v1h1c1.103 0 2 .897 2 2s-.897 2-2 2h-3v2h3c2.206 0 4-1.794 4-4a4.01 4.01 0 0 0-3.056-3.888C18.507 7.67 15.56 5 12 5 9.244 5 6.85 6.611 5.757 9.15 3.609 9.792 2 11.82 2 14c0 2.757 2.243 5 5 5z"></path></svg>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ __('hiko.supported_formats') }}
                    </p>
                </div>
                <input
                    type="file"
                    multiple
                    accept=".jpg,.jpeg,.png,.doc,.docx,.pdf"
                    class="hidden"
                    wire:model="photos"
                    x-ref="fileInput"
                    @change="handleFiles($event)"
                />
            </div>

            <!-- Preview Selected Files (List Style) -->
            <template x-if="files.length > 0">
                <div class="mt-4">
                    <ul class="space-y-2">
                        <template x-for="(file, index) in files" :key="index">
                            <li class="flex items-center border rounded p-2 hover:bg-gray-50" draggable="true"
                                @dragstart="dragStart(event, index)" @dragover.prevent="dragOver(event, index)" @dragend="dragEnd()">
                                <div class="w-6 h-6 mr-2 rounded overflow-hidden">
                                    <template x-if="file.type.startsWith('image/')">
                                        <img :src="file.preview" alt="Preview" class="object-cover w-full h-full" />
                                    </template>
                                    <template x-if="!file.type.startsWith('image/')">
                                        <svg class="h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m2 0a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </template>
                                </div>
                                <span class="flex-1 text-sm text-gray-700 truncate" x-text="file.name"></span>
                                <button type="button" @click="removeFile(index)"
                                    class="text-red-500 hover:text-red-700 transition-colors duration-300 ease-in-out">
                                    ×
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>


            @error('photos.*')
                <span class="text-xs text-red-500">{{ $message }}</span>
            @enderror
        </div>

        <!-- Submit and Reset Buttons -->
        <div class="flex space-x-4">
            <!-- Submit Button + Loader -->
            <button type="submit"
                class="flex-1 relative flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-primary rounded hover:bg-black disabled:opacity-50 transition-all duration-300 ease-in-out"
                wire:loading.attr="disabled" wire:target="uploadAndProcess" aria-busy="{{ $isProcessing ? 'true' : 'false' }}">
                <!-- Button text when not processing -->
                <h2 wire:loading.remove wire:target="uploadAndProcess">
                    {{ __('hiko.upload_document') }}
                        </h2>

                <!-- Loader when processing -->
                <span wire:loading wire:target="uploadAndProcess" class="flex items-center">
                    <svg class="animate-spin h-5 w-5 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    {{ __('hiko.loading') }}
                </span>
            </button>

            <!-- Reset Button -->
            <button type="button" wire:click="resetForm"
                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition-all duration-300 ease-in-out">
                {{ __('hiko.reset') }}
            </button>
        </div>
    </form>

    <!-- Processing Results -->
    @if (!empty($ocrText) || count(array_filter($metadata)) > 0)
        <div class="mt-6 space-y-6">
            <!-- Recognized Text -->
            <div>
                <h2 class="mb-2 text-lg font-bold">{{ __('hiko.full_text') }}</h2>
                @if (!empty($ocrText))
                    <textarea class="w-full p-3 border border-gray-300 rounded focus:ring focus:ring-blue-200 transition-all duration-300 ease-in-out"
                        rows="8" readonly>{{ $ocrText }}</textarea>
                @else
                    <p class="text-red-500">N/A</p>
                @endif
            </div>

            <!-- Extracted Metadata -->
            <div>
                <h2 class="mb-2 text-lg font-bold">{{ __('hiko.metadata') }}</h2>
                @if (count(array_filter($metadata)) > 0)
                    <div class="p-4 bg-gray-50 rounded shadow transition-all duration-300 ease-in-out">
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
                                                <!-- If the array is flat -->
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

<!-- Alpine.js Component -->
<script>
    function dropzoneComponent() {
        return {
            isDragging: false,
            draggedFile: false,
            files: [],
            draggedIndex: null,
            dataTransfer: new DataTransfer(), // Initialize data transfer
            handleFiles(event) {
                const selectedFiles = Array.from(event.dataTransfer ? event.dataTransfer.files : event.target.files);
                if (selectedFiles.length + this.files.length > 100) {
                    alert("You can upload up to 100 files.");
                    return;
                }

                 selectedFiles.forEach(file => {
                    if (this.files.length >= 100) return;

                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ];
                    if (!validTypes.includes(file.type)) {
                        alert(`Unsupported file type: ${file.name}`);
                        return;
                    }

                    // Validate file size (e.g., 5MB limit)
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    if (file.size > maxSize) {
                        alert(`File ${file.name} exceeds the maximum size limit of 5MB.`);
                        return;
                    }

                    // Add preview for images
                    if (file.type.startsWith('image/')) {
                        file.preview = URL.createObjectURL(file);
                    }

                    this.files.push(file);
                    this.dataTransfer.items.add(file);
                 });

                 // Update the file input
                this.$refs.fileInput.files = this.dataTransfer.files;

                // Inform Livewire about the change
                this.$wire.set('photos', this.dataTransfer.files);

                },
                removeFile(index) {
                    this.files.splice(index, 1);
                    this.dataTransfer = new DataTransfer(); // Recreate the DataTransfer
                    this.files.forEach(file => this.dataTransfer.items.add(file)); // Add remaining files
                     // Update the file input
                    this.$refs.fileInput.files = this.dataTransfer.files;

                     // Inform Livewire about the change
                    this.$wire.set('photos', this.dataTransfer.files);
                },
                dragStart(event, index) {
                    this.draggedIndex = index;
                    event.dataTransfer.setData('text/plain', index);
                    event.dataTransfer.effectAllowed = 'move';
                },
                dragOver(event, index) {
                  event.preventDefault();
                    if (this.draggedIndex === index) return;

                    const temp = this.files[this.draggedIndex];
                    this.files.splice(this.draggedIndex, 1);
                    this.files.splice(index, 0, temp);
                    this.draggedIndex = index;

                    this.dataTransfer = new DataTransfer(); // Recreate the DataTransfer
                    this.files.forEach(file => this.dataTransfer.items.add(file)); // Add remaining files
                    // Update the file input
                    this.$refs.fileInput.files = this.dataTransfer.files;
                     // Inform Livewire about the change
                    this.$wire.set('photos', this.dataTransfer.files);

                },
                dragEnd() {
                  this.draggedIndex = null;
                },
                 handleDragStart(event) {
                    this.isDragging = true;
                    this.draggedFile = true;

                },
                 handleDragEnd(event) {
                    this.isDragging = false;
                    this.draggedFile = false;

                }
        };
    }
</script>