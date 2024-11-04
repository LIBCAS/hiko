<x-app-layout :title="$title">
    <x-success-alert />

    <!-- Professions Section -->
    @can('manage-metadata')
        <div class="flex items-center space-x-4">
            <!-- Link for creating a local profession -->
            <x-create-link 
                label="{{ __('hiko.new_profession') }}" 
                link="{{ route('professions.create') }}" 
            />

            <!-- Conditional link for creating a global profession -->
            @can('manage-users')
                <x-create-link 
                    label="{{ __('hiko.new_global_profession') }}" 
                    link="{{ route('global.professions.create') }}" 
                />
            @endcan
        </div>

        <!-- Export link for professions -->
        <a href="{{ route('professions.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('hiko.export') }}
        </a>
    @endcan

    <!-- Table for displaying professions -->
    <livewire:professions-table />

    <!-- Section for when the user cannot manage metadata -->
    @cannot('manage-metadata')
        <p class="mt-16 font-bold">
            {{ __('hiko.professions_category') }}
        </p>
    @endcannot

    <!-- Table for displaying profession categories -->
    <livewire:profession-categories-table />
</x-app-layout>
