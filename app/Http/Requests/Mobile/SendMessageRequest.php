<?php

namespace App\Http\Requests\Mobile;

use App\Models\Message;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Only the text and the client's idempotency key are accepted. Sender, recipient and
 * conversation membership are derived on the server from the token and the URL.
 */
class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.Message::MAX_BODY_LENGTH],
            'client_message_id' => ['required', 'string', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Write a message before sending.',
            'body.max' => 'Messages can be at most '.Message::MAX_BODY_LENGTH.' characters long.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('body'))) {
            $this->merge(['body' => trim(str_replace(["\r\n", "\r"], "\n", $this->input('body')))]);
        }

        if (is_string($this->input('client_message_id'))) {
            $this->merge(['client_message_id' => strtolower($this->input('client_message_id'))]);
        }
    }
}
