<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\HtmlString;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewUserPasswordCreate extends Notification
{
    use Queueable;

    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => app('auth.password.broker')->createToken($this->user),
            'email' => $this->user->email,
        ], false));

        $passwordRequest = route('password.request');

        return (new MailMessage)
            ->subject(__('hiko.new_account'))
            ->line(__('hiko.new_account_created', ['name' => config('app.name')]))
            ->action(__('hiko.set_password'), $url)
            ->line(__('hiko.password_expiration', ['count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire')]))
            ->line(__('hiko.password_expired'))
            ->line(new HtmlString("<a href='{$passwordRequest}'>{$passwordRequest}</a>"));
    }

    public function toArray(): array
    {
        return [];
    }
}
