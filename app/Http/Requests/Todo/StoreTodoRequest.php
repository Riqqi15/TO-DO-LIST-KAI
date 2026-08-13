<?php

namespace App\Http\Requests\Todo;

use Illuminate\Foundation\Http\FormRequest;

class StoreTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'        => ['required', 'integer', 'exists:categories,id'],
            'title'              => ['required', 'string', 'max:180'],
            'description'        => ['nullable', 'string', 'max:5000'],
            'start_date'         => ['nullable', 'date', 'before_or_equal:deadline_at'],
            'deadline_at'        => ['required', 'date'],
            'manual_reminders'   => ['sometimes', 'array', 'max:10'],
            'manual_reminders.*' => ['required', 'date', 'distinct'],
        ];
    }
}
