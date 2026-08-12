<?php

namespace App\Notifications\Todo;

use App\Domain\Todo\Models\Todo;
use App\Support\Wib;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TodoReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public Todo $todo) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $deadlineWib = Wib::format($this->todo->deadline_at, 'd M Y H:i').' WIB';

        return (new MailMessage)
            ->subject('Reminder task: '.$this->todo->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Task berikut mendekati deadline:')
            ->line($this->todo->title)
            ->line('Deadline: '.$deadlineWib)
            ->action('Buka To Do List', route('todo.index'))
            ->line('Email ini dikirim otomatis sesuai pengaturan reminder task.');
    }

    public function toArray(object $notifiable): array
    {
        return ['todo_id' => $this->todo->id, 'title' => $this->todo->title, 'deadline_at' => $this->todo->deadline_at->toIso8601String()];
    }
}
