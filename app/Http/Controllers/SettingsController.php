<?php

namespace App\Http\Controllers;

use App\Services\WorkspaceDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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

    public function destroyWorkspace(Request $request, WorkspaceDeletionService $deletionService): RedirectResponse
    {
        $business = $request->attributes->get('currentBusiness');
        $user = $request->user();

        abort_unless((int) $business->owner_id === (int) $user->id, 403);

        $validated = $request->validateWithBag('workspaceDeletion', [
            'workspace_name' => ['required', 'string', 'max:120'],
            'confirmation' => ['required', 'string'],
        ]);

        if (! hash_equals($business->name, trim($validated['workspace_name'])) || $validated['confirmation'] !== 'DELETE') {
            throw ValidationException::withMessages([
                'workspace_name' => 'Enter the exact workspace name and type DELETE to confirm permanent deletion.',
            ])->errorBag('workspaceDeletion');
        }

        $result = $deletionService->delete($business, $user);
        $request->session()->forget('current_business_id');

        if ($result['user_deleted']) {
            Auth::logout();
            $user->delete();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('landing')->with('status', 'Your account and workspace data were permanently deleted.');
        }

        if ($result['next_business_id']) {
            $request->session()->put('current_business_id', $result['next_business_id']);
        }

        return redirect()->route('dashboard')->with('status', 'Workspace data permanently deleted.');
    }
}
