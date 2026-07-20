<x-app-layout>
    @php
        $ready = $aiRuntimeEnabled && $aiProviderConfigured && $creditBalance > 0 && $connectedChannels > 0;
        $toneOptions = [
            'friendly' => ['Friendly', 'Warm, helpful, and conversational'],
            'professional' => ['Professional', 'Clear, polished, and respectful'],
            'casual' => ['Casual', 'Relaxed, simple, and direct'],
        ];
        $knowledgeCards = [
            ['faqs', 'Questions & answers', 'Teach it how to answer common customer questions.', $knowledgeCounts['faqs']],
            ['products', 'Products & services', 'Add your offers, prices, and availability.', $knowledgeCounts['products']],
            ['rules', 'Business policies', 'Set facts and limits it must always follow.', $knowledgeCounts['rules']],
        ];
    @endphp

    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#2563EB]">AI Assistant</p>
        <h2 class="mt-2 text-3xl font-bold text-[#111827]">Set it up in a few minutes</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-[#6B7280]">Choose how it speaks, teach it about your business, and decide when your team should step in.</p>
    </div>

    <div class="mb-6 rounded-xl border {{ $ready ? 'border-[#A7F3D0] bg-[#ECFDF5]' : 'border-[#FDE68A] bg-[#FFFBEB]' }} p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-bold {{ $ready ? 'text-[#047857]' : 'text-[#92400E]' }}">{{ $ready ? 'Your assistant is ready' : 'Finish setup before turning on replies' }}</p>
                <p class="mt-1 text-sm leading-6 {{ $ready ? 'text-[#047857]' : 'text-[#92400E]' }}">
                    {{ $connectedChannels }} connected {{ Str::plural('channel', $connectedChannels) }} · {{ number_format($creditBalance) }} credits
                </p>
            </div>
            @if (! $connectedChannels)
                <a href="{{ route('dashboard.accounts') }}" class="rounded-lg bg-white px-4 py-2 text-sm font-bold text-[#92400E] shadow-sm">Connect a channel</a>
            @elseif ($creditBalance <= 0)
                <a href="{{ route('dashboard.ai-credits') }}" class="rounded-lg bg-white px-4 py-2 text-sm font-bold text-[#92400E] shadow-sm">Get credits</a>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('dashboard.ai-settings.update') }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <section class="content-card p-5 sm:p-6">
            <div class="flex items-start justify-between gap-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-[#2563EB]">1. Automatic replies</p>
                    <h3 class="mt-2 text-lg font-bold text-[#111827]">Let the assistant reply to customers</h3>
                    <p class="mt-1 text-sm leading-6 text-[#6B7280]">Turn this off anytime. Your inbox and manual replies will continue working.</p>
                </div>
                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                    <input type="checkbox" name="auto_reply_enabled" value="1" @checked($settings->auto_reply_enabled) class="peer sr-only">
                    <span class="h-7 w-12 rounded-full bg-[#D1D5DB] transition peer-checked:bg-[#10B981] after:absolute after:left-1 after:top-1 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
                </label>
            </div>
        </section>

        <section class="content-card p-5 sm:p-6">
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-[#2563EB]">2. How it speaks</p>
            <div class="mt-4 grid gap-5 md:grid-cols-2">
                <label class="block text-sm font-semibold text-[#374151]">Assistant name
                    <input name="assistant_name" required value="{{ old('assistant_name', $settings->assistant_name) }}" class="mt-2 w-full rounded-lg border-[#E5E7EB] bg-white text-[#111827] shadow-sm focus:border-[#2563EB] focus:ring-[#2563EB]" placeholder="e.g. Ada">
                </label>
                <fieldset>
                    <legend class="text-sm font-semibold text-[#374151]">Tone</legend>
                    <div class="mt-2 grid gap-2">
                        @foreach ($toneOptions as $value => [$label, $description])
                            <label class="flex cursor-pointer gap-3 rounded-xl border border-[#E5E7EB] p-3 has-[:checked]:border-[#2563EB] has-[:checked]:bg-blue-50">
                                <input type="radio" name="tone" value="{{ $value }}" @checked(old('tone', Str::lower($settings->tone)) === $value) class="mt-1 border-[#D1D5DB] text-[#2563EB] focus:ring-[#2563EB]">
                                <span><span class="block text-sm font-bold text-[#111827]">{{ $label }}</span><span class="text-xs text-[#6B7280]">{{ $description }}</span></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>
        </section>

        <section class="content-card p-5 sm:p-6">
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-[#2563EB]">3. What it knows</p>
            <h3 class="mt-2 text-lg font-bold text-[#111827]">Teach it about your business</h3>
            <p class="mt-1 text-sm leading-6 text-[#6B7280]">The assistant only uses information you approve here. If the answer is missing, it sends the conversation to your team.</p>
            <div class="mt-5 grid gap-3 md:grid-cols-3">
                @foreach ($knowledgeCards as [$section, $title, $description, $count])
                    <a href="{{ route('dashboard.knowledge-base', ['section' => $section]) }}" class="rounded-xl border border-[#E5E7EB] bg-white p-4 transition hover:border-[#2563EB] hover:bg-blue-50/40">
                        <div class="flex items-center justify-between gap-3"><p class="font-bold text-[#111827]">{{ $title }}</p><span class="rounded-full bg-[#EEF0F3] px-2.5 py-1 text-xs font-bold text-[#6B7280]">{{ $count }}</span></div>
                        <p class="mt-2 text-sm leading-6 text-[#6B7280]">{{ $description }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="content-card p-5 sm:p-6">
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-[#2563EB]">4. When your team steps in</p>
            <h3 class="mt-2 text-lg font-bold text-[#111827]">Human help is always available</h3>
            <p class="mt-1 text-sm leading-6 text-[#6B7280]">Complaints, refunds, discounts, custom prices, approval requests, and uncertain answers are automatically sent to your team.</p>
            <label class="mt-4 block text-sm font-semibold text-[#374151]">Any other situations?
                <textarea name="escalation_instructions" rows="3" class="mt-2 w-full rounded-lg border-[#E5E7EB] bg-white text-[#111827] shadow-sm focus:border-[#2563EB] focus:ring-[#2563EB]" placeholder="Example: Always ask a staff member before confirming same-day delivery.">{{ old('escalation_instructions', $settings->escalation_instructions) }}</textarea>
            </label>

            <details class="mt-5 rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-4">
                <summary class="cursor-pointer text-sm font-bold text-[#374151]">Optional settings</summary>
                <div class="mt-4 grid gap-4">
                    <label class="flex items-start gap-3 text-sm text-[#374151]">
                        <input type="checkbox" name="business_hours_enabled" value="1" @checked($settings->business_hours_enabled) class="mt-1 rounded border-[#D1D5DB] text-[#2563EB] focus:ring-[#2563EB]">
                        <span><span class="block font-bold">Only reply between 9:00 and 19:00</span><span class="text-[#6B7280]">Outside these hours, new messages wait for your team.</span></span>
                    </label>
                    <label class="block text-sm font-semibold text-[#374151]">Message shown when the assistant cannot safely answer
                        <textarea name="fallback_response" rows="2" class="mt-2 w-full rounded-lg border-[#E5E7EB] bg-white text-[#111827] shadow-sm focus:border-[#2563EB] focus:ring-[#2563EB]" placeholder="A team member will review this and reply shortly.">{{ old('fallback_response', $settings->fallback_response) }}</textarea>
                    </label>
                </div>
            </details>
        </section>

        <div class="flex justify-end">
            <button class="rounded-lg bg-[#2563EB] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#1d4ed8]">Save AI assistant</button>
        </div>
    </form>

    <section class="mt-8 border-t border-[#E5E7EB] pt-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h3 class="font-bold text-[#111827]">Team saved replies</h3><p class="mt-1 text-sm text-[#6B7280]">Manual shortcuts for staff. They do not affect AI answers.</p></div>
            <a href="{{ route('dashboard.knowledge-base', ['section' => 'saved-replies']) }}" class="rounded-lg border border-[#E5E7EB] bg-white px-4 py-2.5 text-sm font-bold text-[#374151]">Manage {{ $knowledgeCounts['savedReplies'] }} saved replies</a>
        </div>
    </section>
</x-app-layout>
