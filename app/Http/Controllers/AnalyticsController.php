<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $since = now()->subDays(30);

        return view('dashboard.analytics', [
            'conversationCount' => Conversation::where('business_id', $business->id)->where('created_at', '>=', $since)->count(),
            'messageCount' => Message::where('business_id', $business->id)->where('created_at', '>=', $since)->count(),
            'inboundCount' => Message::where('business_id', $business->id)->where('direction', 'inbound')->where('created_at', '>=', $since)->count(),
            'channelBreakdown' => Conversation::where('business_id', $business->id)->where('created_at', '>=', $since)
                ->select('channel', DB::raw('COUNT(*) as total'))->groupBy('channel')->orderByDesc('total')->get(),
        ]);
    }
}
