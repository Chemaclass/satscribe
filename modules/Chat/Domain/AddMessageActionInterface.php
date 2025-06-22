<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use App\Models\Chat;

interface AddMessageActionInterface
{
    public function execute(Chat $chat, string $message): void;
}
