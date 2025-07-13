<?php

declare(strict_types=1);

namespace Modules\NostrAuth\Infrastructure\Http\Controller;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Payment;
use Illuminate\View\View;

final readonly class ProfileController
{
    public function index(): View
    {
        $trackingId = tracking_id();

        return view('profile', [
            'pubkey' => nostr_pubkey(),
            'totalChats' => Chat::where('tracking_id', $trackingId)->count(),
            'totalMessages' => Message::whereHas('chat', static function ($q) use ($trackingId): void {
                $q->where('tracking_id', $trackingId);
            })->count(),
            'totalZaps' => Payment::where('status', 'SETTLED')
                ->where('tracking_id', $trackingId)
                ->sum('amount'),
        ]);
    }

    public function edit(): View
    {
        return view('profile-edit', [
            'pubkey' => nostr_pubkey(),
        ]);
    }
}
