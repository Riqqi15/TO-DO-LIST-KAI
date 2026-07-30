<?php

namespace App\Domain\ActivityLog\Models;

use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['workspace_id', 'actor_id', 'action', 'subject_type', 'subject_id', 'snapshot', 'changes'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'changes' => 'array'];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Activity log bersifat permanen dan tidak dapat diubah.'));
        static::deleting(fn () => throw new \LogicException('Activity log bersifat permanen dan tidak dapat dihapus.'));
    }
}
