<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\RequestStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = RequestStatus::tryFrom($request->string('status')->toString());

        $requests = ServiceRequest::query()
            ->with(['homeowner.user', 'service.category', 'acceptedOffer.handyman.user'])
            ->withCount('offers')
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statusCounts = ServiceRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.requests.index', compact('requests', 'status', 'statusCounts'));
    }
}
