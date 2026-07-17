<x-app-layout>
    <div class="space-y-6">
        <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-[#2563EB]">People & access</p><h2 class="mt-2 text-2xl font-black">Workspace team</h2><p class="mt-1 text-sm text-[#6B7280]">See who can access this business and the role currently assigned to them.</p></div>
        <section class="overflow-hidden rounded-2xl border border-[#E5E7EB] bg-white shadow-sm">
            <div class="border-b border-[#E5E7EB] px-5 py-4"><h3 class="font-bold">Members</h3></div>
            <div class="divide-y divide-[#E5E7EB]">
                @foreach ($members as $member)
                    <div class="flex items-center gap-4 px-5 py-4"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#EFF6FF] font-bold text-[#2563EB]">{{ strtoupper(substr($member->name, 0, 1)) }}</span><div class="min-w-0 flex-1"><p class="truncate font-bold">{{ $member->name }}</p><p class="truncate text-sm text-[#6B7280]">{{ $member->email }}</p></div><span class="rounded-full bg-[#F3F4F6] px-3 py-1 text-xs font-bold capitalize">{{ $member->pivot->role }}</span></div>
                @endforeach
            </div>
        </section>
        <section class="rounded-2xl border border-[#E5E7EB] bg-white p-5 shadow-sm"><div class="flex items-start justify-between gap-4"><div><h3 class="font-bold">Pending invitations</h3><p class="mt-1 text-sm text-[#6B7280]">Invitation controls will be enabled during the Owner/Admin/Agent permission phase.</p></div><span class="rounded-full bg-[#FFF7ED] px-3 py-1 text-xs font-bold text-[#C2410C]">Coming next</span></div><div class="mt-5 space-y-2">@forelse ($invites as $invite)<div class="flex justify-between rounded-xl bg-[#F9FAFB] p-3 text-sm"><span>{{ $invite->email }}</span><span class="capitalize text-[#6B7280]">{{ $invite->role }} · {{ $invite->status }}</span></div>@empty<div class="rounded-xl border border-dashed border-[#D1D5DB] py-8 text-center text-sm text-[#6B7280]">No pending invitations.</div>@endforelse</div></section>
    </div>
</x-app-layout>
