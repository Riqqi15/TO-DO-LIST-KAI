<?php

namespace App\Http\Requests\StickyNote;

use Illuminate\Foundation\Http\FormRequest;

class ReorderPinnedStickyNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note_ids' => ['required', 'array'],
            'note_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
