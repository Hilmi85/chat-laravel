<?php

namespace App\Http\Requests;

use App\Support\ChatText;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sanitize the message before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'message' => ChatText::message((string) $this->input('message')),
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
            'message' => ['required', 'string', 'max:'.(int) config('chat.max_message_length', 500)],
        ];
    }
}
