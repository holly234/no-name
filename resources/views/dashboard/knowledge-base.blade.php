<x-app-layout>
    @php
        $inputClass = 'w-full rounded-lg border border-[#E5E7EB] bg-white px-3 py-2.5 text-sm font-medium text-[#111827] placeholder:text-[#6B7280] focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20';
        $textareaClass = 'w-full resize-y rounded-lg border border-[#E5E7EB] bg-white px-3 py-2.5 text-sm font-medium leading-6 text-[#111827] placeholder:text-[#6B7280] focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20';
        $labelClass = 'text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]';
        $tabs = [
            'faqs' => ['label' => 'FAQs', 'count' => $faqs->count(), 'title' => 'FAQs', 'description' => 'Common questions and approved answers.'],
            'products' => ['label' => 'Services', 'count' => $products->count(), 'title' => 'Products and services', 'description' => 'Offers, pricing, availability, and reply guidance.'],
            'rules' => ['label' => 'Rules', 'count' => $rules->count(), 'title' => 'Business rules', 'description' => 'Policies that guide routing, escalation, and tone.'],
            'saved-replies' => ['label' => 'Replies', 'count' => $savedReplies->count(), 'title' => 'Saved replies', 'description' => 'Reusable responses for common manual replies.'],
        ];
        $section = $tabs[$activeSection] ?? $tabs['faqs'];
    @endphp

    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#2563EB]">Knowledge base</p>
        <h2 class="mt-2 text-3xl font-bold text-[#111827]">Business memory</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-[#6B7280]">Add and maintain the source material used for replies, routing, and handover decisions.</p>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-pink-200 bg-pink-50 px-4 py-3 text-sm font-semibold text-[#BE185D]">
            {{ $errors->first() }}
        </div>
    @endif

    <nav class="sticky top-0 z-10 -mx-4 mb-4 overflow-x-auto border-y border-[#E5E7EB] bg-[#F5F6F8]/95 px-4 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:px-0 sm:py-0" aria-label="Knowledge base sections">
        <div class="grid min-w-max grid-cols-4 gap-2 sm:min-w-0">
            @foreach ($tabs as $key => $tab)
                @php $isActive = $activeSection === $key; @endphp
                <a href="{{ route('dashboard.knowledge-base', ['section' => $key]) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-full border px-4 text-sm font-semibold transition {{ $isActive ? 'border-[#2563EB] bg-[#2563EB] text-white shadow-sm' : 'border-[#E5E7EB] bg-white text-[#374151] hover:bg-[#F5F6F8]' }}">
                    {{ $tab['label'] }}
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $isActive ? 'bg-white/20 text-white' : 'bg-[#EEF0F3] text-[#6B7280]' }}">{{ $tab['count'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    <section class="content-card overflow-hidden">
        <div class="border-b border-[#E5E7EB] px-4 py-4 sm:px-5">
            <h3 class="font-bold text-[#111827]">{{ $section['title'] }}</h3>
            <p class="mt-1 text-sm text-[#6B7280]">{{ $section['description'] }}</p>
        </div>

        @if ($activeSection === 'faqs')
            <details class="border-b border-[#E5E7EB] bg-[#F5F6F8] px-4 py-4 sm:px-5" open>
                <summary class="cursor-pointer text-sm font-bold text-[#2563EB]">Add FAQ</summary>
                <form method="POST" action="{{ route('dashboard.knowledge-base.faqs.store') }}" class="mt-4 grid gap-3">
                    @csrf
                    <label class="grid gap-1.5">
                        <span class="{{ $labelClass }}">Question</span>
                        <input name="question" value="{{ old('question') }}" class="{{ $inputClass }}" placeholder="What are your business hours?">
                    </label>
                    <label class="grid gap-1.5">
                        <span class="{{ $labelClass }}">Answer</span>
                        <textarea name="answer" rows="3" class="{{ $textareaClass }}" placeholder="We respond every day from 9am to 7pm.">{{ old('answer') }}</textarea>
                    </label>
                    <label class="grid gap-1.5">
                        <span class="{{ $labelClass }}">Category</span>
                        <input name="category" value="{{ old('category') }}" class="{{ $inputClass }}" placeholder="Booking, pricing, operations">
                    </label>
                    <button class="inline-flex w-fit items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1d4ed8]">Add FAQ</button>
                </form>
            </details>
            <div class="divide-y divide-[#E5E7EB]">
                @forelse ($faqs as $faq)
                    <article class="px-4 py-4 sm:px-5">
                        <p class="font-semibold text-[#111827]">{{ $faq->question }}</p>
                        <p class="mt-2 text-sm leading-6 text-[#6B7280]">{{ $faq->answer }}</p>
                        @if ($faq->category)
                            <p class="mt-3 inline-flex rounded-full bg-[#EEF0F3] px-2.5 py-1 text-xs font-semibold text-[#6B7280]">{{ $faq->category }}</p>
                        @endif
                        <details class="mt-4 rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-3">
                            <summary class="cursor-pointer text-sm font-semibold text-[#2563EB]">Edit FAQ</summary>
                            <form method="POST" action="{{ route('dashboard.knowledge-base.faqs.update', $faq) }}" class="mt-4 grid gap-3">
                                @csrf
                                @method('PATCH')
                                <input name="question" value="{{ old('question', $faq->question) }}" class="{{ $inputClass }}">
                                <textarea name="answer" rows="3" class="{{ $textareaClass }}">{{ old('answer', $faq->answer) }}</textarea>
                                <input name="category" value="{{ old('category', $faq->category) }}" class="{{ $inputClass }}" placeholder="Category">
                                <button class="w-fit rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-semibold text-white">Save changes</button>
                            </form>
                            <form method="POST" action="{{ route('dashboard.knowledge-base.faqs.destroy', $faq) }}" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg border border-pink-200 bg-pink-50 px-4 py-2 text-sm font-semibold text-[#BE185D]">Delete FAQ</button>
                            </form>
                        </details>
                    </article>
                @empty
                    <p class="px-4 py-5 text-sm text-[#6B7280] sm:px-5">No FAQs yet.</p>
                @endforelse
            </div>
        @elseif ($activeSection === 'products')
            <details class="border-b border-[#E5E7EB] bg-[#F5F6F8] px-4 py-4 sm:px-5" open>
                <summary class="cursor-pointer text-sm font-bold text-[#2563EB]">Add product/service</summary>
                <form method="POST" action="{{ route('dashboard.knowledge-base.products.store') }}" class="mt-4 grid gap-3">
                    @csrf
                    <input name="name" value="{{ old('name') }}" class="{{ $inputClass }}" placeholder="Service name">
                    <input name="price" value="{{ old('price') }}" class="{{ $inputClass }}" placeholder="Price, e.g. From NGN 55,000">
                    <input name="availability" value="{{ old('availability') }}" class="{{ $inputClass }}" placeholder="Availability">
                    <textarea name="description" rows="3" class="{{ $textareaClass }}" placeholder="Description customers can understand.">{{ old('description') }}</textarea>
                    <textarea name="ai_notes" rows="2" class="{{ $textareaClass }}" placeholder="Internal guidance for replies.">{{ old('ai_notes') }}</textarea>
                    <button class="inline-flex w-fit items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1d4ed8]">Add product/service</button>
                </form>
            </details>
            <div class="divide-y divide-[#E5E7EB]">
                @forelse ($products as $product)
                    <article class="px-4 py-4 sm:px-5">
                        <p class="font-semibold text-[#111827]">{{ $product->name }}</p>
                        @if ($product->price)
                            <p class="mt-1 text-sm font-bold text-[#10B981]">{{ $product->price }}</p>
                        @endif
                        <p class="mt-2 text-sm leading-6 text-[#6B7280]">{{ $product->description ?: 'No description yet.' }}</p>
                        @if ($product->availability || $product->ai_notes)
                            <div class="mt-3 space-y-1 text-xs font-semibold text-[#6B7280]">
                                @if ($product->availability)
                                    <p>Availability: {{ $product->availability }}</p>
                                @endif
                                @if ($product->ai_notes)
                                    <p>Reply note: {{ $product->ai_notes }}</p>
                                @endif
                            </div>
                        @endif
                        <details class="mt-4 rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-3">
                            <summary class="cursor-pointer text-sm font-semibold text-[#2563EB]">Edit product/service</summary>
                            <form method="POST" action="{{ route('dashboard.knowledge-base.products.update', $product) }}" class="mt-4 grid gap-3">
                                @csrf
                                @method('PATCH')
                                <input name="name" value="{{ old('name', $product->name) }}" class="{{ $inputClass }}">
                                <input name="price" value="{{ old('price', $product->price) }}" class="{{ $inputClass }}" placeholder="Price">
                                <input name="availability" value="{{ old('availability', $product->availability) }}" class="{{ $inputClass }}" placeholder="Availability">
                                <textarea name="description" rows="3" class="{{ $textareaClass }}">{{ old('description', $product->description) }}</textarea>
                                <textarea name="ai_notes" rows="2" class="{{ $textareaClass }}" placeholder="Reply notes">{{ old('ai_notes', $product->ai_notes) }}</textarea>
                                <button class="w-fit rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-semibold text-white">Save changes</button>
                            </form>
                            <form method="POST" action="{{ route('dashboard.knowledge-base.products.destroy', $product) }}" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg border border-pink-200 bg-pink-50 px-4 py-2 text-sm font-semibold text-[#BE185D]">Delete product/service</button>
                            </form>
                        </details>
                    </article>
                @empty
                    <p class="px-4 py-5 text-sm text-[#6B7280] sm:px-5">No products or services yet.</p>
                @endforelse
            </div>
        @elseif ($activeSection === 'rules')
            <details class="border-b border-[#E5E7EB] bg-[#F5F6F8] px-4 py-4 sm:px-5" open>
                <summary class="cursor-pointer text-sm font-bold text-[#2563EB]">Add rule</summary>
                <form method="POST" action="{{ route('dashboard.knowledge-base.rules.store') }}" class="mt-4 grid gap-3">
                    @csrf
                    <select name="rule_type" class="{{ $inputClass }}">
                        @foreach (['handover' => 'Handover', 'pricing' => 'Pricing', 'availability' => 'Availability', 'tone' => 'Tone', 'policy' => 'Policy', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input name="title" value="{{ old('title') }}" class="{{ $inputClass }}" placeholder="Rule title">
                    <textarea name="content" rows="3" class="{{ $textareaClass }}" placeholder="What should the team or assistant follow?">{{ old('content') }}</textarea>
                    <button class="inline-flex w-fit items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1d4ed8]">Add rule</button>
                </form>
            </details>
            <div class="divide-y divide-[#E5E7EB]">
                @forelse ($rules as $rule)
                    <article class="px-4 py-4 sm:px-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-[#111827]">{{ $rule->title }}</p>
                            <span class="rounded-full bg-[#EEF0F3] px-2.5 py-1 text-xs font-semibold text-[#6B7280]">{{ ucfirst($rule->rule_type) }}</span>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-[#6B7280]">{{ $rule->content }}</p>
                        <details class="mt-4 rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-3">
                            <summary class="cursor-pointer text-sm font-semibold text-[#2563EB]">Edit rule</summary>
                            <form method="POST" action="{{ route('dashboard.knowledge-base.rules.update', $rule) }}" class="mt-4 grid gap-3">
                                @csrf
                                @method('PATCH')
                                <select name="rule_type" class="{{ $inputClass }}">
                                    @foreach (['handover' => 'Handover', 'pricing' => 'Pricing', 'availability' => 'Availability', 'tone' => 'Tone', 'policy' => 'Policy', 'other' => 'Other'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('rule_type', $rule->rule_type) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input name="title" value="{{ old('title', $rule->title) }}" class="{{ $inputClass }}">
                                <textarea name="content" rows="3" class="{{ $textareaClass }}">{{ old('content', $rule->content) }}</textarea>
                                <button class="w-fit rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-semibold text-white">Save changes</button>
                            </form>
                            <form method="POST" action="{{ route('dashboard.knowledge-base.rules.destroy', $rule) }}" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg border border-pink-200 bg-pink-50 px-4 py-2 text-sm font-semibold text-[#BE185D]">Delete rule</button>
                            </form>
                        </details>
                    </article>
                @empty
                    <p class="px-4 py-5 text-sm text-[#6B7280] sm:px-5">No business rules yet.</p>
                @endforelse
            </div>
        @else
            <details class="border-b border-[#E5E7EB] bg-[#F5F6F8] px-4 py-4 sm:px-5" open>
                <summary class="cursor-pointer text-sm font-bold text-[#2563EB]">Add saved reply</summary>
                <form method="POST" action="{{ route('dashboard.knowledge-base.saved-replies.store') }}" class="mt-4 grid gap-3">
                    @csrf
                    <input name="title" value="{{ old('title') }}" class="{{ $inputClass }}" placeholder="Reply title">
                    <input name="shortcut" value="{{ old('shortcut') }}" class="{{ $inputClass }}" placeholder="Shortcut, e.g. /pricing">
                    <textarea name="body" rows="4" class="{{ $textareaClass }}" placeholder="Saved reply text">{{ old('body') }}</textarea>
                    <button class="inline-flex w-fit items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1d4ed8]">Add saved reply</button>
                </form>
            </details>
            <div class="divide-y divide-[#E5E7EB]">
                @forelse ($savedReplies as $reply)
                    <article class="px-4 py-4 sm:px-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-[#111827]">{{ $reply->title }}</p>
                            @if ($reply->shortcut)
                                <span class="rounded-full bg-[#EEF0F3] px-2.5 py-1 text-xs font-semibold text-[#6B7280]">{{ $reply->shortcut }}</span>
                            @endif
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-[#6B7280]">{{ $reply->body }}</p>
                        <details class="mt-4 rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-3">
                            <summary class="cursor-pointer text-sm font-semibold text-[#2563EB]">Edit saved reply</summary>
                            <form method="POST" action="{{ route('dashboard.knowledge-base.saved-replies.update', $reply) }}" class="mt-4 grid gap-3">
                                @csrf
                                @method('PATCH')
                                <input name="title" value="{{ old('title', $reply->title) }}" class="{{ $inputClass }}">
                                <input name="shortcut" value="{{ old('shortcut', $reply->shortcut) }}" class="{{ $inputClass }}" placeholder="Shortcut">
                                <textarea name="body" rows="4" class="{{ $textareaClass }}">{{ old('body', $reply->body) }}</textarea>
                                <button class="w-fit rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-semibold text-white">Save changes</button>
                            </form>
                            <form method="POST" action="{{ route('dashboard.knowledge-base.saved-replies.destroy', $reply) }}" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg border border-pink-200 bg-pink-50 px-4 py-2 text-sm font-semibold text-[#BE185D]">Delete saved reply</button>
                            </form>
                        </details>
                    </article>
                @empty
                    <p class="px-4 py-5 text-sm text-[#6B7280] sm:px-5">No saved replies yet.</p>
                @endforelse
            </div>
        @endif
    </section>
</x-app-layout>
