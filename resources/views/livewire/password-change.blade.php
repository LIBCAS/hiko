<div>
    <form wire:submit="save" class="max-w-sm space-y-3">
        @csrf
        <legend class="font-semibold">{{ __('hiko.change_password') }}</legend>
        <div class="">
            <x-label for="current-password" value="{{ __('hiko.current_password') }}" />
            <x-input wire:model="currentPassword" id="current-password" class="block w-full mt-1" type="password"
                autocomplete="current-password" />
        </div>
        <div class="">
            <x-label for="new-password" value="{{ __('hiko.new_password') }}" />
            <x-input wire:model="newPassword" id="new-password" class="block w-full mt-1" type="password"
                autocomplete="new-password" />
        </div>
        <div class="">
            <x-label for="new-password-confirm" value="{{ __('hiko.confirm_new_password') }}" />
            <x-input wire:model="newPasswordConfirm" id="new-password-confirm" class="block w-full mt-1" type="password"
                autocomplete="new-password" />
        </div>
        <x-button-simple class="w-full">
            {{ __('hiko.save') }}
        </x-button-simple>
        @if ($errors->any())
            <ul class="text-sm text-red-700">
                {!! implode('', $errors->all('<li>:message</li>')) !!}
            </ul>
        @endif
        <div x-data="{ shown: false, timeout: null }"
            x-init="@this.on('saved', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, 2000); })"
            x-show.transition.opacity.out.duration.1500ms="shown" style="display: none;">
            <p class="text-sm text-green-700">
                {{ __('hiko.saved') }}
            </p>
        </div>
    </form>
</div>
