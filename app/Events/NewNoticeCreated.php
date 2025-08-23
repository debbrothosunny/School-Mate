<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Notice; // Make sure to import your Notice model

class NewNoticeCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notice;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\Notice  $notice
     * @return void
     */
    public function __construct(Notice $notice)
    {
        // Eager load the className relationship if you want to send it with the broadcast.
        // Based on our previous conversation, if you want class name in real-time notices,
        // you'd need to link Notice to ClassName in your backend.
        // For now, it loads it, but if Notice doesn't have className, it will be null.
        $this->notice = $notice->load('className');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // This is a public channel named 'notices'.
        // Any user listening to this channel will receive the event.
        return [
            new Channel('notices'),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        // This is the name the event will be broadcast as on the channel.
        // Your frontend (Laravel Echo) will listen for '.new.notice'.
        return 'new.notice';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'notice' => [
                'id' => $this->notice->id,
                'title' => $this->notice->title,
                'description' => $this->notice->content, // Map content to description
                'created_at' => $this->notice->created_at,
                // Include other fields you need
            ]
        ];
    }
}
