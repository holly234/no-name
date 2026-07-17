<x-guest-layout>
    <div class="text-center">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#2563EB] text-sm font-black text-white">PI</span>
        <p class="mt-5 text-xs font-bold uppercase tracking-[0.16em] text-[#2563EB]">Perpetual Inbox</p>
        <h1 class="mt-2 text-2xl font-bold text-[#111827]">Welcome back</h1>
        <p class="mt-2 text-sm leading-6 text-[#6B7280]">Use your Google account to access your workspace securely.</p>
    </div>

    @if ($errors->has('google'))
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ $errors->first('google') }}
        </div>
    @endif

    <a
        href="{{ route('auth.google.redirect') }}"
        class="mt-7 flex w-full items-center justify-center gap-3 rounded-xl border border-[#D1D5DB] bg-white px-4 py-3.5 text-sm font-bold text-[#111827] shadow-sm transition hover:border-[#9CA3AF] hover:bg-[#F9FAFB]"
    >
        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5">
            <path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.91h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.33 2.98-7.4Z"/>
            <path fill="#34A853" d="M12 22c2.7 0 4.97-.9 6.63-2.43l-3.24-2.53c-.9.6-2.05.96-3.39.96-2.6 0-4.81-1.76-5.6-4.13H3.06v2.61A10 10 0 0 0 12 22Z"/>
            <path fill="#FBBC05" d="M6.4 13.87A6.02 6.02 0 0 1 6.08 12c0-.65.11-1.28.32-1.87V7.52H3.06A10 10 0 0 0 2 12c0 1.61.39 3.14 1.06 4.48l3.34-2.61Z"/>
            <path fill="#EA4335" d="M12 6c1.47 0 2.79.5 3.83 1.49l2.87-2.87A9.62 9.62 0 0 0 12 2a10 10 0 0 0-8.94 5.52l3.34 2.61C7.19 7.76 9.4 6 12 6Z"/>
        </svg>
        Continue with Google
    </a>

    <p class="mt-6 text-center text-xs leading-5 text-[#6B7280]">
        By continuing, you agree to our
        <a href="{{ route('legal.terms') }}" class="font-semibold text-[#2563EB]">Terms</a>
        and
        <a href="{{ route('legal.privacy') }}" class="font-semibold text-[#2563EB]">Privacy Policy</a>.
    </p>
</x-guest-layout>
