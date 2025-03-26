<x-app-layout :title="$title">
    <x-success-alert />

    <div class="grid grid-cols-3 gap-4 mb-4 space-y-3">
        <!-- Form Section -->
        <div class="max-w-sm">
            <form 
                x-data="similarItems({ 
                    similarNamesUrl: '{{ route('ajax.items.similar', ['model' => 'ProfessionCategory']) }}', 
                    id: '{{ $professionCategory->id ?? null }}' 
                })" 
                x-init="$watch('search', () => findSimilarNames($data))" 
                action="{{ $action }}" 
                method="POST" 
                class="space-y-3" 
                autocomplete="off"
            >
                @csrf
                @isset($method)
                    @method($method)
                @endisset

                <!-- CS Field -->
                <div>
                    <x-label for="cs" value="{{ __('CS') }}" />
                    <x-input 
                        id="cs" 
                        class="block w-full mt-1" 
                        type="text" 
                        name="cs" 
                        :value="old('cs', $professionCategory->translations['name']['cs'] ?? null)"
                        x-on:change="search = $el.value"
                        required
                    />
                    @error('cs')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- EN Field -->
                <div>
                    <x-label for="en" value="{{ __('EN') }}" />
                    <x-input 
                        id="en" 
                        class="block w-full mt-1" 
                        type="text" 
                        name="en" 
                        :value="old('en', $professionCategory->translations['name']['en'] ?? null)"
                        x-on:change="search = $el.value"
                    />
                    @error('en')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Alert for Similar Names -->
                <x-alert-similar-names />

                <!-- Submit Buttons -->
                <x-button-simple class="w-full" name="action" value="edit">
                    {{ $label }}
                </x-button-simple>
                <x-button-inverted class="w-full text-black bg-white" name="action" value="create">
                    {{ $label }} {{ __('hiko.and_create_new') }}
                </x-button-inverted>
            </form>

            <!-- Delete Button -->
            @if (isset($professionCategory) && $professionCategory->id)
                @can('delete-metadata')
                    <form 
                        x-data="{ form: $el }" 
                        action="{{ route('professions.category.destroy', $professionCategory->id) }}" 
                        method="POST" 
                        class="w-full mt-8"
                    >
                        @csrf
                        @method('DELETE')

                        <x-button-danger class="w-full" 
                            x-on:click.prevent="if (confirm('{{ __('hiko.confirm_remove') }}')) form.submit()">
                            {{ __('hiko.remove') }}
                        </x-button-danger>
                    </form>
                @endcan
            @endif
        </div>

        <!-- Related Identities Section -->
        @if (isset($professionCategory) && $professionCategory->id)
            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                @php
                    // Find identities related to the category
                    $relatedIdentities = collect();
                    
                    // Get all identities that have a profession in this category
                    $professionsInCategory = \App\Models\Profession::where('profession_category_id', $professionCategory->id)->get();
                    
                    foreach ($professionsInCategory as $profession) {
                        $identities = $profession->identities;
                        foreach ($identities as $identity) {
                            if (!$relatedIdentities->contains('id', $identity->id)) {
                                $relatedIdentities->push($identity);
                            }
                        }
                    }
                    
                    // Sort identities by name
                    $relatedIdentities = $relatedIdentities->sortBy('name');
                @endphp
                
                <h2 class="text-l font-semibold">
                    {{ __('hiko.attached_persons_count') }}: {{ $relatedIdentities->count() }}
                </h2>
                
                @if($relatedIdentities->count() > 0)
                    <ul class="list-disc px-3 py-3">
                        @foreach ($relatedIdentities as $identity)
                            <li>
                                <a href="{{ route('identities.edit', $identity->id) }}" 
                                   class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">
                                    {{ $identity->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500 mt-2">{{ __('hiko.no_attached_persons') }}</p>
                @endif
            </div>

            <!-- Related Professions Section -->
            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                @php
                    // Get all professions in this category
                    $categoryProfessions = \App\Models\Profession::where('profession_category_id', $professionCategory->id)->get();
                    
                    // Get count of professions with identities
                    $professionsWithIdentities = $categoryProfessions->filter(function($prof) {
                        return $prof->identities->count() > 0;
                    });
                @endphp
                
                <h2 class="text-l font-semibold">
                    {{ __('hiko.professions') }}: {{ $categoryProfessions->count() }}
                </h2>
                
                @if($categoryProfessions->count() > 0)
                    <ul class="list-disc px-3 py-3 space-y-1">
                        @foreach($categoryProfessions->sortBy(function($prof) {
                            return $prof->getTranslation('name', 'cs', false) ?? 
                                   $prof->getTranslation('name', 'en', false) ?? '';
                        }) as $prof)
                            @php
                                $csName = $prof->getTranslation('name', 'cs', false);
                                $enName = $prof->getTranslation('name', 'en', false);
                                $displayName = $csName ?? '';
                                if (!empty($enName) && $csName != $enName) {
                                    $displayName .= !empty($displayName) ? ' / ' . $enName : $enName;
                                }
                                if (empty($displayName)) {
                                    $displayName = __('hiko.no_name');
                                }
                                
                                $hasIdentities = $prof->identities->count() > 0;
                                
                                // Check for global profession link
                                $hasGlobalLink = !empty($prof->global_profession_id);
                                $globalProfessionName = null;
                                
                                if ($hasGlobalLink) {
                                    $globalProfession = DB::table('global_professions')
                                        ->where('id', $prof->global_profession_id)
                                        ->first();
                                    
                                    if ($globalProfession) {
                                        $globalNameData = json_decode($globalProfession->name, true);
                                        $globalProfessionName = $globalNameData['cs'] ?? $globalNameData['en'] ?? null;
                                    }
                                }
                            @endphp
                            
                            <li>
                                <a href="{{ route('professions.edit', $prof->id) }}" 
                                   class="text-sm border-b {{ $hasIdentities ? 'text-primary-dark border-primary-light hover:border-primary-dark' : 'text-gray-500 border-gray-300 hover:border-gray-700' }}">
                                    {{ $displayName }}
                                    
                                    @if($hasGlobalLink && $globalProfessionName)
                                        <span class="text-xs text-blue-600 ml-1">(↔ {{ $globalProfessionName }})</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500 mt-2">{{ __('hiko.no_professions_in_category') }}</p>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
