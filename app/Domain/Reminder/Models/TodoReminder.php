<?php

namespace App\Domain\Reminder\Models;

use App\Domain\Reminder\Enums\ReminderKind;
use App\Domain\Reminder\Enums\ReminderStatus;
use App\Domain\Todo\Models\Todo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TodoReminder extends Model
{
    protected $fillable = ['todo_id', 'kind', 'scheduled_at', 'status', 'cancelled_at'];

    protected function casts(): array
    {
        return ['kind' => ReminderKind::class, 'status' => ReminderStatus::class, 'scheduled_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function scopeManual(Builder $query): void
    {
        $query->where('kind', ReminderKind::Manual->value);
    }

    public function scopeAutomatic(Builder $query): void
    {
        $query->whereIn('kind', [ReminderKind::AutomaticSevenDays->value, ReminderKind::AutomaticThreeDays->value]);
    }

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ReminderDelivery::class, 'reminder_id');
    }
}
