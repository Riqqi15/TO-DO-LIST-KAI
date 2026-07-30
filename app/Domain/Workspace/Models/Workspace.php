<?php

namespace App\Domain\Workspace\Models;

use App\Domain\Category\Models\Category;
use App\Domain\StickyNote\Models\StickyNote;
use App\Domain\Todo\Models\Todo;
use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Domain\Workspace\Enums\WorkspaceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    protected $fillable = ['created_by', 'name', 'type', 'member_limit'];

    protected function casts(): array
    {
        return ['type' => WorkspaceType::class, 'member_limit' => 'integer'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function membershipRows(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')->withPivot(['role', 'joined_at'])->withTimestamps();
    }

    public function invites(): HasMany
    {
        return $this->hasMany(TeamInvite::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function stickyNotes(): HasMany
    {
        return $this->hasMany(StickyNote::class);
    }

    public function isTeam(): bool
    {
        return $this->type === WorkspaceType::Team;
    }

    public function isPersonal(): bool
    {
        return $this->type === WorkspaceType::Personal;
    }

    public function hasMember(User $user): bool
    {
        return $this->membershipRows()->where('user_id', $user->id)->exists();
    }

    public function isOwner(User $user): bool
    {
        return $this->membershipRows()->where('user_id', $user->id)->where('role', WorkspaceRole::Owner->value)->exists();
    }
}
