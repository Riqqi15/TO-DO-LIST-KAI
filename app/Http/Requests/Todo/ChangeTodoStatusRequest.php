<?php

namespace App\Http\Requests\Todo;

use App\Domain\Todo\Enums\TodoStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeTodoStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TodoStatus::class)],
            'manual_reminder_at' => ['nullable', 'date'],
        ];
    }
}
