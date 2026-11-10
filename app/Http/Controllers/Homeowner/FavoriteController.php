<?php

namespace App\Http\Controllers\Homeowner;

use App\Http\Controllers\Controller;
use App\Models\ActivityEvent;
use App\Models\HandymanProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function store(Request $request, HandymanProfile $handyman): RedirectResponse
    {
        abort_unless($handyman->user?->isActive(), 404);

        $changes = $request->user()->homeownerProfile->favoriteHandymen()->syncWithoutDetaching([$handyman->user_id]);

        if ($changes['attached'] !== []) {
            ActivityEvent::record($request->user(), 'favorite.added', 'handyman', $handyman->user_id);
        }

        return back()->with('success', "{$handyman->user->fullName()} was added to your favorites.");
    }

    public function destroy(Request $request, HandymanProfile $handyman): RedirectResponse
    {
        $removed = $request->user()->homeownerProfile->favoriteHandymen()->detach($handyman->user_id);

        if ($removed > 0) {
            ActivityEvent::record($request->user(), 'favorite.removed', 'handyman', $handyman->user_id);
        }

        return back()->with('success', "{$handyman->user->fullName()} was removed from your favorites.");
    }
}
