<?php

namespace App\Http\Controllers;

use App\Models\TeamInvite;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __invoke(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');

        return view('dashboard.team', [
            'members' => $business->users()->orderBy('name')->get(),
            'invites' => TeamInvite::where('business_id', $business->id)->latest()->get(),
        ]);
    }
}
