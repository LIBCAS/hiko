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

    <!-- Profession Categories Section -->
    @can('manage-metadata')
        <div class="flex items-center space-x-4 mt-8">
            <x-create-link label="{{ __('hiko.new_professions_category') }}" link="{{ route('professions.category.create') }}" />
            @can('manage-users')
                <x-create-link label="{{ __('hiko.new_global_professions_category') }}" link="{{ route('global.profession.category.create') }}" />
            @endcan
        </div>
        <a href="{{ route('professions.category.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('hiko.export') }}
        </a>
    @endcan

    @cannot('manage-metadata')
        <p class="mt-16 font-bold">
            {{ __('hiko.professions_category') }}
        </p>
    @endcannot

    <livewire:profession-categories-table />
</x-app-layout>
