<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\QuestionPlaceholder;
use App\Enums\PromptPersona;
use App\Services\BlockHeightProvider;
use Illuminate\View\View;

final readonly class HomeController
{
    public function __construct(
        private BlockHeightProvider $heightProvider,
    ) {
    }

    public function index(): View
    {
        return view('home', [
            'questionPlaceholder' => QuestionPlaceholder::rand(),
            'suggestedPromptsGrouped' => QuestionPlaceholder::groupedPrompts(),
            'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
            'personaDescriptions' => PromptPersona::descriptions()->toJson(),
        ]);
    }
}
