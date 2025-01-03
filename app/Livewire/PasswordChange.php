<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class PasswordChange extends Component
{
    public $currentPassword;
    public $newPassword;
    public $newPasswordConfirm;
    public $validationAttributes;
    public $messages = [];

    public function rules()
    {
        return [
            'currentPassword' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, auth()->user()->password)) {
                        return $fail(__('validation.current_password'));
                    }
                },
            ],
            'newPassword' => ['required', Password::defaults()],
            'newPasswordConfirm' => 'required|same:newPassword',
        ];
    }

    public function save()
    {
        $this->validate();
        auth()->user()->password = Hash::make($this->newPassword);
        auth()->user()->save();
        $this->dispatch('saved');
    }

    public function render()
    {
        return view('livewire.password-change');
    }

    public function mount()
    {
        $this->validationAttributes = [
            'currentPassword' => __('hiko.current_password'),
            'newPassword' => __('hiko.new_password'),
            'newPasswordConfirm' => __('hiko.confirm_new_password'),
        ];

        $this->messages = [
            'newPasswordConfirm.same' => __('hiko.confirm_new_password_error'),
        ];
    }
}
