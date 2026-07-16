@php
    $content = [
        'privacy' => [
            'eyebrow' => 'Privacy', 'title' => 'Privacy policy',
            'intro' => 'Perpetual Inbox helps teams manage customer conversations from connected channels. This policy explains what we handle and why.',
            'sections' => [
                ['title' => 'Information we handle', 'body' => 'We may handle your name, email address, workspace details, connected account identifiers, customer names and identifiers, conversation content, attachments, delivery metadata, and settings you provide. We receive channel data only after you connect and authorize that channel.'],
                ['title' => 'How we use it', 'body' => 'We use this information to display your inbox, route conversations, send replies you authorize, maintain workspace security, provide support, and improve reliability. We do not sell conversation data.'],
                ['title' => 'Connected services', 'body' => 'Channel providers such as Google and Meta process data under their own terms. Access tokens are encrypted at rest and are used only to provide the connection you requested.'],
                ['title' => 'Retention and deletion', 'body' => 'Conversation records remain in your workspace until you or an authorized administrator deletes them or requests deletion. You can disconnect a channel from Accounts. See the data deletion page for a direct request process.'],
                ['title' => 'Contact', 'body' => 'Questions or privacy requests can be sent to '.config('legal.contact_email').'.'],
            ],
        ],
        'terms' => [
            'eyebrow' => 'Terms', 'title' => 'Terms of service',
            'intro' => 'These baseline terms govern use of Perpetual Inbox while the product is being prepared for wider release.',
            'sections' => [
                ['title' => 'Your workspace', 'body' => 'You are responsible for the workspace information you provide, the people you invite, and the permissions you grant. Keep account credentials and connected provider access secure.'],
                ['title' => 'Connected channels', 'body' => 'You must have permission to connect each Gmail, WhatsApp, Telegram, or other channel account. You must follow the channel provider terms and applicable messaging laws.'],
                ['title' => 'Acceptable use', 'body' => 'Do not use the service for unlawful, abusive, deceptive, unsolicited, or harmful communications. You are responsible for reviewing automated assistance before sending messages where approval is required.'],
                ['title' => 'Availability', 'body' => 'The service may change during development and may depend on third-party APIs, network access, and provider limits. We do not guarantee uninterrupted delivery.'],
                ['title' => 'Contact', 'body' => 'For support or account questions, contact '.config('legal.contact_email').'.'],
            ],
        ],
        'data-deletion' => [
            'eyebrow' => 'Data control', 'title' => 'Data deletion',
            'intro' => 'You can disconnect channels and request removal of workspace data at any time.',
            'sections' => [
                ['title' => 'Disconnect a channel', 'body' => 'Open Dashboard, choose Accounts, and select Disconnect beside the connected account. This stops future synchronization and removes the stored access token from the active connection.'],
                ['title' => 'Request workspace deletion', 'body' => 'Email '.config('legal.contact_email').' from an authorized workspace email with the workspace name and the accounts you want removed. We will verify the request before deleting the requested records.'],
                ['title' => 'Provider data', 'body' => 'Disconnecting Perpetual Inbox does not delete data held by Google, Meta, Telegram, or another provider. Use that provider’s own privacy and deletion controls for provider-side deletion.'],
            ],
        ],
    ];
    $active = $content[$page];
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $active['title'] }} | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#F5F6F8] text-[#111827]">
    <header class="border-b border-[#E5E7EB] bg-white"><div class="mx-auto flex max-w-4xl items-center justify-between px-5 py-5">
        <a href="{{ route('landing') }}" class="text-lg font-bold">Perpetual Inbox</a>
        <a href="{{ route('landing') }}" class="text-sm font-semibold text-[#2563EB]">Back home</a>
    </div></header>
    <main class="mx-auto max-w-3xl px-5 py-14 sm:py-20">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#2563EB]">{{ $active['eyebrow'] }}</p>
        <h1 class="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">{{ $active['title'] }}</h1>
        <p class="mt-5 text-base leading-7 text-[#667085]">{{ $active['intro'] }}</p>
        <div class="mt-12 divide-y divide-[#E5E7EB] rounded-2xl border border-[#E5E7EB] bg-white">
            @foreach ($active['sections'] as $section)
                <section class="p-6 sm:p-8"><h2 class="text-lg font-bold">{{ $section['title'] }}</h2><p class="mt-3 text-sm leading-7 text-[#667085]">{{ $section['body'] }}</p></section>
            @endforeach
        </div>
    </main>
</body>
</html>
