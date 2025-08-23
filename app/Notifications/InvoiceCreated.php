<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Models\Invoice;
use App\Models\Student;

class InvoiceCreated extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public Invoice $invoice;

    /**
     * Create a new notification instance.
     *
     * @param Invoice $invoice The invoice model instance
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['broadcast', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('A new invoice has been created for you.')
            ->action('View Invoice', route('student.invoices.show', $this->invoice->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable)
    {
        // We are now sending a more descriptive message including the invoice number.
        $message = "Your new invoice ({$this->invoice->invoice_number}) for {$this->invoice->billing_period} has been created.";

        return new BroadcastMessage([
            'invoice_id' => $this->invoice->id,
            'invoice_name' => $this->invoice->invoice_number, // Add this line
            'amount' => $this->invoice->total_amount_due, // It's better to send total amount due
            'due_date' => $this->invoice->due_date,
            'message' => $message,
            'created_at' => now()->diffForHumans(),
        ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        // Store the same descriptive message and invoice number in the database
        $message = "Your new invoice ({$this->invoice->invoice_number}) for {$this->invoice->billing_period} has been created.";

        return [
            'invoice_id' => $this->invoice->id,
            'invoice_name' => $this->invoice->invoice_number, // Add this line
            'message' => $message,
            'created_at' => now(),
        ];
    }
}
