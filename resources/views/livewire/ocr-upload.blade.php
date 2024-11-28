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
        </div>

        @if(!empty($metadata))
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700">{{ __('hiko.extracted_metadata') }}:</label>
                <div class="mt-2 p-4 border border-gray-300 rounded text-sm bg-gray-50">
                    <ul class="list-disc list-inside space-y-2">
                        <li><strong>{{ __('hiko.year') }}:</strong> {{ $metadata['year'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.month') }}:</strong> {{ $metadata['month'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.day') }}:</strong> {{ $metadata['day'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.date_marked') }}:</strong> {{ $metadata['date_marked'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.date_uncertain') }}:</strong> {{ $metadata['date_uncertain'] ? __('hiko.yes') : __('hiko.no') }}</li>
                        <li><strong>{{ __('hiko.date_approximate') }}:</strong> {{ $metadata['date_approximate'] ? __('hiko.yes') : __('hiko.no') }}</li>
                        <li><strong>{{ __('hiko.date_inferred') }}:</strong> {{ $metadata['date_inferred'] ? __('hiko.yes') : __('hiko.no') }}</li>
                        <li><strong>{{ __('hiko.date_is_range') }}:</strong> {{ $metadata['date_is_range'] ? __('hiko.yes') : __('hiko.no') }}</li>
                        <li><strong>{{ __('hiko.range_year') }}:</strong> {{ $metadata['range_year'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.range_month') }}:</strong> {{ $metadata['range_month'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.range_day') }}:</strong> {{ $metadata['range_day'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.date_note') }}:</strong> {{ $metadata['date_note'] ?? __('hiko.not_available') }}</li>

                        <li><strong>{{ __('hiko.author') }}:</strong> {{ $metadata['author'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.author_inferred') }}:</strong> {{ $metadata['author_inferred'] ? __('hiko.yes') : __('hiko.no') }}</li>
                        <li><strong>{{ __('hiko.author_uncertain') }}:</strong> {{ $metadata['author_uncertain'] ? __('hiko.yes') : __('hiko.no') }}</li>
                        <li><strong>{{ __('hiko.author_note') }}:</strong> {{ $metadata['author_note'] ?? __('hiko.not_available') }}</li>

                        <li><strong>{{ __('hiko.recipient') }}:</strong> {{ $metadata['recipient'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.recipient_inferred') }}:</strong> {{ $metadata['recipient_inferred'] ? __('hiko.yes') : __('hiko.no') }}</li>
                        <li><strong>{{ __('hiko.recipient_uncertain') }}:</strong> {{ $metadata['recipient_uncertain'] ? __('hiko.yes') : __('hiko.no') }}</li>
                        <li><strong>{{ __('hiko.recipient_note') }}:</strong> {{ $metadata['recipient_note'] ?? __('hiko.not_available') }}</li>

                        <li><strong>{{ __('hiko.origin') }}:</strong> {{ $metadata['origin'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.origin_inferred') }}:</strong> {{ $metadata['origin_inferred'] ? __('hiko.yes') : __('hiko.no') }}</li>
                        <li><strong>{{ __('hiko.origin_uncertain') }}:</strong> {{ $metadata['origin_uncertain'] ? __('hiko.yes') : __('hiko.no') }}</li>
                        <li><strong>{{ __('hiko.origin_note') }}:</strong> {{ $metadata['origin_note'] ?? __('hiko.not_available') }}</li>

                        <li><strong>{{ __('hiko.destination') }}:</strong> {{ $metadata['destination'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.destination_inferred') }}:</strong> {{ $metadata['destination_inferred'] ? __('hiko.yes') : __('hiko.no') }}</li>
                        <li><strong>{{ __('hiko.destination_uncertain') }}:</strong> {{ $metadata['destination_uncertain'] ? __('hiko.yes') : __('hiko.no') }}</li>
                        <li><strong>{{ __('hiko.destination_note') }}:</strong> {{ $metadata['destination_note'] ?? __('hiko.not_available') }}</li>

                        <li><strong>{{ __('hiko.languages') }}:</strong> {{ implode(', ', $metadata['languages']) }}</li>
                        <li><strong>{{ __('hiko.keywords') }}:</strong> {{ implode(', ', $metadata['keywords']) }}</li>
                        <li><strong>{{ __('hiko.abstract_cs') }}:</strong> {{ $metadata['abstract_cs'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.abstract_en') }}:</strong> {{ $metadata['abstract_en'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.incipit') }}:</strong> {{ $metadata['incipit'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.explicit') }}:</strong> {{ $metadata['explicit'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.mentioned') }}:</strong> {{ implode(', ', $metadata['mentioned']) }}</li>
                        <li><strong>{{ __('hiko.people_mentioned_note') }}:</strong> {{ $metadata['people_mentioned_note'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.notes_private') }}:</strong> {{ $metadata['notes_private'] ?? __('hiko.not_available') }}</li>
                        <li><strong>{{ __('hiko.notes_public') }}:</strong> {{ $metadata['notes_public'] ?? __('hiko.not_available') }}</li>

                        <li><strong>{{ __('hiko.related_resources') }}:</strong> 
                            @forelse($metadata['related_resources'] as $resource)
                                {{ $resource }}@if(!$loop->last), @endif
                            @empty
                                {{ __('hiko.not_available') }}
                            @endforelse
                        </li>

                        <li><strong>{{ __('hiko.copies') }}:</strong> 
                            @forelse($metadata['copies'] as $copy)
                                {{ $copy }}@if(!$loop->last), @endif
                            @empty
                                {{ __('hiko.not_available') }}
                            @endforelse
                        </li>

                        <li><strong>{{ __('hiko.copyright') }}:</strong> {{ $metadata['copyright'] ?? __('hiko.not_available') }}</li>

                        <li><strong>{{ __('hiko.status') }}:</strong> {{ $metadata['status'] ?? __('hiko.not_available') }}</li>
                    </ul>
                </div>
            </div>
        @endif
    @endif
</div>
 