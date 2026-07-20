<x-app-layout>
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#2563EB]">Smart assistance</p>
        <h2 class="mt-2 text-3xl font-bold text-[#111827]">Reply and routing settings</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-[#6B7280]">Control tone, routing rules, automatic replies, and human takeover behavior.</p>
    </div>

    <div class="mb-6 rounded-xl border {{ $aiRuntimeEnabled && $aiProviderConfigured ? 'border-[#A7F3D0] bg-[#ECFDF5]' : 'border-[#FDE68A] bg-[#FFFBEB]' }} p-4">
        <p class="font-bold {{ $aiRuntimeEnabled && $aiProviderConfigured ? 'text-[#047857]' : 'text-[#92400E]' }}">
            {{ $aiRuntimeEnabled && $aiProviderConfigured ? 'AI runtime ready' : 'AI runtime safely paused' }}
        </p>
        <p class="mt-1 text-sm leading-6 {{ $aiRuntimeEnabled && $aiProviderConfigured ? 'text-[#047857]' : 'text-[#92400E]' }}">
            Provider: {{ ucfirst($aiProvider) }}.
            @if (! $aiProviderConfigured)
                Add the provider API key on the server before enabling live replies.
            @elseif (! $aiRuntimeEnabled)
                The provider is configured, but <code>AI_ENABLED</code> is off.
            @else
                Replies use the dedicated AI queue and require a positive workspace credit balance.
            @endif
        </p>
    </div>

    <form method="POST" action="{{ route('dashboard.ai-settings.update') }}" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        @csrf
        @method('PATCH')
        <section class="content-card space-y-6 p-5">
            <div>
                <h3 class="font-bold text-[#111827]">Assistant identity</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-semibold text-[#374151]">Assistant name
                        <input name="assistant_name" value="{{ old('assistant_name', $settings->assistant_name) }}" class="mt-2 w-full rounded-lg border-[#E5E7EB] bg-white text-[#111827] shadow-sm focus:border-[#2563EB] focus:ring-[#2563EB]">
                    </label>
                    <label class="block text-sm font-semibold text-[#374151]">Tone of voice
                        <input name="tone" value="{{ old('tone', $settings->tone) }}" class="mt-2 w-full rounded-lg border-[#E5E7EB] bg-white text-[#111827] shadow-sm focus:border-[#2563EB] focus:ring-[#2563EB]">
                    </label>
                </div>
            </div>

            <div class="grid gap-4">
                @foreach ([
                    'fallback_response' => ['Fallback response', 3],
                    'escalation_instructions' => ['Human takeover rules', 3],
                    'never_say' => ['Things assistance must never say', 3],
                    'handover_rules' => ['Business approval rules', 3],
                ] as $field => [$label, $rows])
                    <label class="block text-sm font-semibold text-[#374151]">{{ $label }}
                        <textarea name="{{ $field }}" rows="{{ $rows }}" class="mt-2 w-full rounded-lg border-[#E5E7EB] bg-white text-[#111827] shadow-sm focus:border-[#2563EB] focus:ring-[#2563EB]">{{ old($field, $settings->{$field}) }}</textarea>
                    </label>
                @endforeach
            </div>

            <div class="rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-bold text-[#111827]">Confidence threshold</p>
                        <p class="mt-1 text-sm text-[#6B7280]">Escalate when confidence drops below this level.</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-[#B45309]">65%</span>
                </div>
                <div class="mt-4 h-2 rounded-full bg-[#E5E7EB]">
                    <div class="h-2 w-[65%] rounded-full bg-[#F59E0B]"></div>
                </div>
            </div>

            <button class="rounded-lg bg-[#2563EB] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1d4ed8]">Save settings</button>
        </section>

        <aside class="content-card space-y-4 p-5">
            <h3 class="font-bold text-[#111827]">Auto-reply behavior</h3>
            @foreach ([
                'auto_reply_enabled' => 'Auto reply enabled',
                'human_takeover_enabled' => 'Human takeover enabled',
                'business_hours_enabled' => 'Business hours enabled',
            ] as $field => $label)
                <label class="flex items-center justify-between gap-4 rounded-xl bg-[#F5F6F8] px-4 py-3 text-sm font-semibold text-[#374151]">
                    <span>{{ $label }}</span>
                    <input type="checkbox" name="{{ $field }}" value="1" @checked($settings->{$field}) class="rounded border-[#D1D5DB] bg-white text-[#2563EB] focus:ring-[#2563EB]">
                </label>
            @endforeach

            <div class="rounded-xl border border-[#A7F3D0] bg-[#ECFDF5] p-4">
                <p class="font-bold text-[#047857]">Human judgment guardrail</p>
                <p class="mt-2 text-sm leading-6 text-[#047857]">Discounts, complaints, refunds, custom quotes, manager approvals, and unknown requests should escalate.</p>
            </div>
            <div class="rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-4">
                <p class="font-bold text-[#111827]">Business hours rule</p>
                <p class="mt-2 text-sm leading-6 text-[#6B7280]">When enabled, automatic replies run from 09:00 to 19:00 app time. Outside that window, new messages move to staff review.</p>
            </div>
        </aside>
    </form>
</x-app-layout>
