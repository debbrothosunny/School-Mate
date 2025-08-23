<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class StudentPaymentReceived extends Notification implements ShouldBroadcast
{
    use Queueable;

    public $studentName;
    public $amount;

    /**
     * Create a new notification instance.
     *
     * @param string $studentName The name of the student who made the payment.
     * @param float $amount The payment amount.
     * @return void
     */
    public function __construct(string $studentName, float $amount)
    {
        $this->studentName = $studentName;
        $this->amount = $amount;
    }

    /**
     * Get the notification's delivery channels.
     * We'll use 'database' to store it and 'broadcast' for real-time display.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     * This data is stored in the 'data' column of the notifications table.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->studentName} has made a payment of {$this->amount}.",
            'student_name' => $this->studentName,
            'amount' => $this->amount,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     * This is the payload that is sent to the client via Pusher.
     *
     * @return BroadcastMessage
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => "{$this->studentName} has made a payment of {$this->amount}.",
            'student_name' => $this->studentName,
            'amount' => $this->amount,
        ]);
    }
}