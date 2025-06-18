<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class HomeIndexRequest extends FormRequest
{
    public const DEFAULT_USER_QUESTION = 'Give me a generic overview.';

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable', 'string', function ($attribute, $value, $fail): void {
                    if (!preg_match('/^[a-f0-9]{64}$/i', $value) && !ctype_digit($value)) {
                        $fail('The '.$attribute.' must be a valid Bitcoin TXID or block height.');
                    }
                }
            ],
            'question' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function isSubmitted(): bool
    {
        return $this->boolean('submitted');
    }

    public function hasSearchInput(): bool
    {
        return $this->filled('search');
    }

    public function getSearchInput(): string
    {
        return strtolower(trim((string) $this->string('search')));
    }

    public function getQuestionInput(): string
    {
        return trim((string) $this->string('question'))
            ?: __(self::DEFAULT_USER_QUESTION);
    }

    public function getPersonaInput(): string
    {
        return (string)$this->string('persona', '');
    }

    public function isRefreshEnabled(): bool
    {
        return $this->boolean('refresh');
    }

    public function isPrivate(): bool
    {
        return $this->boolean('private');
    }
}
