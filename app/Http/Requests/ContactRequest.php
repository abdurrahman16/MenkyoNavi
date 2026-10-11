<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['name', 'email', 'subject', 'message'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $this->merge([
                    $field => trim(str_replace(["\r", "\0"], '', $this->string($field)->toString())),
                ]);
            }
        }

        if ($this->has('subject') && is_string($this->input('subject'))) {
            $this->merge([
                'subject' => str_replace("\n", ' ', $this->string('subject')->toString()),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
            // Honeypot — must remain empty. Bots that fill it are rejected.
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('pages.contact.validation.name_required'),
            'email.required' => __('pages.contact.validation.email_required'),
            'email.email' => __('pages.contact.validation.email_invalid'),
            'subject.required' => __('pages.contact.validation.subject_required'),
            'message.required' => __('pages.contact.validation.message_required'),
            'message.min' => __('pages.contact.validation.message_min'),
            'website.max' => __('pages.contact.validation.spam'),
        ];
    }
}
