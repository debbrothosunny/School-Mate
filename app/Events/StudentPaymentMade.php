<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class StudentPaymentMade implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $paymentId;
    public $amount;
    public $studentName;
    public $accountantId;

    /**
     * Create a new event instance.
     *
     * @param int $paymentId The ID of the new payment.
     * @param float $amount The payment amount.
     * @param string $studentName The name of the student who made the payment.
     * @param int $accountantId The ID of the accountant to notify.
     * @return void
     */
    public function __construct(int $paymentId, float $amount, string $studentName, int $accountantId)
    {
        $this->paymentId = $paymentId;
        $this->amount = $amount;
        $this->studentName = $studentName;
        $this->accountantId = $accountantId;
    }

    /**
     * Get the channels the event should broadcast on.
     * This is the crucial part that ensures the event is sent to the correct user.
     *
     * @return \Illuminate\Broadcasting\Channel|array
    */
    public function broadcastOn()
    {
        // The channel name MUST match the one in your Vue.js code
        return new PrivateChannel('private.accountant.' . $this->accountantId);
    }

    /**
     * The event's broadcast name.
     * This is the event name that your Vue.js listener is waiting for.
     *
     * @return string
    */
    public function broadcastAs()
    {
        return 'student.payment.made';
    }
}
