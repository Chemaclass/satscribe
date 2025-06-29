<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
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
}
