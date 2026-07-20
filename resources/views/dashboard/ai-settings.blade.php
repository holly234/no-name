<x-app-layout>
    @php
        $ready = $aiRuntimeEnabled && $aiProviderConfigured && $creditBalance > 0 && $connectedChannels > 0;
        $toneOptions = ['friendly' => 'Friendly', 'professional' => 'Professional', 'casual' => 'Casual'];
        $knowledgeCards = [
            ['faqs', 'Questions & answers', 'Common questions specific to your business.', $knowledgeCounts['faqs']],
            ['products', 'Products & services', 'Your offers, prices, and availability.', $knowledgeCounts['products']],
            ['rules', 'Business policies', 'Limits and policies it must follow.', $knowledgeCounts['rules']],
        ];
    @endphp

    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#2563EB]">AI Assistant</p>
        <h2 class="mt-2 text-3xl font-bold text-[#111827]">Ready without the homework</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-[#6B7280]">Turn it on and it can handle greetings, general questions, and everyday conversations immediately. Add business information only when you want more precise company-specific answers.</p>
    </div>

    <div class="mb-6 rounded-xl border {{ $ready ? 'border-[#A7F3D0] bg-[#ECFDF5]' : 'border-[#FDE68A] bg-[#FFFBEB]' }} p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-bold {{ $ready ? 'text-[#047857]' : 'text-[#92400E]' }}">{{ $ready ? 'Your assistant is ready' : 'One setup item is still missing' }}</p>
                <p class="mt-1 text-sm {{ $ready ? 'text-[#047857]' : 'text-[#92400E]' }}">{{ $connectedChannels }} connected {{ Str::plural('channel', $connectedChannels) }} · {{ number_format($creditBalance) }} credits</p>
            </div>
            @if (! $connectedChannels)
                <a href="{{ route('dashboard.accounts') }}" class="rounded-lg bg-white px-4 py-2 text-sm font-bold text-[#92400E] shadow-sm">Connect a channel</a>
            @elseif ($creditBalance <= 0)
                <a href="{{ route('dashboard.ai-credits') }}" class="rounded-lg bg-white px-4 py-2 text-sm font-bold text-[#92400E] shadow-sm">Get credits</a>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('dashboard.ai-settings.update') }}" class="content-card overflow-hidden">
        @csrf
        @method('PATCH')

        <div class="flex items-start justify-between gap-5 p-5 sm:p-6">
            <div>
                <h3 class="text-lg font-bold text-[#111827]">Automatic replies</h3>
                <p class="mt-1 max-w-xl text-sm leading-6 text-[#6B7280]">The assistant answers what it safely can, asks useful follow-up questions, and sends decisions requiring your authority to the team.</p>
            </div>
            <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                <input type="checkbox" name="auto_reply_enabled" value="1" @checked($settings->auto_reply_enabled) class="peer sr-only">
                <span class="h-7 w-12 rounded-full bg-[#D1D5DB] transition peer-checked:bg-[#10B981] after:absolute after:left-1 after:top-1 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
            </label>
        </div>

        <details class="border-t border-[#E5E7EB] bg-[#F9FAFB]">
            <summary class="cursor-pointer px-5 py-4 text-sm font-bold text-[#374151] sm:px-6">Customize it (optional)</summary>
            <div class="grid gap-5 border-t border-[#E5E7EB] p-5 sm:p-6 md:grid-cols-2">
                <label class="block text-sm font-semibold text-[#374151]">Assistant name
                    <input name="assistant_name" required value="{{ old('assistant_name', $settings->assistant_name) }}" class="mt-2 w-full rounded-lg border-[#E5E7EB] bg-white text-[#111827] shadow-sm focus:border-[#2563EB] focus:ring-[#2563EB]">
                </label>
                <label class="block text-sm font-semibold text-[#374151]">Tone
                    <select name="tone" class="mt-2 w-full rounded-lg border-[#E5E7EB] bg-white text-[#111827] shadow-sm focus:border-[#2563EB] focus:ring-[#2563EB]">
                        @foreach ($toneOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('tone', Str::lower($settings->tone)) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-semibold text-[#374151] md:col-span-2">Other situations where your team must step in
                    <textarea name="escalation_instructions" rows="2" class="mt-2 w-full rounded-lg border-[#E5E7EB] bg-white text-[#111827] shadow-sm focus:border-[#2563EB] focus:ring-[#2563EB]" placeholder="Optional — sensible safety defaults already apply.">{{ old('escalation_instructions', $settings->escalation_instructions) }}</textarea>
                </label>
                <label class="flex items-start gap-3 text-sm text-[#374151] md:col-span-2">
                    <input type="checkbox" name="business_hours_enabled" value="1" @checked($settings->business_hours_enabled) class="mt-1 rounded border-[#D1D5DB] text-[#2563EB] focus:ring-[#2563EB]">
                    <span><span class="block font-bold">Only reply between 9:00 and 19:00</span><span class="text-[#6B7280]">Leave off to reply at any time.</span></span>
                </label>
                <label class="block text-sm font-semibold text-[#374151] md:col-span-2">Handover acknowledgement
                    <textarea name="fallback_response" rows="2" class="mt-2 w-full rounded-lg border-[#E5E7EB] bg-white text-[#111827] shadow-sm focus:border-[#2563EB] focus:ring-[#2563EB]" placeholder="Optional — the assistant can write a natural acknowledgement automatically.">{{ old('fallback_response', $settings->fallback_response) }}</textarea>
                </label>
            </div>
        </details>

        <div class="flex justify-end border-t border-[#E5E7EB] px-5 py-4 sm:px-6">
            <button class="rounded-lg bg-[#2563EB] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#1d4ed8]">Save</button>
        </div>
    </form>

    <section class="mt-8">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-[#2563EB]">Optional business knowledge</p>
            <h3 class="mt-2 text-lg font-bold text-[#111827]">Teach it about your business</h3>
            <p class="mt-1 text-sm leading-6 text-[#6B7280]">Skip this for now if you want. Add information later when customers ask questions specific to your company.</p>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            @foreach ($knowledgeCards as [$section, $title, $description, $count])
                <a href="{{ route('dashboard.knowledge-base', ['section' => $section]) }}" class="rounded-xl border border-[#E5E7EB] bg-white p-4 transition hover:border-[#2563EB] hover:bg-blue-50/40">
                    <div class="flex items-center justify-between gap-3"><p class="font-bold text-[#111827]">{{ $title }}</p><span class="rounded-full bg-[#EEF0F3] px-2.5 py-1 text-xs font-bold text-[#6B7280]">{{ $count }}</span></div>
                    <p class="mt-2 text-sm leading-6 text-[#6B7280]">{{ $description }}</p>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mt-8 border-t border-[#E5E7EB] pt-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h3 class="font-bold text-[#111827]">Team saved replies</h3><p class="mt-1 text-sm text-[#6B7280]">Optional manual shortcuts for staff. They do not control AI answers.</p></div>
            <a href="{{ route('dashboard.knowledge-base', ['section' => 'saved-replies']) }}" class="rounded-lg border border-[#E5E7EB] bg-white px-4 py-2.5 text-sm font-bold text-[#374151]">Manage saved replies</a>
        </div>
    </section>
</x-app-layout>
