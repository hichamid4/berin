<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ActivityEventController extends Controller
{
    public function index(Request $request): View
    {
        $action = $request->string('action')->toString();

        $events = ActivityEvent::query()
            ->with('actor')
            ->when($action !== '', fn (Builder $query) => $query->where('action', $action))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $actions = ActivityEvent::query()->distinct()->orderBy('action')->pluck('action');

        return view('admin.activity.index', compact('events', 'actions', 'action'));
    }
}
