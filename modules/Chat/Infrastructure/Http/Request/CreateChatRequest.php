<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Shared\Domain\Chat\ChatConstants;

final class CreateChatRequest extends FormRequest
{
    public const DEFAULT_USER_QUESTION = ChatConstants::DEFAULT_USER_QUESTION;

    /**
     * A bring-your-own key must never survive the request that carried it —
     * not even in the session that a failed validation flashes input into.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'api_key',
    ];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable', 'string', static function ($attribute, $value, $fail): void {
                    if (!preg_match('/^[a-f0-9]{64}$/i', $value) && !ctype_digit($value)) {
                        $fail('The ' . $attribute . ' must be a valid Bitcoin TXID or block height.');
                    }
                },
            ],
            'question' => ['nullable', 'string', 'max:200'],
            // Values are checked against the provider registry, which is the
            // allowlist; these rules only bound the raw shape.
            'provider' => ['nullable', 'string', 'max:32'],
            'model' => ['nullable', 'string', 'max:128'],
            'api_key' => ['nullable', 'string', 'max:256'],
        ];
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
        return (string) $this->string('persona', '');
    }

    public function getProviderInput(): string
    {
        return trim((string) $this->string('provider', ''));
    }

    public function getModelInput(): string
    {
        return trim((string) $this->string('model', ''));
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
