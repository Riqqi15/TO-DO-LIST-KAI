<?php

namespace App\Http\Requests\StickyNote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStickyNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['content' => ['required', 'string', 'max:5000'], 'color' => ['sometimes', Rule::in(['yellow', 'blue', 'green', 'pink', 'purple'])]];
    }
}
