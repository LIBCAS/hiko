<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Lang;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewUserPasswordCreate extends Notification
{
    use Queueable;

    protected $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail()
    {
        $token = app('auth.password.broker')->createToken($this->user);

        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $this->user->email,
        ], false));

        return (new MailMessage)
            ->subject(Lang::get('Nový účet'))
            ->line(Lang::get('Právě vám byl založený účet v aplikaci :name', ['name' => config('app.name')]))
            ->action(Lang::get('Nastavit heslo'), $url)
            ->line(Lang::get('Tento odkaz vyprší za :count minut.', ['count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire')]))
            ->line(Lang::get('Pokud už odkaz vypršel, můžete o nové heslo zažádat na následující adrese:'))
            ->line(route('password.request'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
