<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreContactMessageRequest extends FormRequest
{
    /**
     * Name of the hidden field that real visitors never see or fill in.
     */
    public const HONEYPOT_FIELD = 'website';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish($this->string('name')->toString()),
            'email' => Str::lower(trim($this->string('email')->toString())),
            'subject' => Str::squish($this->string('subject')->toString()),
            'message' => trim(str_replace("\r\n", "\n", $this->string('message')->toString())),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'subject' => ['required', 'string', 'min:3', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please tell us your name.',
            'name.max' => 'Your name may not be longer than 100 characters.',
            'email.required' => 'Please enter the email address we should reply to.',
            'email.email' => 'Please enter a valid email address, for example name@example.com.',
            'email.max' => 'Your email address may not be longer than 254 characters.',
            'subject.required' => 'Please add a subject so we can route your message.',
            'subject.min' => 'The subject must be at least 3 characters.',
            'subject.max' => 'The subject may not be longer than 150 characters.',
            'message.required' => 'Please write your message.',
            'message.min' => 'Your message must be at least 10 characters so we can understand your request.',
            'message.max' => 'Your message may not be longer than 5,000 characters.',
        ];
    }

    public function isLikelySpam(): bool
    {
        return $this->filled(self::HONEYPOT_FIELD);
    }
}
