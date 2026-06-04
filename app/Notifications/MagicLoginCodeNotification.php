<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLoginCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Tenant $tenant,
        private readonly string $code,
        private readonly int $expiresInMinutes = 10,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Seu código de acesso RestauraAí')
            ->greeting('Acesso rápido em '.$this->tenant->name)
            ->line('Use o código abaixo para entrar sem senha:')
            ->line($this->code)
            ->line('Este código expira em '.$this->expiresInMinutes.' minutos.')
            ->line('Se você não solicitou este acesso, ignore esta mensagem.');
    }
}
