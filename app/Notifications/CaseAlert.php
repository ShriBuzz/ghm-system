<?php

namespace App\Notifications;

use App\Enums\AlertKind;
use App\Filament\Resources\Grievances\GrievanceResource;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells staff a case needs them, and why, with no grievance details: only a link to sign in
 * (N1, U6). The queued job holds the case ID and the kind of alert, nothing else.
 */
class CaseAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $grievanceId, public AlertKind $kind)
    {
        // The staff panel is in English; APP_LOCALE is Nepali.
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
        [$subject, $line] = match ($this->kind) {
            AlertKind::NewCase => ['New grievance received', 'A new grievance has been received.'],
            AlertKind::Restricted => ['Grievance marked restricted', 'A grievance has been marked restricted. Only restricted-case officers and the Administrator can now see it.'],
            AlertKind::Message => ['New message on a grievance', 'A complainant has sent a new message.'],
            AlertKind::Critical => ['Grievance set to Critical', 'A grievance has been set to Critical priority. It needs action today.'],
            AlertKind::PastTarget => ['Grievance past its target', 'An open grievance has passed its target time without being resolved.'],
        };

        return (new MailMessage)
            ->subject($subject)
            ->line($line)
            ->action('Log in to view', GrievanceResource::getUrl('view', ['record' => $this->grievanceId], panel: 'admin'));
    }
}
