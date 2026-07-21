<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Perpetual brings customer conversations into one workspace and gives your team a controlled AI assistant for everyday replies.">
    <title>Perpetual — Every customer conversation, under control</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --paper: #f4f0e8;
            --paper-deep: #e9e2d6;
            --ink: #1d211f;
            --muted: #666b66;
            --line: #d8d1c5;
            --surface: #fbf9f4;
            --accent: #b94f36;
            --accent-dark: #8e3928;
            --moss: #53645a;
        }
        html, body { width: 100%; max-width: 100%; margin: 0; overflow-x: clip !important; overscroll-behavior-x: none; }
        .marketing-shell { width: 100%; max-width: 100%; background: var(--paper); color: var(--ink); contain: inline-size; overflow-x: clip !important; }
        .marketing-shell, .marketing-shell main, .marketing-shell section { width: 100%; max-width: 100%; min-width: 0; overflow-x: clip; }
        .marketing-shell *, .marketing-shell *::before, .marketing-shell *::after { box-sizing: border-box; }
        .marketing-shell * { max-width: 100%; }
        .marketing-shell img, .marketing-shell svg, .marketing-shell video { max-width: 100%; }
        .marketing-shell [class*="grid"] > * { min-width: 0; }
        .marketing-shell h1, .marketing-shell h2, .marketing-shell h3, .marketing-shell p { max-width: 100%; overflow-wrap: anywhere; }
        .marketing-shell .product-frame { width: 100%; max-width: 100%; }
        .marketing-grid { background-image: linear-gradient(to right, rgba(29,33,31,.045) 1px, transparent 1px); background-size: 72px 100%; }
        .marketing-rule { border-color: var(--line); }
        .marketing-kicker { letter-spacing: .16em; text-transform: uppercase; font-size: .72rem; font-weight: 700; color: var(--accent-dark); }
        .marketing-button { display:inline-flex; min-height:3rem; align-items:center; justify-content:center; gap:.6rem; padding:.75rem 1.15rem; font-size:.875rem; font-weight:700; transition:background-color .18s ease,color .18s ease,transform .18s ease; }
        .marketing-button:hover { transform: translateY(-1px); }
        .marketing-button-primary { background:var(--accent); color:white; }
        .marketing-button-primary:hover { background:var(--accent-dark); }
        .marketing-button-secondary { border:1px solid var(--line); background:rgba(251,249,244,.55); color:var(--ink); }
        .marketing-button-secondary:hover { background:var(--surface); }
        .product-frame { box-shadow: 0 32px 70px rgba(44,38,32,.16), 0 2px 8px rgba(44,38,32,.08); }
        .reveal { animation: reveal .65s cubic-bezier(.22,.8,.24,1) both; }
        @keyframes reveal { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
        @media (prefers-reduced-motion: reduce) { .reveal { animation:none; } html { scroll-behavior:auto; } }
        @media (max-width: 767px) {
            html, body { width: 100%; max-width: 100%; overflow-x: hidden !important; }
            .marketing-shell { width: 92%; max-width: 92%; margin-inline: auto; overflow-x: hidden !important; }
            .marketing-shell main > section > div,
            .marketing-shell main > section > div > div,
            .marketing-shell .reveal,
            .marketing-shell .reveal p,
            .marketing-shell .reveal h1,
            .marketing-shell .reveal .flex,
            .marketing-shell .product-frame { width: 100%; min-width: 0; max-width: 100%; }
            .marketing-shell .reveal p { overflow-wrap: anywhere; }
            .marketing-shell .reveal .flex.flex-col > a { width: 100%; max-width: 100%; }
            .marketing-shell .product-frame { overflow: hidden; }
            .marketing-shell .product-frame > div { min-width: 0; }
        }
    </style>
</head>
@php
    $primaryUrl = auth()->check() ? route('dashboard') : route('auth.google.redirect');
    $primaryLabel = auth()->check() ? 'Open workspace' : 'Start with Google';
    $channels = [
        ['name' => 'Instagram', 'icon' => 'instagram'],
        ['name' => 'WhatsApp', 'icon' => 'whatsapp'],
        ['name' => 'Facebook', 'icon' => 'facebook'],
        ['name' => 'Gmail', 'icon' => 'gmail'],
        ['name' => 'Telegram', 'icon' => 'telegram'],
    ];
    $inboxRows = [
        ['name' => 'Amaka N.', 'copy' => 'Can I book the full detail for Saturday?', 'time' => '10:42', 'channel' => 'Instagram', 'icon' => 'instagram', 'state' => 'Needs reply', 'active' => true],
        ['name' => 'Tunde Bello', 'copy' => 'What is included in ceramic coating?', 'time' => '10:18', 'channel' => 'WhatsApp', 'icon' => 'whatsapp', 'state' => 'AI handling'],
        ['name' => 'Facebook', 'copy' => 'Your weekly page summary is ready.', 'time' => '09:51', 'channel' => 'Gmail', 'icon' => 'gmail', 'state' => 'Info'],
        ['name' => 'Nora James', 'copy' => 'Thank you — see you tomorrow.', 'time' => '09:33', 'channel' => 'Facebook', 'icon' => 'facebook', 'state' => 'Waiting'],
    ];
    $useCases = [
        ['title' => 'Service businesses', 'copy' => 'Turn enquiries into bookings without losing the details that matter.'],
        ['title' => 'Online stores', 'copy' => 'Handle product questions, order updates and escalations from one queue.'],
        ['title' => 'Agencies', 'copy' => 'Give each client a clear workspace, controlled knowledge and visible handoffs.'],
        ['title' => 'Support teams', 'copy' => 'Keep routine questions moving while specialists own sensitive conversations.'],
    ];
@endphp
<body class="marketing-shell overflow-x-hidden font-['Inter',sans-serif] antialiased">
<div x-data="{ menuOpen: false }" class="min-h-screen">
    <header class="relative z-40 border-b marketing-rule bg-[#f4f0e8]/95 backdrop-blur">
        <div class="mx-auto flex h-[74px] max-w-[1240px] items-center justify-between px-5 lg:px-8">
            <a href="{{ route('landing') }}" class="flex items-center gap-3" aria-label="Perpetual home">
                <span class="flex h-9 w-9 items-center justify-center bg-[#1d211f] text-sm font-extrabold text-[#f4f0e8]">P</span>
                <span class="text-[15px] font-extrabold tracking-[-.02em]">PERPETUAL</span>
            </a>
            <nav class="hidden items-center gap-8 text-sm font-semibold text-[#545954] lg:flex" aria-label="Main navigation">
                <a href="#product" class="hover:text-[#1d211f]">Product</a>
                <a href="#workflow" class="hover:text-[#1d211f]">How it works</a>
                <a href="#control" class="hover:text-[#1d211f]">Control</a>
                <a href="#use-cases" class="hover:text-[#1d211f]">Use cases</a>
                <a href="#pricing" class="hover:text-[#1d211f]">Pricing</a>
            </nav>
            <div class="hidden items-center gap-5 lg:flex">
                <a href="{{ $primaryUrl }}" class="text-sm font-semibold text-[#545954] hover:text-[#1d211f]">Log in</a>
                <a href="{{ $primaryUrl }}" class="marketing-button marketing-button-primary">{{ $primaryLabel }}</a>
            </div>
            <button type="button" @click="menuOpen = !menuOpen" class="flex h-11 w-11 items-center justify-center border marketing-rule lg:hidden" aria-label="Toggle navigation" :aria-expanded="menuOpen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
        <div x-cloak x-show="menuOpen" class="border-t marketing-rule bg-[#f4f0e8] px-5 py-5 lg:hidden">
            <nav class="grid gap-1 text-sm font-semibold">
                <a @click="menuOpen=false" href="#product" class="border-b marketing-rule py-3">Product</a>
                <a @click="menuOpen=false" href="#workflow" class="border-b marketing-rule py-3">How it works</a>
                <a @click="menuOpen=false" href="#control" class="border-b marketing-rule py-3">Control</a>
                <a @click="menuOpen=false" href="#use-cases" class="border-b marketing-rule py-3">Use cases</a>
                <a href="{{ $primaryUrl }}" class="marketing-button marketing-button-primary mt-4">{{ $primaryLabel }}</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="marketing-grid border-b marketing-rule">
            <div class="mx-auto max-w-[1240px] px-5 pb-16 pt-16 sm:pt-20 lg:px-8 lg:pb-24 lg:pt-28">
                <div class="grid items-end gap-12 lg:grid-cols-[.86fr_1.14fr] lg:gap-16">
                    <div class="reveal">
                        <p class="marketing-kicker">One workspace. Every conversation.</p>
                        <h1 class="mt-5 max-w-[680px] text-[clamp(3rem,7vw,6.4rem)] font-semibold leading-[.93] tracking-[-.065em]">Your customer conversations, finally under control.</h1>
                        <p class="mt-7 max-w-xl text-[17px] leading-7 text-[#626761] sm:text-lg">Bring social messages and email into one focused inbox. Let a business-trained AI handle the routine, while your team keeps the final say.</p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ $primaryUrl }}" class="marketing-button marketing-button-primary">{{ $primaryLabel }} <span aria-hidden="true">→</span></a>
                            <a href="#product" class="marketing-button marketing-button-secondary">See the product</a>
                        </div>
                        <p class="mt-5 text-xs font-medium text-[#777b76]">Google sign-in · Set up in minutes · Human takeover built in</p>
                    </div>

                    <div class="relative lg:translate-y-8">
                        <div class="absolute -left-5 top-8 hidden h-[72%] w-px bg-[#b94f36] lg:block"></div>
                        <div class="product-frame overflow-hidden border border-[#cbc3b6] bg-[#f8f6f1]">
                            <div class="flex h-11 items-center justify-between border-b border-[#d9d3c8] bg-[#ebe6dc] px-4">
                                <div class="flex gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#c2b9aa]"></span><span class="h-2.5 w-2.5 rounded-full bg-[#c2b9aa]"></span><span class="h-2.5 w-2.5 rounded-full bg-[#c2b9aa]"></span></div>
                                <span class="text-[10px] font-bold uppercase tracking-[.16em] text-[#77736d]">Live workspace</span>
                            </div>
                            <div class="grid min-h-[480px] md:grid-cols-[238px_1fr]">
                                <div class="border-b border-[#ddd7cc] bg-[#fdfcf9] md:border-b-0 md:border-r">
                                    <div class="border-b border-[#e2ddd4] p-3">
                                        <div class="flex h-9 items-center gap-2 border border-[#ddd7cc] bg-white px-3 text-xs text-[#8a8d88]"><span>⌕</span> Search conversations</div>
                                        <div class="mt-3 flex gap-1.5">
                                            @foreach ($channels as $channel)
                                                <span data-platform-icon="{{ $channel['icon'] }}" class="flex h-4 w-4 items-center justify-center" aria-label="{{ $channel['name'] }}"></span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        @foreach ($inboxRows as $row)
                                            <div class="border-b border-[#e6e1d9] p-3 {{ ($row['active'] ?? false) ? 'bg-[#f0e7dc]' : '' }}">
                                                <div class="flex items-center justify-between gap-2"><span class="truncate text-xs font-bold">{{ $row['name'] }}</span><span class="text-[9px] text-[#8b8d89]">{{ $row['time'] }}</span></div>
                                                <p class="mt-1 truncate text-[10px] text-[#777b76]">{{ $row['copy'] }}</p>
                                                <div class="mt-2 flex items-center gap-1.5"><span data-platform-icon="{{ $row['icon'] }}" class="h-3.5 w-3.5" aria-label="{{ $row['channel'] }}"></span><span class="border border-[#ded8cf] bg-white px-1.5 py-0.5 text-[8px] font-semibold text-[#696d68]">{{ $row['state'] }}</span></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="flex min-h-[420px] flex-col bg-[#f3f0ea]">
                                    <div class="flex items-center justify-between border-b border-[#ddd7cc] bg-[#fdfcf9] px-4 py-3">
                                        <div><p class="text-xs font-bold">Amaka N.</p><p class="text-[9px] text-[#737772]">Instagram · Needs reply</p></div>
                                        <span class="border border-[#cfc8bd] px-2 py-1 text-[9px] font-bold">Take over</span>
                                    </div>
                                    <div class="flex-1 space-y-3 p-4">
                                        <div class="max-w-[82%] bg-white p-3 text-[11px] leading-5 shadow-sm">Hi, do you offer interior detailing for a Toyota Camry?</div>
                                        <div class="ml-auto max-w-[86%] bg-[#53645a] p-3 text-[11px] leading-5 text-white"><span class="mb-1 block text-[8px] font-bold uppercase tracking-[.12em] text-[#dce6df]">AI assistant</span>Yes. Our full interior detail covers seats, carpets, dashboard and finishing. Would you like a weekday or Saturday appointment?</div>
                                        <div class="max-w-[82%] bg-white p-3 text-[11px] leading-5 shadow-sm">Saturday works. What time do you have?</div>
                                    </div>
                                    <div class="border-t border-[#ddd7cc] bg-[#fdfcf9] p-3">
                                        <div class="flex items-center justify-between border border-[#ddd7cc] bg-white px-3 py-2.5 text-[10px] text-[#878a85]"><span>Write a reply…</span><span class="font-bold text-[#53645a]">Send</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-b marketing-rule bg-[#fbf9f4]">
            <div class="mx-auto flex max-w-[1240px] flex-col gap-7 px-5 py-9 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <p class="max-w-sm text-sm font-semibold leading-6">Connect the channels your customers already use.</p>
                <div class="flex flex-wrap gap-x-7 gap-y-4">
                    @foreach ($channels as $channel)
                        <div class="flex items-center gap-2 text-sm font-semibold text-[#656a65]"><span data-platform-icon="{{ $channel['icon'] }}" class="h-5 w-5" aria-label="{{ $channel['name'] }}"></span>{{ $channel['name'] }}</div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="workflow" class="border-b marketing-rule">
            <div class="mx-auto max-w-[1240px] px-5 py-20 lg:px-8 lg:py-28">
                <div class="grid gap-12 lg:grid-cols-[.65fr_1.35fr]">
                    <div><p class="marketing-kicker">The operating model</p><h2 class="mt-4 text-4xl font-semibold leading-tight tracking-[-.045em] sm:text-5xl">Connect.<br>Understand.<br>Respond.</h2></div>
                    <div class="border-t marketing-rule">
                        @foreach ([
                            ['01', 'Connect the channels', 'Add the accounts your business already runs. Every new message enters one accountable workspace.'],
                            ['02', 'Teach the assistant', 'Give it approved services, prices, policies, FAQs and a clear brand voice—not vague instructions.'],
                            ['03', 'Respond with control', 'The assistant handles routine questions. Your team reviews, takes over and closes the conversations that need judgment.'],
                        ] as $step)
                            <article class="grid gap-4 border-b marketing-rule py-7 sm:grid-cols-[70px_220px_1fr] sm:items-start">
                                <span class="font-mono text-xs text-[#9a6254]">{{ $step[0] }}</span><h3 class="text-lg font-bold">{{ $step[1] }}</h3><p class="max-w-xl text-sm leading-6 text-[#666b66]">{{ $step[2] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="product" class="border-b marketing-rule bg-[#1d211f] text-[#f4f0e8]">
            <div class="mx-auto max-w-[1240px] px-5 py-20 lg:px-8 lg:py-28">
                <div class="grid items-start gap-12 lg:grid-cols-2 lg:gap-20">
                    <div class="lg:sticky lg:top-24">
                        <p class="text-xs font-bold uppercase tracking-[.16em] text-[#d07b64]">Unified inbox</p>
                        <h2 class="mt-5 max-w-xl text-4xl font-semibold leading-[1.05] tracking-[-.045em] sm:text-5xl">One queue your whole team can actually understand.</h2>
                        <p class="mt-6 max-w-lg text-base leading-7 text-[#b7bcb6]">See who wrote, where they came from, what needs a reply and who is handling it—without bouncing between apps.</p>
                        <ul class="mt-8 grid gap-4 text-sm text-[#d7dbd5] sm:grid-cols-2">
                            @foreach (['Channel and status filters', 'Customer context', 'Unread tracking', 'Human and AI ownership', 'Safe attachment handling', 'Fast conversation search'] as $item)
                                <li class="flex gap-3 border-t border-white/15 pt-3"><span class="text-[#d07b64]">—</span>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="border border-white/15 bg-[#272c29] p-3 sm:p-5">
                        <div class="border border-white/10 bg-[#f7f4ed] text-[#1d211f]">
                            <div class="grid grid-cols-[1fr_auto] border-b border-[#ded8cd] p-4"><div><p class="text-xs font-bold">Today’s conversations</p><p class="mt-1 text-[10px] text-[#777b76]">Across five connected channels</p></div><span class="text-2xl font-semibold">27</span></div>
                            @foreach ($inboxRows as $row)
                                <div class="grid grid-cols-[34px_1fr_auto] gap-3 border-b border-[#e4ded5] p-3 last:border-0">
                                    <span data-platform-icon="{{ $row['icon'] }}" class="flex h-8 w-8 items-center justify-center bg-[#ebe4d9] p-2" aria-label="{{ $row['channel'] }}"></span>
                                    <div class="min-w-0"><p class="truncate text-xs font-bold">{{ $row['name'] }}</p><p class="mt-1 truncate text-[10px] text-[#777b76]">{{ $row['copy'] }}</p></div>
                                    <span class="self-center text-[9px] font-semibold text-[#777b76]">{{ $row['state'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="control" class="border-b marketing-rule bg-[#fbf9f4]">
            <div class="mx-auto max-w-[1240px] px-5 py-20 lg:px-8 lg:py-28">
                <div class="max-w-3xl"><p class="marketing-kicker">AI, with boundaries</p><h2 class="mt-5 text-4xl font-semibold leading-[1.05] tracking-[-.045em] sm:text-5xl">Useful enough to act.<br>Controlled enough to trust.</h2><p class="mt-6 max-w-2xl text-base leading-7 text-[#666b66]">Perpetual does not ask you to hand over the business. You define what the assistant knows, how it speaks and when a person should step in.</p></div>
                <div class="mt-14 grid border-y marketing-rule lg:grid-cols-3">
                    @foreach ([
                        ['Business knowledge', 'Approved answers, services, prices and policies ground every response.'],
                        ['Behaviour rules', 'Set tone, escalation triggers, business hours and the situations AI must not guess about.'],
                        ['Human takeover', 'Switch any conversation to your team instantly, reply manually and resume AI only when you choose.'],
                    ] as $item)
                        <article class="border-b marketing-rule py-8 lg:border-b-0 lg:border-r lg:px-8 lg:first:pl-0 lg:last:border-r-0">
                            <span class="mb-12 block h-2 w-10 bg-[#b94f36]"></span><h3 class="text-xl font-bold tracking-[-.025em]">{{ $item[0] }}</h3><p class="mt-4 text-sm leading-6 text-[#666b66]">{{ $item[1] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="use-cases" class="border-b marketing-rule">
            <div class="mx-auto max-w-[1240px] px-5 py-20 lg:px-8 lg:py-28">
                <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><p class="marketing-kicker">Built for real operations</p><h2 class="mt-4 text-4xl font-semibold tracking-[-.045em] sm:text-5xl">For teams with messages to answer.</h2></div><p class="max-w-sm text-sm leading-6 text-[#666b66]">Not another dashboard to watch. A working queue for the conversations that move your business.</p></div>
                <div class="mt-12 grid border-l border-t marketing-rule sm:grid-cols-2">
                    @foreach ($useCases as $index => $case)
                        <article class="min-h-[220px] border-b border-r marketing-rule p-6 sm:p-8">
                            <span class="font-mono text-xs text-[#9a6254]">0{{ $index + 1 }}</span><h3 class="mt-12 text-2xl font-semibold tracking-[-.035em]">{{ $case['title'] }}</h3><p class="mt-4 max-w-md text-sm leading-6 text-[#666b66]">{{ $case['copy'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="pricing" class="border-b marketing-rule bg-[#e9e2d6]">
            <div class="mx-auto grid max-w-[1240px] gap-10 px-5 py-20 lg:grid-cols-[1fr_.8fr] lg:px-8 lg:py-24">
                <div><p class="marketing-kicker">Early access</p><h2 class="mt-4 max-w-2xl text-4xl font-semibold leading-[1.05] tracking-[-.045em] sm:text-5xl">Start with the workflow. Scale when the volume does.</h2></div>
                <div class="border-l border-[#cfc5b7] pl-6 sm:pl-10"><p class="text-base leading-7 text-[#5e635e]">Create a workspace, connect a channel and shape the assistant around your business. Formal public pricing will follow the production release.</p><a href="{{ $primaryUrl }}" class="marketing-button marketing-button-primary mt-7">{{ $primaryLabel }} <span>→</span></a></div>
            </div>
        </section>

        <section class="bg-[#b94f36] text-white">
            <div class="mx-auto max-w-[1240px] px-5 py-20 lg:px-8 lg:py-28">
                <div class="grid items-end gap-10 lg:grid-cols-[1fr_auto]"><div><p class="text-xs font-bold uppercase tracking-[.16em] text-white/70">Bring the inbox together</p><h2 class="mt-5 max-w-4xl text-5xl font-semibold leading-[.98] tracking-[-.055em] sm:text-6xl lg:text-7xl">Give every conversation a clear next step.</h2></div><a href="{{ $primaryUrl }}" class="marketing-button bg-[#1d211f] text-white hover:bg-black">{{ $primaryLabel }} <span>→</span></a></div>
            </div>
        </section>
    </main>

    <footer class="bg-[#1d211f] text-[#d4d7d2]">
        <div class="mx-auto max-w-[1240px] px-5 py-12 lg:px-8">
            <div class="flex flex-col gap-8 border-b border-white/15 pb-10 sm:flex-row sm:items-start sm:justify-between"><div><p class="font-extrabold tracking-[-.02em] text-white">PERPETUAL</p><p class="mt-3 max-w-xs text-sm leading-6 text-[#929892]">Customer conversations, handled with context and control.</p></div><div class="grid grid-cols-2 gap-x-12 gap-y-3 text-sm"><a href="#product" class="hover:text-white">Product</a><a href="{{ route('legal.privacy') }}" class="hover:text-white">Privacy</a><a href="#use-cases" class="hover:text-white">Use cases</a><a href="{{ route('legal.terms') }}" class="hover:text-white">Terms</a><a href="#pricing" class="hover:text-white">Pricing</a><a href="{{ route('legal.data-deletion') }}" class="hover:text-white">Data deletion</a></div></div>
            <div class="flex flex-col gap-2 pt-7 text-xs text-[#858b85] sm:flex-row sm:justify-between"><p>© {{ date('Y') }} Perpetual. All rights reserved.</p><p>Built for focused customer operations.</p></div>
        </div>
    </footer>
</div>
</body>
</html>
