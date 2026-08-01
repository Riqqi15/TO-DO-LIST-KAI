<?php

namespace App\Domain\StickyNote\Models;

use App\Domain\Todo\Models\Todo;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StickyNote extends Model
{
    protected $fillable = ['workspace_id', 'created_by', 'converted_to_todo_id', 'content', 'color', 'pinned_at', 'pin_order', 'converted_at'];

    protected function casts(): array
    {
        return ['pinned_at' => 'datetime', 'converted_at' => 'datetime'];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedTodo(): BelongsTo
    {
        return $this->belongsTo(Todo::class, 'converted_to_todo_id');
    }
}
