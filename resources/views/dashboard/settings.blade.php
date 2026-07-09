<x-app-layout>
    @php
        $inputClass = 'w-full rounded-lg border border-[#E5E7EB] bg-white px-3 py-2.5 text-sm font-medium text-[#111827] placeholder:text-[#6B7280] focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20';
        $textareaClass = 'w-full resize-y rounded-lg border border-[#E5E7EB] bg-white px-3 py-2.5 text-sm font-medium leading-6 text-[#111827] placeholder:text-[#6B7280] focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20';
        $labelClass = 'text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]';
    @endphp

    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#2563EB]">Settings</p>
        <h2 class="mt-2 text-3xl font-bold text-[#111827]">Workspace settings</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-[#6B7280]">Keep the business details your team and customer workflows rely on up to date.</p>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-pink-200 bg-pink-50 px-4 py-3 text-sm font-semibold text-[#BE185D]">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
        <section class="content-card overflow-hidden">
            <div class="border-b border-[#E5E7EB] px-5 py-4">
                <h3 class="font-bold text-[#111827]">Business profile</h3>
                <p class="mt-1 text-sm text-[#6B7280]">These details help staff recognize the active workspace and prepare future customer-facing replies.</p>
            </div>

            <form method="POST" action="{{ route('dashboard.settings.business.update') }}" class="grid gap-4 p-5">
                @csrf
                @method('PATCH')

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="grid gap-1.5">
                        <span class="{{ $labelClass }}">Business name</span>
                        <input name="name" value="{{ old('name', $business->name) }}" class="{{ $inputClass }}" placeholder="Lagos Detailing">
                    </label>

                    <label class="grid gap-1.5">
                        <span class="{{ $labelClass }}">Category</span>
                        <input name="category" value="{{ old('category', $business->category) }}" class="{{ $inputClass }}" placeholder="Auto care">
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="grid gap-1.5">
                        <span class="{{ $labelClass }}">Email</span>
                        <input type="email" name="email" value="{{ old('email', $business->email) }}" class="{{ $inputClass }}" placeholder="hello@example.com">
                    </label>

                    <label class="grid gap-1.5">
                        <span class="{{ $labelClass }}">Phone</span>
                        <input name="phone" value="{{ old('phone', $business->phone) }}" class="{{ $inputClass }}" placeholder="+234 800 000 0000">
                    </label>
                </div>

                <label class="grid gap-1.5">
                    <span class="{{ $labelClass }}">Website</span>
                    <input type="url" name="website" value="{{ old('website', $business->website) }}" class="{{ $inputClass }}" placeholder="https://example.com">
                </label>

                <label class="grid gap-1.5">
                    <span class="{{ $labelClass }}">Description</span>
                    <textarea name="description" rows="4" class="{{ $textareaClass }}" placeholder="Short description of what this business does.">{{ old('description', $business->description) }}</textarea>
                </label>

                <button class="inline-flex w-fit items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/30">
                    Save changes
                </button>
            </form>
        </section>

        <aside class="content-card p-5">
            <h3 class="font-bold text-[#111827]">Workspace summary</h3>
            <dl class="mt-5 space-y-4 text-sm">
                <div>
                    <dt class="font-semibold text-[#6B7280]">Name</dt>
                    <dd class="mt-1 font-bold text-[#111827]">{{ $business->name }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-[#6B7280]">Category</dt>
                    <dd class="mt-1 text-[#374151]">{{ $business->category ?: 'Not set' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-[#6B7280]">Email</dt>
                    <dd class="mt-1 break-all text-[#374151]">{{ $business->email ?: 'Not set' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-[#6B7280]">Phone</dt>
                    <dd class="mt-1 text-[#374151]">{{ $business->phone ?: 'Not set' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-[#6B7280]">Website</dt>
                    <dd class="mt-1 break-all text-[#374151]">{{ $business->website ?: 'Not set' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-[#6B7280]">Description</dt>
                    <dd class="mt-1 leading-6 text-[#6B7280]">{{ $business->description ?: 'Not set' }}</dd>
                </div>
            </dl>
        </aside>
    </div>
</x-app-layout>
