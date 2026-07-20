<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\AutomationLog;
use App\Models\BusinessRule;
use App\Models\ConnectedAccount;
use App\Models\Faq;
use App\Models\Product;
use App\Models\SavedReply;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiSettingsController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $creditBalance = (int) ($business->aiCreditWallet?->balance ?? 0);
        $connectedChannels = ConnectedAccount::where('business_id', $business->id)
            ->where('status', 'connected')
            ->count();

        return view('dashboard.ai-settings', [
            'settings' => AiSetting::firstOrCreate(['business_id' => $business->id]),
            'aiRuntimeEnabled' => (bool) config('ai.enabled'),
            'aiProvider' => (string) config('ai.provider'),
            'aiProviderConfigured' => filled(config('ai.providers.'.config('ai.provider').'.api_key')),
            'creditBalance' => $creditBalance,
            'connectedChannels' => $connectedChannels,
            'knowledgeCounts' => [
                'faqs' => Faq::where('business_id', $business->id)->count(),
                'products' => Product::where('business_id', $business->id)->count(),
                'rules' => BusinessRule::where('business_id', $business->id)->count(),
                'savedReplies' => SavedReply::where('business_id', $business->id)->count(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $validated = $request->validate([
            'assistant_name' => ['required', 'string', 'max:120'],
            'tone' => ['required', Rule::in(['friendly', 'professional', 'casual'])],
            'fallback_response' => ['nullable', 'string', 'max:4000'],
            'escalation_instructions' => ['nullable', 'string', 'max:4000'],
            'auto_reply_enabled' => ['nullable', 'boolean'],
            'business_hours_enabled' => ['nullable', 'boolean'],
        ]);

        $settings = AiSetting::firstOrCreate(['business_id' => $business->id]);
        $settings->update([
            ...$validated,
            'auto_reply_enabled' => $request->boolean('auto_reply_enabled'),
            'human_takeover_enabled' => true,
            'business_hours_enabled' => $request->boolean('business_hours_enabled'),
        ]);

        AutomationLog::create([
            'business_id' => $business->id,
            'event_type' => 'ai_settings_changed',
            'status' => 'success',
            'message' => 'AI settings were updated.',
        ]);

        return back()->with('status', 'AI settings saved.');
    }
}
