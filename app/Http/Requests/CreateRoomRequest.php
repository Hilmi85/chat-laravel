<?php

namespace App\Http\Requests;

use App\Support\ChatText;
use Illuminate\Foundation\Http\FormRequest;

class CreateRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sanitize the name before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => ChatText::name((string) $this->input('name')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.(int) config('chat.max_name_length', 30)],
        ];
    }
}
