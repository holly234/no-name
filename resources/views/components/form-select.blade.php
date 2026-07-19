@props([
    'name',
    'options' => [],
    'selected' => null,
    'buttonClass' => 'w-full',
    'autoSubmit' => false,
])

@php
    $selected = (string) ($selected ?? array_key_first($options));
@endphp

<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="{
        open: false,
        value: @js($selected),
        options: @js($options),
        get label() {
            return this.options[this.value] || Object.values(this.options)[0] || 'Select';
        },
        choose(next) {
            this.value = next;
            this.open = false;

            @if ($autoSubmit)
                this.$nextTick(() => this.$root.closest('form')?.requestSubmit());
            @endif
        },
    }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
>
    <input type="hidden" name="{{ $name }}" x-bind:value="value">

    <button
        type="button"
        x-on:click="open = ! open"
        class="inline-flex items-center justify-between gap-3 rounded-lg border border-[#D1D5DB] bg-white px-3 py-2.5 text-left text-sm font-semibold text-[#111827] shadow-sm transition hover:border-[#93C5FD] hover:bg-[#F8FAFC] focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20 {{ $buttonClass }}"
        aria-haspopup="listbox"
        x-bind:aria-expanded="open"
    >
        <span class="truncate" x-text="label"></span>
        <svg class="h-4 w-4 shrink-0 text-[#6B7280] transition" x-bind:class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6"></path>
        </svg>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top
        class="absolute left-0 right-0 z-30 mt-2 overflow-hidden rounded-xl border border-[#E5E7EB] bg-white py-1 shadow-xl shadow-[#111827]/10"
        role="listbox"
    >
        @foreach ($options as $value => $label)
            <button
                type="button"
                x-on:click="choose(@js((string) $value))"
                class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm font-semibold transition hover:bg-[#EFF6FF] hover:text-[#2563EB]"
                x-bind:class="value === @js((string) $value) ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#374151]'"
                role="option"
                x-bind:aria-selected="value === @js((string) $value)"
            >
                <span>{{ $label }}</span>
                <svg x-cloak x-show="value === @js((string) $value)" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 6 9 17l-5-5"></path>
                </svg>
            </button>
        @endforeach
    </div>
</div>
