<?php

namespace App\Domain\Todo\Models;

use App\Domain\Category\Models\Category;
use App\Domain\Reminder\Models\TodoReminder;
use App\Domain\Todo\Enums\TodoStatus;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Todo extends Model
{
    protected $fillable = ['workspace_id', 'created_by', 'category_id', 'title', 'description', 'status', 'deadline_at'];

    protected function casts(): array
    {
        return ['status' => TodoStatus::class, 'deadline_at' => 'datetime'];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(TodoReminder::class);
    }
}
