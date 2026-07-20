<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ai_settings')->orderBy('id')->each(function ($settings): void {
            $currentTone = strtolower(trim((string) $settings->tone));
            $tone = str_contains($currentTone, 'casual')
                ? 'casual'
                : (str_contains($currentTone, 'friendly') || str_contains($currentTone, 'warm') ? 'friendly' : 'professional');
            $escalation = collect([
                trim((string) $settings->escalation_instructions),
                trim((string) $settings->handover_rules),
            ])->filter()->unique()->implode("\n\n");

            $neverSay = trim((string) $settings->never_say);

            if ($neverSay !== '' && ! DB::table('business_rules')
                ->where('business_id', $settings->business_id)
                ->where('title', 'Restricted claims and promises')
                ->exists()) {
                DB::table('business_rules')->insert([
                    'business_id' => $settings->business_id,
                    'rule_type' => 'policy',
                    'title' => 'Restricted claims and promises',
                    'content' => $neverSay,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('ai_settings')->where('id', $settings->id)->update([
                'escalation_instructions' => $escalation !== '' ? $escalation : null,
                'tone' => $tone,
                'handover_rules' => null,
                'never_say' => null,
                'human_takeover_enabled' => true,
                'updated_at' => now(),
            ]);
        });

        DB::table('business_rules')
            ->whereIn('rule_type', ['handover', 'tone'])
            ->update(['rule_type' => 'policy', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Consolidated customer guidance cannot be separated reliably.
    }
};
