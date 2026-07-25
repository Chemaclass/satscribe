<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Chat;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Shared\Domain\Chat\ChatConstants;

use function is_string;

/**
 * Follow-up messages used to be read straight off the Request with no bound,
 * while the initial question has always been capped. Both feed the same model
 * prompt, so they get the same limit.
 */
final class AddMessageRequest extends FormRequest
{
    /**
     * A bring-your-own key must never survive the request that carried it.
     *
     * @var array<int, string>
     */
    protected $dontFlash = ['api_key'];

    /**
     * Laravel runs this before the rules, so someone else's chat is refused
     * without the validation errors revealing anything about it.
     */
    public function authorize(): bool
    {
        $chat = $this->route('chat');

        return $chat instanceof Chat && tracking_id() === $chat->tracking_id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:' . ChatConstants::MAX_QUESTION_LENGTH],
            'provider' => ['nullable', 'string', 'max:32'],
            'model' => ['nullable', 'string', 'max:128'],
            'api_key' => ['nullable', 'string', 'max:256'],
        ];
    }

    public function getMessageInput(): string
    {
        return trim((string) $this->string('message'));
    }

    protected function prepareForValidation(): void
    {
        $message = $this->input('message');

        if (is_string($message)) {
            $this->merge(['message' => trim($message)]);
        }
    }
}
