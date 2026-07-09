<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard.settings', [
            'business' => $request->attributes->get('currentBusiness'),
        ]);
    }

    public function updateBusiness(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'url', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $business->update($validated);

        return back()->with('status', 'Workspace profile updated.');
    }
}
