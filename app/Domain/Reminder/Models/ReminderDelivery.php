<?php

namespace App\Domain\Reminder\Models;

use App\Domain\Reminder\Enums\DeliveryStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderDelivery extends Model
{
    protected $fillable = ['reminder_id', 'user_id', 'status', 'attempts', 'sent_at', 'failed_at', 'last_error'];

    protected function casts(): array
    {
        return ['status' => DeliveryStatus::class, 'sent_at' => 'datetime', 'failed_at' => 'datetime'];
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(TodoReminder::class, 'reminder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
