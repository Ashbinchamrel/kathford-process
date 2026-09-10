<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Jobs\SendNotificationEmail;

class NotificationService
{
    public function send(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        bool $sendEmail = true,
    ): Notification {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'link'    => $link,
        ]);

        if ($sendEmail && $user->email) {
            SendNotificationEmail::dispatch($notification)
                ->onQueue('emails');
        }

        return $notification;
    }

    public function sendToMany(
        array $users,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
    ): void {
        foreach ($users as $user) {
            $this->send($user, $type, $title, $message, $link);
        }
    }
}
