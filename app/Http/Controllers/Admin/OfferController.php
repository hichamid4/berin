<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\OfferStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index(Request $request): View
    {
        $status = OfferStatus::tryFrom($request->string('status')->toString());

        $offers = Offer::query()
            ->with(['request.service', 'request.homeowner.user', 'handyman.user'])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statusCounts = Offer::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.offers.index', compact('offers', 'status', 'statusCounts'));
    }
}
