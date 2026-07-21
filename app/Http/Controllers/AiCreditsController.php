<?php

namespace App\Http\Controllers;

use App\Models\AiCreditTransaction;
use App\Models\AiCreditWallet;
use App\Models\AiUsageRecord;
use Illuminate\Http\Request;

class AiCreditsController extends Controller
{
    public function __invoke(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $wallet = AiCreditWallet::firstOrCreate(['business_id' => $business->id]);
        $periodStart = now()->startOfMonth();
        $month = AiUsageRecord::where('business_id', $business->id)
            ->where('created_at', '>=', $periodStart)
            ->selectRaw('COALESCE(SUM(input_tokens), 0) + COALESCE(SUM(output_tokens), 0) as tokens')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as replies")
            ->selectRaw('COALESCE(SUM(credits_used), 0) as credits')
            ->first();

        return view('dashboard.ai-credits', [
            'wallet' => $wallet,
            'usage' => AiUsageRecord::where('business_id', $business->id)->latest()->paginate(15, ['*'], 'usage_page'),
            'transactions' => AiCreditTransaction::where('business_id', $business->id)->latest()->limit(20)->get(),
            'monthTokens' => (int) $month->tokens,
            'monthReplies' => (int) $month->replies,
            'monthCredits' => (int) $month->credits,
        ]);
    }
}
