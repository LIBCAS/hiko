<div class="p-6 bg-white rounded-lg shadow-lg" x-data="dropzoneComponent()" x-init="showButtons = false">
    @if (session()->has('message'))
        <div class="mb-4 p-4 text-green-700 bg-green-100 rounded">{{ session('message') }}</div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 text-red-700 bg-red-100 rounded">{{ session('error') }}</div>
    @endif

    <form wire:submit.prevent="uploadAndProcess" class="space-y-6" enctype="multipart/form-data">
        <div class="flex flex-col space-y-3">
            <label for="photos" class="text-lg font-semibold text-gray-700">
                {{ __('hiko.upload_document') }}
            </label>
            <div
                class="flex justify-center items-center px-4 py-6 bg-gray-100 hover:bg-gray-200 rounded-lg cursor-pointer transition-all duration-300 ease-in-out"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="isDragging = false; handleFiles($event)"
                :class="{
                    'border-blue-500 bg-gray-50': isDragging,
                    'hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500': !isDragging
                }"
                @click="$refs.fileInput.click()"
                x-ref="dropzoneArea"
            >
                <div class="text-center mx-auto">
                    <svg class="mx-auto" width="48" height="48" viewBox="0 0 24 24" style="fill:rgb(109 40 217);">
                        <path d="M13 19v-4h3l-4-5-4 5h3v4z"></path>
                        <path d="M7 19h2v-2H7c-1.654 0-3-1.346-3-3 0-1.404 1.199-2.756 2.673-3.015l.581-.102.192-.558C8.149 8.274 9.895 7 12 7c2.757 0 5 2.243 5 5v1h1c1.103 0 2 .897 2 2s-.897 2-2 2h-3v2h3c2.206 0 4-1.794 4-4a4.01 4.01 0 0 0-3.056-3.888C18.507 7.67 15.56 5 12 5 9.244 5 6.85 6.611 5.757 9.15 3.609 9.792 2 11.82 2 14c0 2.757 2.243 5 5 5z"></path>
                    </svg>
                    <p class="mt-1 text-xs text-gray-500">{{ __('hiko.supported_formats') }}</p>
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

            <template x-if="files.length > 0">
                <div class="mt-4">
                    <ul class="space-y-2">
                        <template x-for="(file, index) in files" :key="index">
                            <li class="flex items-center border rounded p-2 hover:bg-gray-50">
                                <div class="w-6 h-6 mr-2 rounded overflow-hidden">
                                    <template x-if="file.type.startsWith('image/')">
                                        <img :src="file.preview" alt="Preview" class="object-cover w-full h-full" />
                                    </template>
                                    <template x-if="!file.type.startsWith('image/')">
                                        <svg class="h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m2 0a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </template>
                                </div>
                                <span class="flex-1 text-sm text-gray-700 truncate" x-text="file.name"></span>
                                <button type="button" @click="removeFile(index)" class="text-red-500 hover:text-red-700">×</button>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            @error('photos.*')
                <span class="text-xs text-red-500">{{ $message }}</span>
            @enderror
        </div>

        <div class="flex flex-row items-center space-x-3">
            <label for="selectedModel" class="text-sm font-semibold text-nowrap text-gray-700">
                {{ __('hiko.ocr_model') }}
            </label>
            <select id="selectedModel" wire:model="selectedModel" class="w-full border-gray-300 rounded-md">
                @foreach ($models as $modelKey => $label)
                    <option value="{{ $modelKey }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex space-x-4" x-show="showButtons">
            <button
                type="submit"
                class="flex-1 relative flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-primary rounded hover:bg-black disabled:opacity-50"
                wire:loading.attr="disabled"
                wire:target="uploadAndProcess"
                aria-busy="{{ $isProcessing ? 'true' : 'false' }}"
            >
                <span wire:loading.remove wire:target="uploadAndProcess">{{ __('hiko.recognize_text') }}</span>
                <span wire:loading wire:target="uploadAndProcess" class="flex justify-center items-center">
                    <svg class="animate-spin h-5 w-5 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </span>
            </button>
        </div>
    </form>

    @if (!empty($snapshots))
        <div class="mt-8 border rounded p-4">
            <h3 class="text-lg font-bold mb-3">{{ __('hiko.ocr_snapshots_history') }}</h3>
            <div class="space-y-2">
                @foreach ($snapshots as $snapshot)
                    <button
                        type="button"
                        wire:click="selectSnapshot({{ $snapshot['id'] }})"
                        class="w-full text-left border rounded p-2 hover:bg-gray-50 {{ $selectedSnapshotId === $snapshot['id'] ? 'border-primary bg-gray-50' : '' }}"
                    >
                        <div class="text-sm font-semibold">
                            #{{ $snapshot['id'] }} | {{ $snapshot['model'] }} | {{ $snapshot['created_at'] }}
                        </div>
                        <div class="text-xs text-gray-600">
                            {{ __('hiko.status') }}: <span class="lowercase">{{ __('hiko.' .  $snapshot['status']) }}</span>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @php
        $hasMappableData = $selectedSnapshotId || !empty($transientMappedFields);
        $hasRawData = !empty($ocrText) || count(array_filter($metadata)) > 0;
        $selectedSnapshot = collect($snapshots)->firstWhere('id', $selectedSnapshotId);
        $snapshotFields = $selectedSnapshot['mapped_fields'] ?? $transientMappedFields;
    @endphp

    @if ($hasRawData)
        @php
            $mappedFieldKeys = array_keys($snapshotFields);
            $rawToMappedField = [
                'Rok' => 'date_year',
                'Měsíc' => 'date_month',
                'Den' => 'date_day',
                'Datum označené v dopise' => 'date_marked',
                'Poznámka k datu' => 'date_note',
                'Poznámka k autorům' => 'author_note',
                'Poznámka k příjemcům' => 'recipient_note',
                'Poznámka pro zpracovatele' => 'notes_private',
                'Veřejná poznámka' => 'notes_public',
                'Abstrakt CS' => 'abstract_cs',
                'Abstrakt EN' => 'abstract_en',
                'Incipit' => 'incipit',
                'Explicit' => 'explicit',
                'Copyright' => 'copyright',
                'Datum je nejisté' => 'date_uncertain',
                'Datum je nejisté (bool)' => 'date_uncertain',
                'Datum je přibližné' => 'date_approximate',
                'Datum je přibližné (bool)' => 'date_approximate',
                'Datum je odvozené' => 'date_inferred',
                'Datum je odvozené (bool)' => 'date_inferred',
                'Datum je uvedené v rozmezí' => 'date_is_range',
                'Datum je uvedené v rozmezí (bool)' => 'date_is_range',
                'Autor je odvozený' => 'author_inferred',
                'Autor je odvozený (bool)' => 'author_inferred',
                'Autor je nejistý' => 'author_uncertain',
                'Autor je nejistý (bool)' => 'author_uncertain',
                'Příjemce je odvozený' => 'recipient_inferred',
                'Příjemce je odvozený (bool)' => 'recipient_inferred',
                'Příjemce je nejistý' => 'recipient_uncertain',
                'Příjemce je nejistý (bool)' => 'recipient_uncertain',
                'Místo určení je odvozené' => 'destination_inferred',
                'Místo určení je odvozené (bool)' => 'destination_inferred',
                'Místo určení je nejisté' => 'destination_uncertain',
                'Místo určení je nejisté (bool)' => 'destination_uncertain',
                'Místo odeslání je odvozené' => 'origin_inferred',
                'Místo odeslání je odvozené (bool)' => 'origin_inferred',
                'Místo odeslání je nejisté' => 'origin_uncertain',
                'Místo odeslání je nejisté (bool)' => 'origin_uncertain',
            ];
            $rawLabelTranslations = [
                'Rok' => __('hiko.year'),
                'Měsíc' => __('hiko.month'),
                'Den' => __('hiko.day'),
                'Datum označené v dopise' => __('hiko.date_marked'),
                'Datum je nejisté' => __('hiko.date_uncertain'),
                'Datum je nejisté (bool)' => __('hiko.date_uncertain'),
                'Datum je přibližné' => __('hiko.date_approximate'),
                'Datum je přibližné (bool)' => __('hiko.date_approximate'),
                'Datum je odvozené' => __('hiko.date_inferred'),
                'Datum je odvozené (bool)' => __('hiko.date_inferred'),
                'Datum je uvedené v rozmezí' => __('hiko.date_is_range'),
                'Datum je uvedené v rozmezí (bool)' => __('hiko.date_is_range'),
                'Autor' => __('hiko.author'),
                'Jméno autora' => __('hiko.author_name'),
                'Autor je odvozený' => __('hiko.author_inferred'),
                'Autor je odvozený (bool)' => __('hiko.author_inferred'),
                'Autor je nejistý' => __('hiko.author_uncertain'),
                'Autor je nejistý (bool)' => __('hiko.author_uncertain'),
                'Příjemce' => __('hiko.recipient'),
                'Jméno příjemce' => __('hiko.recipient_name'),
                'Příjemce je odvozený' => __('hiko.recipient_inferred'),
                'Příjemce je odvozený (bool)' => __('hiko.recipient_inferred'),
                'Příjemce je nejistý' => __('hiko.recipient_uncertain'),
                'Příjemce je nejistý (bool)' => __('hiko.recipient_uncertain'),
                'Místo odeslání' => __('hiko.origin'),
                'Místo odeslání je odvozené' => __('hiko.origin_inferred'),
                'Místo odeslání je odvozené (bool)' => __('hiko.origin_inferred'),
                'Místo odeslání je nejisté' => __('hiko.origin_uncertain'),
                'Místo odeslání je nejisté (bool)' => __('hiko.origin_uncertain'),
                'Místo určení' => __('hiko.destination'),
                'Místo určení je odvozené' => __('hiko.destination_inferred'),
                'Místo určení je odvozené (bool)' => __('hiko.destination_inferred'),
                'Místo určení je nejisté' => __('hiko.destination_uncertain'),
                'Místo určení je nejisté (bool)' => __('hiko.destination_uncertain'),
                'Jazyk' => __('hiko.language'),
                'Abstrakt CS' => __('hiko.abstract') . ' CS',
                'Abstrakt EN' => __('hiko.abstract') . ' EN',
                'Incipit' => __('hiko.incipit'),
                'Explicit' => __('hiko.explicit'),
                'Poznámka k datu' => __('hiko.date_note'),
                'Poznámka k autorům' => __('hiko.author_note'),
                'Poznámka k příjemcům' => __('hiko.recipient_note'),
                'Poznámka pro zpracovatele' => __('hiko.notes_private'),
                'Veřejná poznámka' => __('hiko.notes_public'),
                'Copyright' => __('hiko.copyright'),
            ];
        @endphp

        <div class="mt-6 border rounded p-4 space-y-6">
            <h3 class="text-lg font-bold">
                {{ __('hiko.ocr_apply_snapshot') }}
                @if ($selectedSnapshotId)
                    #{{ $selectedSnapshotId }}
                @endif
            </h3>

            @if ($hasMappableData)
                <div>
                    <label for="applyMode" class="text-sm font-semibold text-gray-700">{{ __('hiko.ocr_apply_mode') }}</label>
                    <select id="applyMode" wire:model="applyMode" class="w-full border-gray-300 rounded-md mt-1">
                        @foreach ($applyModes as $mode => $label)
                            <option value="{{ $mode }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-lg font-bold">{{ __('hiko.full_text') }}</h2>
                    @if ($hasMappableData)
                        <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                            <input type="checkbox" wire:model.live="selectAllMappedFields" class="rounded border-gray-300">
                            <span>{{ __('hiko.all') }}</span>
                        </label>
                    @endif
                </div>
                @if (!empty($ocrText))
                    <div class="space-y-2">
                        <textarea class="w-full p-3 border border-gray-300 rounded" rows="12" readonly>{{ $ocrText }}</textarea>
                    </div>
                @else
                    <p class="text-red-500">N/A</p>
                @endif
            </div>

            <div>
                <h2 class="mb-2 text-lg font-bold">{{ __('hiko.metadata') }}</h2>
                @if (count(array_filter($metadata)) > 0)
                    @php
                        // Stable rendering order aligned with the Letter form sections.
                        $metadataOrderedKeys = [
                            'Rok',
                            'Měsíc',
                            'Den',
                            'Datum označené v dopise',
                            'Datum je nejisté',
                            'Datum je nejisté (bool)',
                            'Datum je přibližné',
                            'Datum je přibližné (bool)',
                            'Datum je odvozené',
                            'Datum je odvozené (bool)',
                            'Datum je uvedené v rozmezí',
                            'Datum je uvedené v rozmezí (bool)',
                            'Poznámka k datu',
                            'Jméno autora',
                            'Autor je odvozený',
                            'Autor je odvozený (bool)',
                            'Autor je nejistý',
                            'Autor je nejistý (bool)',
                            'Poznámka k autorům',
                            'Jméno příjemce',
                            'Příjemce je odvozený',
                            'Příjemce je odvozený (bool)',
                            'Příjemce je nejistý',
                            'Příjemce je nejistý (bool)',
                            'Poznámka k příjemcům',
                            'Místo odeslání',
                            'Místo odeslání je odvozené',
                            'Místo odeslání je odvozené (bool)',
                            'Místo odeslání je nejisté',
                            'Místo odeslání je nejisté (bool)',
                            'Místo určení',
                            'Místo určení je odvozené',
                            'Místo určení je odvozené (bool)',
                            'Místo určení je nejisté',
                            'Místo určení je nejisté (bool)',
                            'Jazyk',
                            'Abstrakt CS',
                            'Abstrakt EN',
                            'Incipit',
                            'Explicit',
                            'Poznámka pro zpracovatele',
                            'Veřejná poznámka',
                            'Copyright',
                        ];
                        $excludedMetadataKeys = ['recognized_text', 'full_text'];
                        $metadataKeys = array_keys($metadata);
                        $orderedPresentKeys = array_values(array_filter(
                            $metadataOrderedKeys,
                            fn($k) => array_key_exists($k, $metadata)
                        ));
                        $remainingKeys = array_values(array_filter(
                            $metadataKeys,
                            fn($k) => !in_array($k, $metadataOrderedKeys, true) && !in_array($k, $excludedMetadataKeys, true)
                        ));
                        $metadataDisplayKeys = array_values(array_unique(array_merge($orderedPresentKeys, $remainingKeys)));
                    @endphp
                    <div class="overflow-x-auto border rounded">
                        <table class="min-w-full">
                            <tbody>
                                @foreach ($metadataDisplayKeys as $key)
                                    @php
                                        $value = $metadata[$key] ?? null;
                                        $label = preg_replace('/\s*\(bool\)$/i', '', (string) $key);
                                        $translatedLabel = $rawLabelTranslations[(string) $key] ?? $rawLabelTranslations[$label] ?? $label;
                                        $isEmpty = is_null($value)
                                            || (is_string($value) && trim($value) === '')
                                            || (is_array($value) && count($value) === 0);
                                        $mappedFieldKey = $rawToMappedField[$key] ?? null;
                                        $isMapped = $mappedFieldKey && in_array($mappedFieldKey, $mappedFieldKeys, true);
                                        $checkboxId = $isMapped ? 'ocr-meta-' . md5($key . $mappedFieldKey) : null;
                                    @endphp
                                    @if (!$isEmpty)
                                        <tr class="align-top">
                                            <td rowspan="2" class="w-0 whitespace-nowrap px-2 py-2">
                                                @if ($isMapped)
                                                    <input id="{{ $checkboxId }}" type="checkbox" wire:model="selectedFields" value="{{ $mappedFieldKey }}" class="rounded border-gray-300">
                                                @endif
                                            </td>
                                            <td class="pr-3 pl-0 pt-2 pb-1">
                                                <div class="flex items-center gap-2">
                                                    @if ($isMapped)
                                                        <label for="{{ $checkboxId }}" class="cursor-pointer font-bold">
                                                            {{ $translatedLabel }}
                                                        </label>
                                                    @else
                                                        <span class="font-bold">{{ $translatedLabel }}</span>
                                                    @endif
                                                    @if ($isMapped && (($fieldDiffs[$mappedFieldKey] ?? false) === true))
                                                        <span class="text-xs px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">{{ __('hiko.ocr_differs') }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="border-b border-gray-200 last:border-b-0">
                                            <td class="pr-3 pl-0 pb-2 text-sm text-gray-700 break-words">
                                                @if (is_array($value))
                                                    {{ implode(', ', $value) }}
                                                @elseif (is_bool($value))
                                                    {{ $value ? __('hiko.yes') : __('hiko.no') }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-red-500">{{ __('hiko.no_metadata_found') }}</p>
                @endif
            </div>

            @if ($hasMappableData)
                @error('selectedFields')
                    <div class="text-xs text-red-500">{{ $message }}</div>
                @enderror

                <button
                    type="button"
                    wire:click="applySnapshot"
                    class="w-full px-4 py-2 text-sm font-medium text-white bg-primary rounded hover:bg-black"
                >
                    {{ __('hiko.ocr_apply_snapshot_button') }}
                </button>
            @endif
        </div>
    @endif
</div>

<script>
    function dropzoneComponent() {
        return {
            isDragging: false,
            files: [],
            dataTransfer: new DataTransfer(),
            showButtons: false,
            handleFiles(event) {
                const selectedFiles = Array.from(event.dataTransfer ? event.dataTransfer.files : event.target.files);
                if (selectedFiles.length + this.files.length > 100) {
                    alert('You can upload up to 100 files.');
                    return;
                }

                selectedFiles.forEach(file => {
                    if (this.files.length >= 100) return;

                    const validTypes = [
                        'image/jpeg',
                        'image/png',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ];
                    if (!validTypes.includes(file.type)) {
                        alert(`Unsupported file type: ${file.name}`);
                        return;
                    }

                    const maxSize = 5 * 1024 * 1024;
                    if (file.size > maxSize) {
                        alert(`File ${file.name} exceeds the maximum size limit of 5MB.`);
                        return;
                    }

                    if (file.type.startsWith('image/')) {
                        file.preview = URL.createObjectURL(file);
                    }

                    this.files.push(file);
                    this.dataTransfer.items.add(file);
                });

                this.$refs.fileInput.files = this.dataTransfer.files;
                this.$wire.set('photos', this.dataTransfer.files);
                this.showButtons = this.files.length > 0;
            },
            removeFile(index) {
                this.files.splice(index, 1);
                this.dataTransfer = new DataTransfer();
                this.files.forEach(file => this.dataTransfer.items.add(file));
                this.$refs.fileInput.files = this.dataTransfer.files;
                this.$wire.set('photos', this.dataTransfer.files);
                this.showButtons = this.files.length > 0;
            }
        };
    }
</script>
