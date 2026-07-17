<x-app-layout>
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#2563EB]">People & access</p>
            <h2 class="mt-2 text-2xl font-black">Workspace team</h2>
            <p class="mt-1 text-sm text-[#6B7280]">Owners control admins. Admins can invite and manage agents.</p>
        </div>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.65fr)]">
            <article class="overflow-hidden rounded-2xl border border-[#E5E7EB] bg-white shadow-sm">
                <div class="border-b border-[#E5E7EB] px-5 py-4"><h3 class="font-bold">Members</h3></div>
                <div class="divide-y divide-[#E5E7EB]">
                    @foreach ($members as $member)
                        @php
                            $memberRole = strtolower($member->pivot->role);
                            $isOwner = (int) $member->id === (int) $currentBusiness->owner_id;
                            $canManageMember = ! $isOwner && ($currentWorkspaceRole === 'owner' || ($currentWorkspaceRole === 'admin' && $memberRole === 'agent'));
                        @endphp
                        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                            <div class="flex min-w-0 flex-1 items-center gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#EFF6FF] font-bold text-[#2563EB]">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                                <div class="min-w-0"><p class="truncate font-bold">{{ $member->name }}</p><p class="truncate text-sm text-[#6B7280]">{{ $member->email }}</p></div>
                            </div>
                            @if ($canManageMember)
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('dashboard.team.members.role', $member) }}" class="flex items-center gap-2">
                                        @csrf @method('PATCH')
                                        <select name="role" class="rounded-lg border-[#D1D5DB] py-2 text-sm" onchange="this.form.submit()">
                                            <option value="agent" @selected($memberRole === 'agent')>Agent</option>
                                            @if ($canManageAdmins)<option value="admin" @selected($memberRole === 'admin')>Admin</option>@endif
                                        </select>
                                    </form>
                                    <form method="POST" action="{{ route('dashboard.team.members.remove', $member) }}" onsubmit="return confirm('Remove this member from the workspace?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50">Remove</button>
                                    </form>
                                </div>
                            @else
                                <span class="rounded-full bg-[#F3F4F6] px-3 py-1 text-xs font-bold capitalize">{{ $isOwner ? 'Owner' : $memberRole }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-2xl border border-[#E5E7EB] bg-white p-5 shadow-sm">
                <h3 class="font-bold">Invite teammate</h3>
                <p class="mt-1 text-sm text-[#6B7280]">They must accept using the same Google email address.</p>
                <form method="POST" action="{{ route('dashboard.team.invite') }}" class="mt-5 space-y-4">
                    @csrf
                    <label class="block"><span class="mb-1.5 block text-xs font-bold uppercase text-[#6B7280]">Google email</span><input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border-[#D1D5DB] text-sm" placeholder="person@gmail.com">@error('email')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="mb-1.5 block text-xs font-bold uppercase text-[#6B7280]">Role</span><select name="role" class="w-full rounded-xl border-[#D1D5DB] text-sm"><option value="agent">Agent — inbox only</option>@if ($canManageAdmins)<option value="admin">Admin — operations</option>@endif</select></label>
                    <button class="w-full rounded-xl bg-[#2563EB] px-4 py-3 text-sm font-bold text-white hover:bg-[#1D4ED8]">Create invitation</button>
                </form>
            </article>
        </section>

        <section class="rounded-2xl border border-[#E5E7EB] bg-white p-5 shadow-sm">
            <h3 class="font-bold">Invitations</h3>
            <div class="mt-4 space-y-3">
                @forelse ($invites as $invite)
                    <div class="flex flex-col gap-3 rounded-xl bg-[#F9FAFB] p-4 lg:flex-row lg:items-center">
                        <div class="min-w-0 flex-1"><p class="truncate font-bold">{{ $invite->email }}</p><p class="text-xs capitalize text-[#6B7280]">{{ $invite->role }} · {{ $invite->status }}</p></div>
                        @if ($invite->status === 'pending')
                            <input readonly value="{{ route('team.invitations.accept', $invite->token) }}" class="min-w-0 flex-1 rounded-lg border-[#D1D5DB] bg-white text-xs" onclick="this.select()">
                            @if ($currentWorkspaceRole === 'owner' || strtolower($invite->role) === 'agent')
                                <form method="POST" action="{{ route('dashboard.team.invitations.cancel', $invite) }}">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600">Cancel</button></form>
                            @endif
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-[#D1D5DB] py-8 text-center text-sm text-[#6B7280]">No invitations yet.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
