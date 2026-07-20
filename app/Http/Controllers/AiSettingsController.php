<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\AutomationLog;
use Illuminate\Http\Request;

class AiSettingsController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');

        return view('dashboard.ai-settings', [
            'settings' => AiSetting::firstOrCreate(['business_id' => $business->id]),
            'aiRuntimeEnabled' => (bool) config('ai.enabled'),
            'aiProvider' => (string) config('ai.provider'),
            'aiProviderConfigured' => filled(config('ai.providers.'.config('ai.provider').'.api_key')),
        ]);
    }

    public function update(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $validated = $request->validate([
            'assistant_name' => ['required', 'string', 'max:120'],
            'tone' => ['required', 'string', 'max:80'],
            'fallback_response' => ['nullable', 'string', 'max:4000'],
            'escalation_instructions' => ['nullable', 'string', 'max:4000'],
            'never_say' => ['nullable', 'string', 'max:4000'],
            'handover_rules' => ['nullable', 'string', 'max:4000'],
            'auto_reply_enabled' => ['nullable', 'boolean'],
            'human_takeover_enabled' => ['nullable', 'boolean'],
            'business_hours_enabled' => ['nullable', 'boolean'],
        ]);

        $settings = AiSetting::firstOrCreate(['business_id' => $business->id]);
        $settings->update([
            ...$validated,
            'auto_reply_enabled' => $request->boolean('auto_reply_enabled'),
            'human_takeover_enabled' => $request->boolean('human_takeover_enabled'),
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
