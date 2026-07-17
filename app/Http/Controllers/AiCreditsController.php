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

        return view('dashboard.ai-credits', [
            'wallet' => $wallet,
            'usage' => AiUsageRecord::where('business_id', $business->id)->latest()->paginate(15, ['*'], 'usage_page'),
            'transactions' => AiCreditTransaction::where('business_id', $business->id)->latest()->limit(20)->get(),
            'monthTokens' => AiUsageRecord::where('business_id', $business->id)->where('created_at', '>=', $periodStart)->sum('input_tokens')
                + AiUsageRecord::where('business_id', $business->id)->where('created_at', '>=', $periodStart)->sum('output_tokens'),
            'monthReplies' => AiUsageRecord::where('business_id', $business->id)->where('created_at', '>=', $periodStart)->where('status', 'completed')->count(),
            'monthCredits' => AiUsageRecord::where('business_id', $business->id)->where('created_at', '>=', $periodStart)->sum('credits_used'),
        ]);
    }
}
