<?php

namespace App\Notifications;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShipmentStatusUpdated extends Notification
{
    public function __construct(public Shipment $shipment, public ShipmentStatus $status)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];   // MAIL_MAILER=log writes the email to storage/logs/laravel.log
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Shipment {$this->shipment->tracking_number}: {$this->status->label()}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your shipment to {$this->shipment->receiver_name} ({$this->shipment->receiver_city}) is now: {$this->status->label()}.")
            ->line('Thank you for shipping with us.');
    }
}
