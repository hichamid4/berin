<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class MobileLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * device_id is a random identifier the app generates once per installation; signing in again
     * on the same device replaces that device's previous token instead of piling up new ones.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'max:255'],
            'device_id' => ['required', 'string', 'min:8', 'max:64', 'regex:/^[A-Za-z0-9-]+$/'],
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }
}
