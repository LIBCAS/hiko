<div class="md:flex ">
    <div class="flex-1 px-3 py-6 border border-primary-light">
        @error('images')
            <p class="text-red-600">{{ $message }}</p>
        @enderror
        @error('images.*')
            <p class="text-red-600">{{ $message }}</p>
        @enderror
        <form wire:submit.prevent="save" class="max-w-sm">
            <div wire:ignore
                x-data="{ pond: null }"
                x-on:remove-images.window="pond.removeFiles();"
                x-init="
                FilePond.setOptions({
                    server: {
                        process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
                            @this.upload('images', file, load, error, progress)
                        },
                        revert: (filename, load) => {
                            @this.removeUpload('images', filename, load)
                        },
                    },
                });
                pond = FilePond.create($refs.input);
            ">
                <input wire:ignore wire:model="images" type="file" x-ref="input" multiple data-allow-reorder="true"
                    data-max-file-size="500KB" accept="image/png, image/jpeg">

                <x-button-simple class="w-full">
                    {{ __('Uložit') }}
                </x-button-simple>
            </div>
        </form>
    </div>
    <div class="flex-1 px-3 py-6 border border-primary-light">
        <div class="space-y-3">
            @foreach ([] as $image)
                <img src="{{ $image->getUrl('thumb') }}" alt="" class="block border">
            @endforeach
        </div>
    </div>
    @push('scripts')
        <script>
            Livewire.on('pondReset', data => {
                document.getElementById('latitude').value = data.latitude;
                document.getElementById('longitude').value = data.longitude;
                document.getElementById('geoname_id').value = data.id;
            })
        </script>
    @endpush
</div>
