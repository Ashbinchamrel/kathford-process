<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Notification $notification) {}

    public function handle(): void
    {
        $user = $this->notification->user;

        if (! $user || ! $user->email) return;

        Mail::send('emails.notification', ['notification' => $this->notification], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                    ->subject('[Kathford Process] ' . $this->notification->title);
        });
    }
}
