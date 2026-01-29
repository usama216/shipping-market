<?php

namespace App\Notifications;

use App\Helpers\PackageStatus;
use App\Models\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a package status changes.
 */
class PackageStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Package $package,
        public string $oldStatus
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusName = $this->getStatusName($this->package->status);
        $statusMessage = $this->getStatusMessage($this->package->status);

        return (new MailMessage)
            ->subject("📋 Package Status Update - {$this->package->package_id}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your package status has been updated.")
            ->line('## Status Information')
            ->line("**Package ID:** {$this->package->package_id}")
            ->line("**New Status:** {$statusName}")
            ->line($statusMessage)
            ->action('View Package', url(route('customer.dashboard')))
            ->line('Log in to your dashboard to view more details.');
    }

    /**
     * Get the array representation of the notification (for database).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'package_status_changed',
            'package_id' => $this->package->id,
            'package_number' => $this->package->package_id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->getStatusName($this->package->status),
            'message' => "Package {$this->package->package_id} status changed to {$this->getStatusName($this->package->status)}",
        ];
    }

    /**
     * Get human-readable status name.
     */
    private function getStatusName(int $status): string
    {
        return match ($status) {
            PackageStatus::ACTION_REQUIRED => 'Action Required',
            PackageStatus::IN_REVIEW => 'In Review',
            PackageStatus::READY_TO_SEND => 'Ready to Send',
            PackageStatus::CONSOLIDATE => 'Consolidated',
            default => 'Unknown',
        };
    }

    /**
     * Get contextual message based on status.
     */
    private function getStatusMessage(int $status): string
    {
        return match ($status) {
            PackageStatus::ACTION_REQUIRED => 'Please log in to provide additional information or upload required documents.',
            PackageStatus::IN_REVIEW => 'Our team is reviewing your package. No action is required from you at this time.',
            PackageStatus::READY_TO_SEND => 'Your package is ready to be shipped! You can now create a shipment request.',
            PackageStatus::CONSOLIDATE => 'Your package has been marked for consolidation with other packages.',
            default => 'Your package status has been updated.',
        };
    }
}
