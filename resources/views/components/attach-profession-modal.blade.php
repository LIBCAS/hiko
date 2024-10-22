@can('manage-metadata')
    <div x-data="{ open: false }" x-cloak>
        <!-- Button to open the modal -->
        <button
            @click="open = true"
            class="bg-blue-500 text-white px-4 py-2 rounded"
        >
            {{ __('Attach Profession') }}
        </button>

        <!-- Modal Overlay --> 
        <div
            x-show="open"
            x-transition
            @keydown.escape.window="open = false"
            class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
        >
            <!-- Modal Content -->
            <div
                @click.away="open = false"
                class="bg-white p-6 rounded-lg shadow-lg"
            >
                <h2 class="text-lg font-semibold mb-4">{{ __('Attach Profession') }}</h2>
                <form method="POST" action="{{ route('professions.category.attach', ['category' => $professionCategory->id]) }}">
                    @csrf
                    <!-- Form Fields -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="profession_id">{{ __('Select Profession') }}</label>
                        <select id="profession_id" name="profession_ids[]" class="form-select mt-1 block w-full" required>
                            @foreach ($availableProfessions as $profession)
                                <option value="{{ $profession->id }}">{{ $profession->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button
                            type="button"
                            @click="open = false"
                            class="bg-gray-300 text-gray-700 px-4 py-2 rounded mr-2"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="submit"
                            class="bg-blue-500 text-white px-4 py-2 rounded"
                        >
                            {{ __('Attach') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
