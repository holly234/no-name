<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\Business;
use App\Services\CurrentBusinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function create()
    {
        return view('onboarding.workspace');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
        ]);

        $business = Business::create([
            'owner_id' => $request->user()->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(5)),
            'category' => $validated['category'] ?? null,
            'email' => $request->user()->email,
            'webhook_secret' => 'whsec_'.Str::random(48),
        ]);

        $business->users()->attach($request->user()->id, ['role' => 'Owner']);
        AiSetting::create(['business_id' => $business->id]);
        session(['current_business_id' => $business->id]);

        return redirect()->route('dashboard');
    }

    public function switch(Request $request, CurrentBusinessService $currentBusinessService)
    {
        $validated = $request->validate([
            'business_id' => ['required', 'integer'],
        ]);

        abort_unless($currentBusinessService->switchForUser($request->user(), (int) $validated['business_id']), 403);

        return back();
    }
}
