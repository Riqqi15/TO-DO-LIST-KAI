<?php

namespace App\Domain\Todo\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoNote extends Model
{
    use HasFactory;

    protected $fillable = ['todo_id', 'created_by', 'body'];

    public function todo()
    {
        return $this->belongsTo(Todo::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
