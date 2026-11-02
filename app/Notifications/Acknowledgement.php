<?php

namespace App\Notifications;

use App\Models\Grievance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a complainant who gave an email that their case was received, with its
 * reference, PIN and where to check them (N2, P7). Never anything they wrote.
 * Encrypted on the queue, because the job carries the PIN.
 */
class Acknowledgement extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(private string $pin) {}

    /**
     * @return list<string>
     */
    public function via(Grievance $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(Grievance $notifiable): MailMessage
    {
        $site = route('home', ['locale' => $notifiable->locale]);

        return (new MailMessage)
            ->subject(__('acknowledgement.subject'))
            ->greeting(__('acknowledgement.greeting'))
            ->line(__('acknowledgement.reference', ['reference' => $notifiable->reference]))
            ->line(__('acknowledgement.pin', ['pin' => $this->pin]))
            ->line(__('acknowledgement.check', ['url' => "[{$site}]({$site})"]))
            ->salutation(__('acknowledgement.salutation'));
    }
}
