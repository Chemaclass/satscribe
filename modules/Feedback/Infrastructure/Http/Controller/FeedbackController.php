<?php

declare(strict_types=1);

namespace Modules\Feedback\Infrastructure\Http\Controller;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class FeedbackController
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string'],
            'captcha_answer' => ['required', 'integer'],
            'captcha_sum' => ['required', 'integer'],
        ]);

        if ((int) $validated['captcha_answer'] !== (int) $validated['captcha_sum']) {
            return response()->json(['error' => __('Invalid captcha answer.')], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Feedback::create([
            'nickname' => $validated['nickname'],
            'email' => $validated['email'],
            'message' => $validated['message'],
        ]);

        return response()->json([], Response::HTTP_CREATED);
    }
}
