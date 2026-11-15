<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One notification class for every email. Subject and body come from the admin-editable
 * templates (Business Settings > Email templates), with {placeholders} replaced.
 * SMS / WhatsApp channels are deliberately not wired: nothing is "sent" without credentials.
 */
class TemplatedNotification extends Notification
{
    public function __construct(
        public string $event,
        public array $vars = [],
        public ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function fill(string $text): string
    {
        $vars = $this->vars + ['business' => Setting::get('business_name')];
        $map = [];
        foreach ($vars as $k => $v) {
            $map['{'.$k.'}'] = (string) $v;
        }

        return strtr($text, $map);
    }

    public function subject(): string
    {
        return $this->fill((string) Setting::get("email_{$this->event}_subject", 'Notification from {business}'));
    }

    public function body(): string
    {
        return $this->fill((string) Setting::get("email_{$this->event}_body", ''));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting('Hi '.explode(' ', trim((string) ($notifiable->name ?? 'there')))[0].',');

        foreach (preg_split('/\R/', $this->body()) as $line) {
            if (trim($line) !== '') {
                $mail->line($line);
            }
        }

        if ($this->url) {
            $mail->action('View details', $this->url);
        }

        return $mail->salutation('The '.Setting::get('business_name').' team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'subject' => $this->subject(),
            'message' => $this->body(),
            'url' => $this->url,
        ];
    }
}
