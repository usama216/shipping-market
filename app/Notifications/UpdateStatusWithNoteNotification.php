<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpdateStatusWithNoteNotification extends Notification
{
    use Queueable;

    protected $note, $package;
    /**
     * Create a new notification instance.
     */
    public function __construct($note, $package)
    {
        $this->note = $note;
        $this->package = $package;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Note Updated for Package: {$this->package->package_id}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new note has been added to package **{$this->package->package_id}**. Your current status is **{$this->package->status_name}**")
            ->line('## Package Note')
            ->line("**Note:** {$this->note}")
            ->action('View Package', url(route('login')))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'package_id' => $this->package->id,
            'package_number' => $this->package->package_id,
            'note' => $this->note,
        ];
    }
}
