<div>
    <div class="space-y-6">
        <div>
            <x-label for="type" :value="__('hiko.type')" />
            <x-select wire:model="identityType" id="type" class="block w-full mt-1" name="type" required>
                @foreach ($types as $type)
                    <option value="{{ $type }}">
                        {{ __("hiko.{$type}") }}
                    </option>
                @endforeach
            </x-select>
            @error('type')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        @if ($identityType === 'person')
            <div class="space-y-6">
                <div class="required">
                    <x-label for="surname" :value="__('hiko.surname')" />
                    <x-input x-model="surname" id="surname" class="block w-full mt-1" type="text" name="surname"
                        x-on:change="fullName = surname + ' ' + forename" :value="old('surname', $identity->surname)"
                        required />
                    @error('surname')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <x-label for="forename" :value="__('hiko.forename')" />
                    <x-input x-model="forename" id="forename" class="block w-full mt-1" type="text" name="forename"
                        x-on:change="fullName = surname + ' ' + forename" :value="old('forename', $identity->forename)" />
                    @error('forename')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <x-label for="general_name_modifier" :value="__('hiko.general_name_modifier')" />
                    <x-input id="general_name_modifier" class="block w-full mt-1" type="text" name="general_name_modifier" :value="old('general_name_modifier', $identity->general_name_modifier)" />
                </div>
                <x-alert-similar-names />
                <livewire:related-names :related_names="$identity->related_names" />
                <div class="flex space-x-6">
                    <div>
                        <x-label for="birth_year" :value="__('hiko.birth_year')" />
                        <x-input id="birth_year" class="block w-full mt-1" type="text" name="birth_year"
                            :value="old('birth_year', $identity->birth_year)" />
                        @error('birth_year')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="death_year" :value="__('hiko.death_year')" />
                        <x-input id="death_year" class="block w-full mt-1" type="text" name="death_year"
                            :value="old('death_year', $identity->death_year)" />
                        @error('death_year')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div>
                    <x-label for="nationality" :value="__('hiko.nationality')" />
                    <x-input id="nationality" class="block w-full mt-1" type="text" name="nationality"
                        :value="old('nationality', $identity->nationality)" />
                    @error('nationality')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <x-label for="gender" :value="__('hiko.gender')" />
                    <x-input id="gender" class="block w-full mt-1" type="text" name="gender" :value="old('gender', $identity->gender)" />
                    @error('gender')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div class="space-y-6">
                    <livewire:repeated-select :items="$selectedProfessions" fieldLabel="{{ __('hiko.profession') }}"
                        fieldKey="profession" route="ajax.professions" />
                    @can('manage-metadata')
                        <livewire:create-new-item-modal :route="route('professions.create')" :text="__('hiko.modal_new_profession')" />
                    @endcan
                </div>
            </div>
        @else
            <div class="required">
                <x-label for="name" :value="__('hiko.title')" />
                <x-input x-model="name" x-on:change="fullName = name" id="name"
                    class="block w-full mt-1" type="text" name="name" :value="old('name', $identity->name)" required />
                @error('name')
                    <div class="text-red-600">{{ $message }}</div>
                @enderror
            </div>
            <x-alert-similar-names />
        @endif
    </div>
</div>

@push('scripts')
    <script>
        // Hide header and footer in modals
        function hideHeaderFooterInIframe() {
            var modals = document.querySelectorAll('.modal-window');
            modals.forEach(function(modal) {
                var iframe = modal.querySelector('iframe');
                if (iframe) {
                    var iframeContent = iframe.contentDocument || iframe.contentWindow.document;
                    var header = iframeContent.querySelector('header');
                    var footer = iframeContent.querySelector('footer');

                    if (header) {
                        header.style.display = 'none';
                    }

                    if (footer) {
                        footer.style.display = 'none';
                    }
                }
            });
        }

        // Function to handle iframe content load
        function handleIframeLoad() {
            hideHeaderFooterInIframe();
        }

        // Listen for iframe load event and handle it
        document.addEventListener('DOMContentLoaded', function() {
            var iframes = document.querySelectorAll('iframe');
            iframes.forEach(function(iframe) {
                iframe.addEventListener('load', handleIframeLoad);
            });
        });
    </script>
@endpush
