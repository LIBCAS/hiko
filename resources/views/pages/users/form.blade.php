<x-app-layout :title="$title">
    <x-success-alert />
    <form onkeydown="return event.key != 'Enter';" action="{{ $action }}" method="post" class="max-w-sm space-y-3"
        autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <div class="required">
            <x-label for="name" :value="__('Jméno')" />
            <x-input id="name" class="block w-full mt-1" type="text" name="name" :value="old('name', $user->name)"
                required />
            @error('name')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        @if ($editEmail)
            <div class="required">
                <x-label for="email" :value="__('E-mail')" />
                <x-input id="email" class="block w-full mt-1" type="email" name="email"
                    :value="old('email', $user->email)" required />
                @error('email')
                    <div class="text-red-600">{{ $message }}</div>
                @enderror
            </div>
        @endif
        <div class="required">
            <x-label for="role" :value="__('Role')" />
            <x-select id="role" class="block w-full mt-1" name="role" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->label }}"
                        {{ old('role', $user->role) == $role->label ? 'selected' : '' }}>
                        {{ $role->label }}
                    </option>
                @endforeach
            </x-select>
            @error('role')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        @if ($editStatus)
            <x-checkbox name="deactivated_at" label="{{ __('Aktivní uživatel') }}"
                :checked="old('deactivated_at') == 'on' || $active" />
        @endif
        <x-button-simple class="w-full">
            {{ $label }}
        </x-button-simple>
    </form>
    @if ($user->id)
        <form x-data="{ form: $el }" action="{{ route('users.destroy', $user->id) }}" method="post"
            class="max-w-sm mt-8">
            @csrf
            @method('DELETE')
            <x-button-danger class="w-full"
                x-on:click.prevent="if (confirm('Odstraní všechna data účtu! Pokračovat?')) form.submit()">
                {{ __('Odstranit účet') }}
            </x-button-danger>
        </form>
    @endif
</x-app-layout>
