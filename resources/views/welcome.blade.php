<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Perpetual Inbox AI</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @keyframes landing-fade-up {
                from { opacity: 0; transform: translateY(18px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .landing-fade-up {
                animation: landing-fade-up 680ms ease both;
            }
        </style>
    </head>
    <body class="overflow-x-hidden bg-[#FAFAF8] font-['Inter',sans-serif] text-[#101828] antialiased">
        @php
            $conversationStates = [
                ['label' => 'All', 'count' => '24', 'tone' => 'bg-[#163B57] text-white'],
                ['label' => 'Needs Human', 'count' => '3', 'tone' => 'bg-red-50 text-red-700'],
                ['label' => 'Auto Sorted', 'count' => '8', 'tone' => 'bg-blue-50 text-blue-700'],
                ['label' => 'Waiting', 'count' => '12', 'tone' => 'bg-amber-50 text-amber-700'],
                ['label' => 'Closed', 'count' => '41', 'tone' => 'bg-slate-100 text-slate-600'],
            ];

            $inboxRows = [
                ['name' => 'Kemi Adebayo', 'channel' => 'Instagram', 'message' => 'I want to file a complaint about yesterday service.', 'state' => 'Needs Human', 'time' => '10:14', 'dot' => 'bg-red-500', 'text' => 'text-red-700'],
                ['name' => 'Daniel Okafor', 'channel' => 'WhatsApp', 'message' => 'What does ceramic coating cost?', 'state' => 'Waiting', 'time' => '09:56', 'dot' => 'bg-amber-400', 'text' => 'text-amber-700'],
                ['name' => 'Simi James', 'channel' => 'Facebook', 'message' => 'Can you detail my SUV at home?', 'state' => 'Auto Sorted', 'time' => '09:38', 'dot' => 'bg-blue-500', 'text' => 'text-blue-700'],
                ['name' => 'Bola Sanni', 'channel' => 'Instagram', 'message' => 'Resolved, thank you.', 'state' => 'Closed', 'time' => '09:20', 'dot' => 'bg-slate-400', 'text' => 'text-slate-500'],
            ];

            $problemCards = [
                ['title' => 'Instagram DMs get missed', 'copy' => 'Customer requests sit inside social inboxes while staff switch between apps.', 'icon' => 'chat'],
                ['title' => 'WhatsApp threads are hard to track', 'copy' => 'Approvals, prices and booking details disappear inside long message histories.', 'icon' => 'layers'],
                ['title' => 'Teams repeat the same replies', 'copy' => 'Staff spend hours answering hours, prices, availability and policy questions.', 'icon' => 'repeat'],
            ];

            $stateCards = [
                ['label' => 'Needs Human', 'copy' => 'Discounts, complaints, custom quotes and sensitive issues are flagged for staff.', 'bar' => 'bg-red-500', 'badge' => 'bg-red-50 text-red-700'],
                ['label' => 'Auto Sorted', 'copy' => 'Routine enquiries are organized and assisted using your business knowledge base.', 'bar' => 'bg-blue-500', 'badge' => 'bg-blue-50 text-blue-700'],
                ['label' => 'Waiting', 'copy' => 'Track conversations where a reply has been sent and the customer needs to respond.', 'bar' => 'bg-amber-400', 'badge' => 'bg-amber-50 text-amber-700'],
                ['label' => 'Closed', 'copy' => 'Resolved conversations are archived so your team stays focused on active work.', 'bar' => 'bg-slate-400', 'badge' => 'bg-slate-100 text-slate-700'],
            ];

            $features = [
                'Unified inbox',
                'Smart routing',
                'Suggested replies',
                'Human takeover',
                'Knowledge base',
                'Team collaboration',
                'Customer history',
                'Connected accounts',
            ];

            $steps = [
                ['title' => 'Create your workspace', 'copy' => 'Set up a business profile for each team or location.'],
                ['title' => 'Connect your channels', 'copy' => 'Bring Instagram, Facebook and WhatsApp messages into one queue.'],
                ['title' => 'Add business knowledge', 'copy' => 'Load FAQs, prices, policies, services and approval rules.'],
                ['title' => 'Route work clearly', 'copy' => 'Routine replies move smoothly while staff get the threads that need judgment.'],
            ];

            $useCases = [
                ['name' => 'Car rentals', 'copy' => 'Availability, booking requirements, deposits, pickup times and discount requests.'],
                ['name' => 'Car detailers', 'copy' => 'Service pricing, location questions, complaints and appointment follow-ups.'],
                ['name' => 'Salons', 'copy' => 'Appointments, service menus, prices, stylist availability and late arrivals.'],
                ['name' => 'Real estate', 'copy' => 'Viewing requests, property questions, documents and lead qualification.'],
                ['name' => 'Clinics', 'copy' => 'Appointment questions, basic policies, reminders and staff handoff.'],
                ['name' => 'Restaurants', 'copy' => 'Reservations, menu questions, delivery issues and event enquiries.'],
            ];

            $pricing = [
                ['name' => 'Starter', 'price' => 'NGN 5,000/mo', 'copy' => 'For solo operators managing a small message queue.', 'items' => ['1 workspace', 'Basic inbox states', 'Knowledge base starter']],
                ['name' => 'Growth', 'price' => 'NGN 10,000/mo', 'copy' => 'For teams that need routing, visibility and human handoff.', 'items' => ['Multiple connected accounts', 'Smart routing rules', 'Team queue visibility'], 'featured' => true],
                ['name' => 'Pro', 'price' => 'NGN 15,000/mo', 'copy' => 'For businesses with higher volume and more controls.', 'items' => ['Advanced workflows', 'Customer history', 'Priority setup support']],
            ];

            $faqs = [
                ['q' => 'Does this replace my staff?', 'a' => 'No. It supports routine communication and flags conversations that need human judgment.'],
                ['q' => 'Can my staff still reply manually?', 'a' => 'Yes. Staff can take over any conversation and send approved replies.'],
                ['q' => 'What happens when the system is unsure?', 'a' => 'The conversation is moved to a human queue instead of guessing.'],
                ['q' => 'Which channels can I connect?', 'a' => 'The product is designed around Instagram, Facebook and WhatsApp conversations.'],
                ['q' => 'Do I need technical setup?', 'a' => 'No-code setup is the goal. You create a workspace, connect channels and add business knowledge.'],
                ['q' => 'Is this for Nigerian businesses?', 'a' => 'Yes. The workflows, pricing examples and use cases are designed with service businesses in Nigeria in mind.'],
            ];
        @endphp

        <header class="sticky top-0 z-40 border-b border-[#E5E7EB] bg-[#FAFAF8]/92 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('landing') }}" class="flex min-w-0 items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#163B57] text-sm font-extrabold text-white shadow-sm">PI</span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-extrabold text-[#111827] sm:text-base">Perpetual Inbox AI</span>
                        <span class="block truncate text-xs font-semibold text-[#667085]">Customer operations</span>
                    </span>
                </a>

                <nav class="hidden items-center gap-7 text-sm font-semibold text-[#475467] lg:flex">
                    <a href="#product" class="transition hover:text-[#111827]">Product</a>
                    <a href="#how-it-works" class="transition hover:text-[#111827]">How it works</a>
                    <a href="#pricing" class="transition hover:text-[#111827]">Pricing</a>
                    <a href="#faq" class="transition hover:text-[#111827]">FAQ</a>
                </nav>

                <div class="flex shrink-0 items-center gap-2 text-sm font-semibold">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-xl bg-[#163B57] px-4 py-2.5 text-white shadow-sm transition hover:bg-[#244E73]">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden rounded-xl px-3 py-2 text-[#344054] transition hover:bg-white sm:inline-flex">Login</a>
                        <a href="{{ route('register') }}" class="rounded-xl bg-[#163B57] px-4 py-2.5 text-white shadow-sm transition hover:bg-[#244E73]">Start Free Trial</a>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden border-b border-[#E5E7EB] bg-[#FAFAF8]">
                <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-24">
                    <div class="landing-fade-up">
                        <p class="inline-flex rounded-full border border-[#D0D5DD] bg-white px-3 py-1.5 text-xs font-bold text-[#163B57] shadow-sm">Customer communication for service teams</p>
                        <h1 class="mt-6 max-w-3xl text-4xl font-extrabold leading-[1.05] tracking-[-0.02em] text-[#101828] sm:text-5xl lg:text-6xl">Every customer conversation, organized in one inbox.</h1>
                        <p class="mt-6 max-w-2xl text-base leading-8 text-[#667085] sm:text-lg">Bring Instagram, Facebook and WhatsApp messages into one workspace. Let routine enquiries flow smoothly while your team focuses on conversations that need attention.</p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-[#163B57] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#244E73]">Open Dashboard</a>
                            @else
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-[#163B57] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#244E73]">Start Free Trial</a>
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-[#D0D5DD] bg-white px-5 py-3 text-sm font-bold text-[#344054] shadow-sm transition hover:-translate-y-0.5 hover:bg-[#F9FAFB]">View Demo</a>
                            @endauth
                        </div>

                        <div class="mt-8 grid max-w-2xl grid-cols-2 gap-3 text-sm font-semibold text-[#475467] sm:grid-cols-4">
                            @foreach (['Unified inbox', 'Human takeover', 'Team visibility', 'Instagram, Facebook & WhatsApp'] as $benefit)
                                <div class="rounded-xl border border-[#E5E7EB] bg-white px-3 py-3 shadow-sm">{{ $benefit }}</div>
                            @endforeach
                        </div>
                    </div>

                    <div class="landing-fade-up min-w-0 lg:pl-4" style="animation-delay: 120ms;">
                        <div class="overflow-hidden rounded-[1.5rem] border border-[#D0D5DD] bg-white shadow-2xl shadow-slate-200/80">
                            <div class="border-b border-[#E5E7EB] bg-white px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-3 w-3 rounded-full bg-[#E5484D]"></span>
                                        <span class="h-3 w-3 rounded-full bg-[#F5C542]"></span>
                                        <span class="h-3 w-3 rounded-full bg-[#1EAD6B]"></span>
                                    </div>
                                    <p class="hidden text-xs font-semibold text-[#667085] sm:block">Customer operations preview</p>
                                </div>
                            </div>

                            <div class="grid min-h-[500px] bg-[#F7F8FA] lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
                                <section class="min-w-0 border-r border-[#E5E7EB] bg-white">
                                    <div class="border-b border-[#E5E7EB] p-4">
                                        <div class="flex items-center gap-3 rounded-xl border border-[#E5E7EB] bg-[#F9FAFB] px-4 py-3 text-sm text-[#667085]">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 shrink-0">
                                                <path d="m21 21-4.35-4.35"></path>
                                                <circle cx="11" cy="11" r="7"></circle>
                                            </svg>
                                            <span class="truncate">Search conversations</span>
                                        </div>
                                        <div class="mt-3 flex gap-2 overflow-hidden">
                                            @foreach ($conversationStates as $state)
                                                <span class="shrink-0 rounded-full px-3 py-1.5 text-xs font-bold {{ $state['tone'] }}">{{ $state['label'] }} {{ $state['count'] }}</span>
                                            @endforeach
                                        </div>
                                    </div>

                                    @foreach ($inboxRows as $row)
                                        <div class="border-b border-[#EEF2F6] p-4">
                                            <div class="flex min-w-0 gap-3">
                                                <span class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#F2F4F7] text-sm font-extrabold text-[#344054]">
                                                    {{ substr($row['name'], 0, 1) }}
                                                    <span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white {{ $row['dot'] }}"></span>
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <p class="truncate font-bold text-[#101828]">{{ $row['name'] }}</p>
                                                        <span class="shrink-0 text-xs font-semibold text-[#98A2B3]">{{ $row['time'] }}</span>
                                                    </div>
                                                    <p class="mt-1 truncate text-sm text-[#667085]">{{ $row['message'] }}</p>
                                                    <p class="mt-2 text-xs font-bold {{ $row['text'] }}">{{ $row['state'] }} <span class="font-medium text-[#98A2B3]">/ {{ $row['channel'] }}</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </section>

                                <section class="hidden min-w-0 flex-col bg-[#101828] text-white sm:flex">
                                    <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                                        <div class="min-w-0">
                                            <p class="truncate font-bold">Kemi Adebayo</p>
                                            <p class="text-xs font-semibold text-red-300">Needs Human / Instagram</p>
                                        </div>
                                        <span class="rounded-full bg-red-500/15 px-3 py-1 text-xs font-bold text-red-200">Review needed</span>
                                    </div>
                                    <div class="flex-1 space-y-4 p-5">
                                        <div class="max-w-md rounded-2xl bg-white/8 p-4 text-sm">
                                            <p class="text-xs font-bold uppercase text-slate-400">Customer</p>
                                            <p class="mt-2 leading-6">I want to file a complaint about yesterday service.</p>
                                        </div>
                                        <div class="ml-auto max-w-md rounded-2xl bg-[#173B57] p-4 text-sm">
                                            <p class="text-xs font-bold uppercase text-blue-200">Routing note</p>
                                            <p class="mt-2 leading-6">Complaint detected. The conversation was moved to staff review.</p>
                                        </div>
                                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                            <p class="text-xs font-bold uppercase text-slate-400">Customer profile</p>
                                            <div class="mt-3 grid gap-2 text-sm text-slate-300">
                                                <p>Channel: Instagram</p>
                                                <p>Intent: Complaint</p>
                                                <p>Suggested action: Review policy match</p>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <p class="text-sm font-bold text-[#163B57]">The problem</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.02em] text-[#101828] sm:text-4xl">Customer messages are scattered everywhere.</h2>
                    </div>

                    <div class="mt-10 grid gap-4 md:grid-cols-3">
                        @foreach ($problemCards as $card)
                            <article class="rounded-2xl border border-[#E5E7EB] bg-[#FAFAF8] p-6 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/60">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-[#E5E7EB] bg-white text-[#163B57]">
                                    @if ($card['icon'] === 'chat')
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/></svg>
                                    @elseif ($card['icon'] === 'layers')
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/></svg>
                                    @else
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                                    @endif
                                </div>
                                <h3 class="mt-5 text-lg font-bold text-[#101828]">{{ $card['title'] }}</h3>
                                <p class="mt-3 text-sm leading-6 text-[#667085]">{{ $card['copy'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="product" class="bg-[#F7F8FA] py-16 sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                        <div>
                            <p class="text-sm font-bold text-[#163B57]">Conversation states</p>
                            <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.02em] text-[#101828] sm:text-4xl">The inbox is organized before your team opens it.</h2>
                            <p class="mt-5 text-base leading-8 text-[#667085]">Every conversation gets a clear state so your team knows what needs attention, what is waiting, and what is already resolved.</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($stateCards as $card)
                                <article class="overflow-hidden rounded-2xl border border-[#E5E7EB] bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/70">
                                    <div class="h-1.5 {{ $card['bar'] }}"></div>
                                    <div class="p-6">
                                        <span class="rounded-full px-3 py-1.5 text-xs font-bold {{ $card['badge'] }}">{{ $card['label'] }}</span>
                                        <p class="mt-5 text-sm leading-6 text-[#667085]">{{ $card['copy'] }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white py-16 sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl text-center">
                        <p class="text-sm font-bold text-[#163B57]">Product features</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.02em] text-[#101828] sm:text-4xl">Built around the way real businesses reply to customers.</h2>
                    </div>

                    <div class="mt-12 grid gap-8 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($features as $feature)
                                <div class="flex items-center gap-3 rounded-2xl border border-[#E5E7EB] bg-[#FAFAF8] p-4">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#EEF4FA] text-[#163B57]">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M20 6 9 17l-5-5"/></svg>
                                    </span>
                                    <span class="text-sm font-bold text-[#344054]">{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="rounded-[1.5rem] border border-[#D0D5DD] bg-[#101828] p-4 shadow-2xl shadow-slate-200">
                            <div class="rounded-2xl bg-white p-5">
                                <div class="flex items-center justify-between gap-3 border-b border-[#E5E7EB] pb-4">
                                    <div>
                                        <p class="text-sm font-bold text-[#101828]">Suggested reply</p>
                                        <p class="mt-1 text-xs text-[#667085]">Based on FAQs, pricing and business rules</p>
                                    </div>
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">92% match</span>
                                </div>
                                <div class="mt-5 rounded-2xl bg-[#F7F8FA] p-4 text-sm leading-6 text-[#344054]">
                                    Thanks for reaching out. Ceramic coating starts from NGN 55,000. I can share available slots and send this to a teammate if you need a custom quote.
                                </div>
                                <div class="mt-5 flex flex-wrap gap-2">
                                    <span class="rounded-full bg-[#EEF4FA] px-3 py-1.5 text-xs font-bold text-[#163B57]">Use reply</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">Edit</span>
                                    <span class="rounded-full bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700">Take over</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="how-it-works" class="border-y border-[#E5E7EB] bg-[#FAFAF8] py-16 sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <p class="text-sm font-bold text-[#163B57]">How it works</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.02em] text-[#101828] sm:text-4xl">Launch a managed inbox without a technical project.</h2>
                    </div>

                    <div class="mt-12 grid gap-4 md:grid-cols-4">
                        @foreach ($steps as $index => $step)
                            <article class="rounded-2xl border border-[#E5E7EB] bg-white p-6 shadow-sm">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#163B57] text-sm font-extrabold text-white">{{ $index + 1 }}</span>
                                <h3 class="mt-5 text-base font-bold text-[#101828]">{{ $step['title'] }}</h3>
                                <p class="mt-3 text-sm leading-6 text-[#667085]">{{ $step['copy'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="bg-white py-16 sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl text-center">
                        <p class="text-sm font-bold text-[#163B57]">Use cases</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.02em] text-[#101828] sm:text-4xl">Made for service businesses that live in DMs.</h2>
                    </div>

                    <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($useCases as $case)
                            <article class="rounded-2xl border border-[#E5E7EB] bg-[#FAFAF8] p-6">
                                <h3 class="text-lg font-bold text-[#101828]">{{ $case['name'] }}</h3>
                                <p class="mt-3 text-sm leading-6 text-[#667085]">{{ $case['copy'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="bg-[#101828] py-16 text-white sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-10 lg:grid-cols-[0.7fr_1.3fr] lg:items-center">
                        <div>
                            <p class="text-sm font-bold text-blue-200">Product preview</p>
                            <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.02em] sm:text-4xl">Handoffs, history and customer context in one place.</h2>
                            <p class="mt-5 text-base leading-8 text-slate-300">See inbox tabs, colored conversation states, customer history and suggested staff actions without switching between apps.</p>
                        </div>

                        <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.04] p-4 shadow-2xl shadow-black/30">
                            <div class="grid overflow-hidden rounded-2xl border border-white/10 bg-[#0B1116] md:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)_240px]">
                                <div class="border-b border-white/10 p-4 md:border-b-0 md:border-r">
                                    <div class="grid grid-cols-2 gap-2 text-xs font-bold sm:grid-cols-3">
                                        <span class="rounded-lg bg-[#163B57] px-3 py-2 text-center">All</span>
                                        <span class="rounded-lg bg-red-500/15 px-3 py-2 text-center text-red-200">Human</span>
                                        <span class="rounded-lg bg-blue-500/15 px-3 py-2 text-center text-blue-200">Auto</span>
                                    </div>
                                    <div class="mt-4 space-y-3">
                                        @foreach (array_slice($inboxRows, 0, 3) as $row)
                                            <div class="rounded-xl bg-white/[0.04] p-3">
                                                <p class="truncate text-sm font-bold">{{ $row['name'] }}</p>
                                                <p class="mt-1 truncate text-xs text-slate-400">{{ $row['message'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="rounded-2xl bg-white/[0.05] p-4">
                                        <p class="text-xs font-bold uppercase text-slate-400">Customer message</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-100">Can I get a discount if I book two SUVs this weekend?</p>
                                    </div>
                                    <div class="ml-auto mt-4 rounded-2xl bg-[#173B57] p-4">
                                        <p class="text-xs font-bold uppercase text-blue-200">Smart handoff</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-100">Discount request detected. Moved to Needs Human with suggested policy context.</p>
                                    </div>
                                </div>
                                <aside class="hidden border-l border-white/10 p-4 md:block">
                                    <p class="text-sm font-bold">Customer profile</p>
                                    <div class="mt-4 space-y-3 text-xs text-slate-300">
                                        <div class="rounded-xl bg-white/[0.05] p-3">Channel: Instagram</div>
                                        <div class="rounded-xl bg-white/[0.05] p-3">Intent: Discount</div>
                                        <div class="rounded-xl bg-white/[0.05] p-3">Action: Staff approval</div>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="pricing" class="bg-[#FAFAF8] py-16 sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl text-center">
                        <p class="text-sm font-bold text-[#163B57]">Pricing preview</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.02em] text-[#101828] sm:text-4xl">Simple plans for the launch version.</h2>
                        <p class="mt-4 text-sm text-[#667085]">Final launch pricing may change.</p>
                    </div>

                    <div class="mt-12 grid gap-4 lg:grid-cols-3">
                        @foreach ($pricing as $plan)
                            <article class="rounded-2xl border {{ ($plan['featured'] ?? false) ? 'border-[#163B57] bg-white shadow-2xl shadow-slate-200' : 'border-[#E5E7EB] bg-white shadow-sm' }} p-6">
                                @if ($plan['featured'] ?? false)
                                    <span class="rounded-full bg-[#EEF4FA] px-3 py-1.5 text-xs font-bold text-[#163B57]">Most popular</span>
                                @endif
                                <h3 class="mt-5 text-xl font-extrabold text-[#101828]">{{ $plan['name'] }}</h3>
                                <p class="mt-3 text-3xl font-extrabold text-[#101828]">{{ $plan['price'] }}</p>
                                <p class="mt-3 text-sm leading-6 text-[#667085]">{{ $plan['copy'] }}</p>
                                <ul class="mt-6 space-y-3 text-sm text-[#475467]">
                                    @foreach ($plan['items'] as $item)
                                        <li class="flex gap-3">
                                            <span class="mt-1 h-2 w-2 rounded-full bg-[#163B57]"></span>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="faq" class="bg-white py-16 sm:py-24">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <p class="text-sm font-bold text-[#163B57]">FAQ</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.02em] text-[#101828] sm:text-4xl">Questions before you connect your inbox.</h2>
                    </div>

                    <div class="mt-10 divide-y divide-[#E5E7EB] rounded-2xl border border-[#E5E7EB] bg-[#FAFAF8]">
                        @foreach ($faqs as $faq)
                            <div class="p-6">
                                <h3 class="text-base font-bold text-[#101828]">{{ $faq['q'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-[#667085]">{{ $faq['a'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="bg-[#163B57] py-16 text-white sm:py-20">
                <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-8 px-4 sm:px-6 lg:flex-row lg:items-center lg:px-8">
                    <div class="max-w-2xl">
                        <h2 class="text-3xl font-extrabold tracking-[-0.02em] sm:text-4xl">Turn scattered DMs into a managed customer workflow.</h2>
                        <p class="mt-4 text-base leading-7 text-blue-50/85">Give your team one inbox, clear conversation states and a safer way to respond faster.</p>
                    </div>
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-bold text-[#163B57] shadow-sm transition hover:-translate-y-0.5">Open Dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-bold text-[#163B57] shadow-sm transition hover:-translate-y-0.5">Start Free Trial</a>
                    @endauth
                </div>
            </section>
        </main>
        <footer class="border-t border-[#E5E7EB] bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-6 text-sm text-[#667085] sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <span>&copy; {{ date('Y') }} Perpetual Inbox</span>
                <nav class="flex gap-5"><a class="hover:text-[#2563EB]" href="{{ route('legal.privacy') }}">Privacy</a><a class="hover:text-[#2563EB]" href="{{ route('legal.terms') }}">Terms</a><a class="hover:text-[#2563EB]" href="{{ route('legal.data-deletion') }}">Data deletion</a></nav>
            </div>
        </footer>
    </body>
</html>
