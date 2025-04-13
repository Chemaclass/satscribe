<?php

namespace App\Http\Controllers;

use App\Services\BlockchainService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;

final class DescribeController extends Controller
{
    public function index()
    {
        return view('describe');
    }

    public function describe(Request $request, BlockchainService $btc, OpenAIService $ai)
    {
        $input = trim($request->input('input'));
        $type = is_numeric($input) ? 'block' : 'transaction';
        $data = $btc->getData($input);

        if (!$data) {
            return back()->withErrors(['input' => 'Could not fetch blockchain data.']);
        }

        $description = $ai->generateDescription($data, $type);

        return view('describe', compact('description', 'data'));
    }
}
