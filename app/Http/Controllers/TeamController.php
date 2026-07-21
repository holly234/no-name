<?php

namespace App\Http\Controllers;

use App\Models\TeamInvite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');

        return view('dashboard.team', [
            'members' => $business->users()->orderBy('name')->paginate(50, ['users.*'], 'members_page')->withQueryString(),
            'invites' => TeamInvite::where('business_id', $business->id)->latest()->paginate(50, ['*'], 'invites_page')->withQueryString(),
            'canManageAdmins' => $request->user()->hasWorkspaceRole($business, 'owner'),
        ]);
    }

    public function invite(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $allowedRoles = $request->user()->hasWorkspaceRole($business, 'owner') ? ['admin', 'agent'] : ['agent'];
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:160'],
            'role' => ['required', Rule::in($allowedRoles)],
        ]);
        $email = Str::lower(trim($validated['email']));

        if ($business->users()->whereRaw('LOWER(users.email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => 'This person is already a workspace member.']);
        }

        $invite = TeamInvite::updateOrCreate(
            ['business_id' => $business->id, 'email' => $email, 'status' => 'pending'],
            ['role' => $validated['role'], 'token' => Str::random(64), 'invited_by' => $request->user()->id, 'expires_at' => now()->addDays(7)]
        );

        return back()->with('status', 'Invitation created. Copy the invitation link and send it to '.$invite->email.'.');
    }

    public function updateRole(Request $request, User $member)
    {
        $business = $request->attributes->get('currentBusiness');
        abort_unless($business->users()->whereKey($member->id)->exists(), 404);
        abort_if((int) $member->id === (int) $business->owner_id, 422, 'The workspace owner cannot be demoted.');

        $actorRole = $request->user()->workspaceRole($business);
        $targetRole = $member->workspaceRole($business);
        abort_if($actorRole === 'admin' && $targetRole !== 'agent', 403);

        $allowedRoles = $actorRole === 'owner' ? ['admin', 'agent'] : ['agent'];
        $validated = $request->validate(['role' => ['required', Rule::in($allowedRoles)]]);
        $business->users()->updateExistingPivot($member->id, ['role' => ucfirst($validated['role'])]);

        return back()->with('status', $member->name.' is now an '.$validated['role'].'.');
    }

    public function remove(Request $request, User $member)
    {
        $business = $request->attributes->get('currentBusiness');
        abort_unless($business->users()->whereKey($member->id)->exists(), 404);
        abort_if((int) $member->id === (int) $business->owner_id, 422, 'The workspace owner cannot be removed.');
        abort_if($request->user()->workspaceRole($business) === 'admin' && $member->workspaceRole($business) !== 'agent', 403);

        $business->users()->detach($member->id);

        return back()->with('status', $member->name.' was removed from the workspace.');
    }

    public function cancelInvite(Request $request, TeamInvite $invite)
    {
        $business = $request->attributes->get('currentBusiness');
        abort_unless((int) $invite->business_id === (int) $business->id, 404);
        abort_if($request->user()->workspaceRole($business) === 'admin' && strtolower($invite->role) !== 'agent', 403);

        $invite->update(['status' => 'cancelled']);

        return back()->with('status', 'Invitation cancelled.');
    }

    public function accept(Request $request, string $token)
    {
        $invite = TeamInvite::where('token', $token)->where('status', 'pending')->firstOrFail();
        abort_if($invite->expires_at?->isPast(), 410, 'This invitation has expired. Ask the workspace owner for a new one.');
        abort_unless(hash_equals(Str::lower($invite->email), Str::lower($request->user()->email)), 403, 'Sign in with the invited Google account.');

        DB::transaction(function () use ($invite, $request) {
            $invite->business->users()->syncWithoutDetaching([
                $request->user()->id => ['role' => ucfirst(strtolower($invite->role))],
            ]);
            $invite->update(['status' => 'accepted']);
        });

        $request->session()->put('current_business_id', $invite->business_id);

        return redirect()->route('dashboard')->with('status', 'You joined '.$invite->business->name.'.');
    }
}
