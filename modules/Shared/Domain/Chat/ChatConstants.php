<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Chat;

final class ChatConstants
{
    public const DEFAULT_USER_QUESTION = 'Give me a generic overview.';

    /**
     * Upper bound on any text a visitor contributes to a model prompt. It caps
     * what one request can cost and keeps a long paste from crowding out the
     * blockchain context the answer is supposed to be about.
     */
    public const MAX_QUESTION_LENGTH = 200;
}
