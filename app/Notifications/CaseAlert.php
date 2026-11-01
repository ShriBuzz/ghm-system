<?php

namespace App\Notifications;

use App\Filament\Resources\Grievances\GrievanceResource;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells staff a case or a complainant message is waiting, with no grievance details:
 * only a link to sign in (N1). The queued job holds the case ID and nothing else.
 */
class CaseAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $grievanceId, public bool $message)
    {
        // The staff panel is in English (clarification question 14); APP_LOCALE is Nepali.
        $this->locale('en');
    }

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->message ? 'New message on a grievance' : 'New grievance received')
            ->line($this->message ? 'A complainant has sent a new message.' : 'A new grievance has been received.')
            ->line('Log in to view it.')
            ->action('Log in to view', GrievanceResource::getUrl('view', ['record' => $this->grievanceId], panel: 'admin'));
    }
}
