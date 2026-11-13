<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuditRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url:http,https', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'Bitte gib die Adresse einer Seite ein.',
            'url.url' => 'Das ist keine gültige Adresse. Sie muss mit http:// oder https:// beginnen.',
            'url.max' => 'Die Adresse darf höchstens :max Zeichen lang sein.',
        ];
    }
}
