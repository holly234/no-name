<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Perpetual Inbox AI') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="overflow-x-hidden font-sans antialiased text-[#111827]">
        @if (isset($currentBusiness))
            @php
                $isInboxPage = request()->routeIs('dashboard.inbox');
                $navItems = collect([
                    ['label' => 'Inbox', 'route' => 'dashboard.inbox', 'icon' => 'inbox', 'roles' => ['owner', 'admin', 'agent']],
                    ['label' => 'Accounts', 'route' => 'dashboard.accounts', 'icon' => 'plug', 'roles' => ['owner', 'admin']],
                    ['label' => 'AI Assistant', 'route' => 'dashboard.ai-settings', 'icon' => 'sparkles', 'roles' => ['owner', 'admin']],
                    ['label' => 'Credits & Usage', 'route' => 'dashboard.ai-credits', 'icon' => 'wallet', 'roles' => ['owner']],
                    ['label' => 'Analytics', 'route' => 'dashboard.analytics', 'icon' => 'chart', 'roles' => ['owner', 'admin']],
                    ['label' => 'Team', 'route' => 'dashboard.team', 'icon' => 'users', 'roles' => ['owner', 'admin']],
                    ['label' => 'Settings', 'route' => 'dashboard.settings', 'icon' => 'settings', 'roles' => ['owner']],
                ])->filter(fn ($item) => in_array($currentWorkspaceRole, $item['roles'], true))->values()->all();
                $pageTitle = request()->routeIs('dashboard.knowledge-base*')
                    ? 'AI Assistant'
                    : data_get(collect($navItems)->first(fn ($item) => request()->routeIs($item['route'])), 'label', 'Overview');
            @endphp

            @php
                $navIcon = function (string $name): string {
                    $icons = [
                        'inbox' => '<path d="M4 4h16l2 10v6H2v-6L4 4Z"/><path d="M2 14h6a4 4 0 0 0 8 0h6"/>',
                        'plug' => '<path d="M9 7V3"/><path d="M15 7V3"/><path d="M7 7h10v4a5 5 0 0 1-10 0V7Z"/><path d="M12 16v5"/>',
                        'sparkles' => '<path d="m12 3 1.8 4.2L18 9l-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8L12 3Z"/><path d="m5 14 .9 2.1L8 17l-2.1.9L5 20l-.9-2.1L2 17l2.1-.9L5 14Z"/><path d="m19 14 .7 1.6 1.6.7-1.6.7-.7 1.6-.7-1.6-1.6-.7 1.6-.7.7-1.6Z"/>',
                        'book' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5v-16Z"/><path d="M4 19a2.5 2.5 0 0 1 2.5-2.5H20"/>',
                        'wallet' => '<path d="M3 6.5A2.5 2.5 0 0 1 5.5 4H19v16H5.5A2.5 2.5 0 0 1 3 17.5v-11Z"/><path d="M3 8h16"/><path d="M15 13h4"/>',
                        'chart' => '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/>',
                        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                        'settings' => '<path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z"/><path d="M19.4 15a1.8 1.8 0 0 0 .36 1.98l.04.04a2.1 2.1 0 1 1-2.97 2.97l-.04-.04a1.8 1.8 0 0 0-1.98-.36 1.8 1.8 0 0 0-1.1 1.66V21a2.1 2.1 0 1 1-4.2 0v-.06a1.8 1.8 0 0 0-1.18-1.66 1.8 1.8 0 0 0-1.98.36l-.04.04a2.1 2.1 0 1 1-2.97-2.97l.04-.04A1.8 1.8 0 0 0 3 14.7a1.8 1.8 0 0 0-1.66-1.1H1.3a2.1 2.1 0 1 1 0-4.2h.06A1.8 1.8 0 0 0 3 8.22a1.8 1.8 0 0 0-.36-1.98l-.04-.04a2.1 2.1 0 1 1 2.97-2.97l.04.04A1.8 1.8 0 0 0 7.6 3a1.8 1.8 0 0 0 1.1-1.66V1.3a2.1 2.1 0 1 1 4.2 0v.06A1.8 1.8 0 0 0 14 3.02a1.8 1.8 0 0 0 1.98-.36l.04-.04a2.1 2.1 0 1 1 2.97 2.97l-.04.04A1.8 1.8 0 0 0 18.98 7.6a1.8 1.8 0 0 0 1.66 1.1h.06a2.1 2.1 0 1 1 0 4.2h-.06A1.8 1.8 0 0 0 19.4 15Z"/>',
                    ];

                    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">'.$icons[$name].'</svg>';
                };
            @endphp

            <div class="app-shell max-w-full overflow-x-hidden" data-spa-shell x-data="{ sidebarOpen: false }" x-on:keydown.escape.window="sidebarOpen = false">
                @php
                    $toastMessage = session('error') ?: session('status');
                    $toastType = session('error') ? 'error' : 'status';
                @endphp

                @if ($toastMessage)
                    <div
                        x-data="{ visible: true }"
                        x-init="setTimeout(() => visible = false, 3000)"
                        x-show="visible"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="translate-y-2 opacity-0"
                        x-transition:enter-end="translate-y-0 opacity-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="translate-y-0 opacity-100"
                        x-transition:leave-end="translate-y-2 opacity-0"
                        class="fixed right-4 top-4 z-[70] max-w-[calc(100vw-2rem)] rounded-xl border border-[#E5E7EB] bg-white px-4 py-3 text-sm font-bold text-[#111827] shadow-xl shadow-slate-900/10 sm:right-6 sm:max-w-sm"
                        role="status"
                        aria-live="polite"
                    >
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $toastType === 'error' ? 'bg-[#EC4899]' : 'bg-[#10B981]' }} text-white">
                                @if ($toastType === 'error')
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                        <path d="M12 9v4"></path>
                                        <path d="M12 17h.01"></path>
                                        <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path>
                                    </svg>
                                @else
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                        <path d="M20 6 9 17l-5-5"></path>
                                    </svg>
                                @endif
                            </span>
                            <span>{{ $toastMessage }}</span>
                        </div>
                    </div>
                @endif

                <div class="max-w-full lg:flex">
                    <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/35 backdrop-blur-sm lg:hidden" x-on:click="sidebarOpen = false"></div>

                    <aside
                        class="app-sidebar fixed inset-y-0 left-0 z-50 flex w-[19rem] max-w-[86vw] -translate-x-full flex-col border-r border-[#E5E7EB] transition-transform duration-200 ease-out lg:translate-x-0"
                        x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                    >
                        <div class="px-5 py-5">
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#10B981] text-sm font-black text-white shadow-sm">PI</span>
                                <span>
                                    <span class="block text-base font-bold text-[#111827]">Perpetual Inbox</span>
                                    <span class="block text-xs font-semibold uppercase text-[#6B7280]">Customer operations</span>
                                </span>
                            </a>
                        </div>
                        <div class="mx-4 rounded-lg border border-[#E5E7EB] bg-[#F5F6F8] p-3">
                            <p class="text-xs font-semibold uppercase text-[#6B7280]">Workspace</p>
                            <p class="mt-1 truncate text-sm font-bold text-[#111827]">{{ $currentBusiness->name }}</p>
                            <p class="mt-1 truncate text-xs text-[#6B7280]">{{ $currentBusiness->category ?? 'Customer operations' }}</p>
                        </div>
                        <nav class="mt-5 min-h-0 flex-1 space-y-1 overflow-y-auto px-4 pb-4">
                            <p class="px-3 pb-2 text-[0.68rem] font-bold uppercase tracking-[0.16em] text-[#9CA3AF]">Workspace</p>
                            @foreach ($navItems as $item)
                                <a href="{{ route($item['route']) }}" x-on:click="sidebarOpen = false" class="app-nav-link whitespace-nowrap {{ request()->routeIs($item['route']) ? 'app-nav-link-active' : '' }}">
                                    <span class="nav-icon">{!! $navIcon($item['icon']) !!}</span>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </nav>
                        <div class="mt-auto border-t border-[#E5E7EB] p-4">
                            <a href="{{ $currentWorkspaceRole === 'owner' ? route('dashboard.settings') : route('dashboard.inbox') }}" class="mb-3 flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-[#F5F6F8]">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#ECFDF5] text-sm font-bold text-[#047857]">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-bold text-[#111827]">{{ auth()->user()->name }}</span>
                                    <span class="block truncate text-xs text-[#6B7280]">{{ auth()->user()->email }}</span>
                                    <span class="mt-1 block text-[0.65rem] font-bold uppercase tracking-wide text-[#2563EB]">{{ $currentWorkspaceRole }}</span>
                                </span>
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 text-[#9CA3AF]"><path d="m9 18 6-6-6-6"/></svg>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" data-spa="false">
                                @csrf
                                <button class="flex w-full items-center justify-center gap-2 rounded-lg border border-[#E5E7EB] bg-white px-4 py-3 text-sm font-bold text-[#111827] shadow-sm transition hover:bg-[#F5F6F8]" aria-label="Logout">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                        <path d="M16 17l5-5-5-5"></path>
                                        <path d="M21 12H9"></path>
                                    </svg>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </aside>

                    <div class="{{ $isInboxPage ? 'app-inbox-frame overflow-hidden' : 'min-h-screen' }} w-full max-w-full lg:pl-72" data-spa-frame>
                        <button type="button" class="{{ $isInboxPage ? 'hidden' : 'mobile-menu-button' }} fixed left-4 top-4 z-30 lg:hidden" x-on:click="sidebarOpen = true" aria-label="Open navigation">
                            <span class="mobile-menu-mark" aria-hidden="true"></span>
                        </button>

                        @unless ($isInboxPage)
                            <header class="app-topbar sticky top-0 z-20 border-b border-[#E5E7EB] bg-white/95 backdrop-blur">
                                <div class="mx-auto flex h-[4.5rem] max-w-7xl items-center gap-3 px-4 pl-20 lg:px-8">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-[#9CA3AF]">{{ $currentBusiness->name }}</p>
                                        <h1 class="truncate text-lg font-bold text-[#111827]">{{ $pageTitle }}</h1>
                                    </div>
                                    @if (in_array($currentWorkspaceRole, ['owner', 'admin'], true))
                                    <a href="{{ route('dashboard.ai-settings') }}" class="hidden items-center gap-2 rounded-full border border-[#D1FAE5] bg-[#ECFDF5] px-3 py-2 text-xs font-bold text-[#047857] sm:inline-flex">
                                        <span class="h-2 w-2 rounded-full bg-[#10B981]"></span>
                                        AI agent
                                    </a>
                                    @endif
                                    <a href="{{ route('dashboard.inbox') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#111827] px-3.5 text-sm font-bold text-white transition hover:bg-black">
                                        {!! $navIcon('inbox') !!}
                                        <span class="hidden sm:inline">Open inbox</span>
                                    </a>
                                </div>
                            </header>
                        @endunless

                        <main class="{{ $isInboxPage ? 'app-inbox-main max-w-full overflow-hidden p-0' : 'mx-auto max-w-7xl px-4 py-6 lg:px-8 lg:py-8' }}" data-spa-main>
                            {{ $slot }}
                        </main>
                    </div>
                </div>
            </div>
        @else
            <div class="min-h-screen bg-gray-100">
                @include('layouts.navigation')

                @isset($header)
                    <header class="bg-white shadow">
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main>{{ $slot }}</main>
            </div>
        @endif
    </body>
</html>
