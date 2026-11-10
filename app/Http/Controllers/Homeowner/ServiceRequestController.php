<?php

namespace App\Http\Controllers\Homeowner;

use App\Actions\Marketplace\MarketplaceWorkflow;
use App\Actions\Marketplace\PhotoStorage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Homeowner\StoreServiceRequestRequest;
use App\Http\Requests\Homeowner\UpdateServiceRequestRequest;
use App\Models\ActivityEvent;
use App\Models\Category;
use App\Models\RequestPhoto;
use App\Models\ServiceRequest;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ServiceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = RequestStatus::tryFrom($request->string('status')->toString());

        $requests = ServiceRequest::query()
            ->where('homeowner_id', $request->user()->id)
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->with(['service.category', 'acceptedOffer.handyman.user'])
            ->withCount(['offers', 'offers as pending_offers_count' => fn (Builder $query) => $query->where('status', OfferStatus::Pending)])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('homeowner.requests.index', ['user' => $request->user(), 'requests' => $requests, 'status' => $status]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', ServiceRequest::class);
        $user = $request->user()->load('homeownerProfile');

        return view('homeowner.requests.form', [
            'user' => $user,
            'serviceRequest' => new ServiceRequest([
                'service_id' => $request->integer('service') ?: null,
                'service_city' => $user->homeownerProfile?->city,
            ]),
            'categories' => $this->availableCatalog(),
        ]);
    }

    public function store(StoreServiceRequestRequest $request, PhotoStorage $photos): RedirectResponse
    {
        $user = $request->user();

        $serviceRequest = DB::transaction(function () use ($request, $user): ServiceRequest {
            $serviceRequest = ServiceRequest::query()->create([
                ...$request->safe()->except('photos'),
                'homeowner_id' => $user->id,
                'status' => RequestStatus::Open,
            ]);
            ActivityEvent::record($user, 'request.created', 'service_request', $serviceRequest->id, ['title' => $serviceRequest->title]);

            return $serviceRequest;
        });

        $photos->attachToRequest($serviceRequest, $request->file('photos', []));

        return to_route('homeowner.requests.show', $serviceRequest)
            ->with('success', 'Your request was published. Handymen with matching skills can now send you offers.');
    }

    public function show(Request $request, ServiceRequest $serviceRequest): View
    {
        Gate::authorize('view', $serviceRequest);
        abort_unless($serviceRequest->isOwnedBy($request->user()), 403);

        $serviceRequest->load([
            'service.category',
            'photos',
            'acceptedOffer.handyman.user',
            'acceptedOffer.review',
            'offers' => fn ($query) => $query->with(['handyman.user', 'handyman.skills', 'photos'])
                ->withCount('photos')
                ->orderByRaw("FIELD(status, 'accepted', 'pending', 'superseded', 'declined', 'withdrawn')")
                ->orderBy('price'),
        ]);

        $serviceRequest->offers->each->setRelation('request', $serviceRequest);

        $ratings = DB::table('reviews')
            ->join('offers', 'offers.id', '=', 'reviews.offer_id')
            ->whereIn('offers.handyman_id', $serviceRequest->offers->pluck('handyman_id'))
            ->groupBy('offers.handyman_id')
            ->selectRaw('offers.handyman_id, avg(reviews.rating) as average, count(*) as total')
            ->get()
            ->keyBy('handyman_id');

        return view('homeowner.requests.show', ['user' => $request->user(), 'serviceRequest' => $serviceRequest, 'ratings' => $ratings]);
    }

    public function edit(Request $request, ServiceRequest $serviceRequest): View
    {
        Gate::authorize('update', $serviceRequest);

        return view('homeowner.requests.form', [
            'user' => $request->user(),
            'serviceRequest' => $serviceRequest->load(['service.category', 'photos']),
            'categories' => $this->availableCatalog(),
        ]);
    }

    public function update(UpdateServiceRequestRequest $request, ServiceRequest $serviceRequest, PhotoStorage $photos): RedirectResponse
    {
        DB::transaction(function () use ($request, $serviceRequest): void {
            $locked = ServiceRequest::query()->lockForUpdate()->findOrFail($serviceRequest->id);
            abort_unless($locked->isOpen(), 409, 'Only open requests can be edited.');

            $locked->update($request->safe()->except('photos'));
            ActivityEvent::record($request->user(), 'request.updated', 'service_request', $locked->id);
        });

        $added = $photos->attachToRequest($serviceRequest, $request->file('photos', []));

        return to_route('homeowner.requests.show', $serviceRequest)
            ->with('success', 'Your request was updated'.($added ? " and {$added} ".str('photo')->plural($added).' added' : '').'.');
    }

    public function cancel(Request $request, ServiceRequest $serviceRequest, MarketplaceWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('cancel', $serviceRequest);
        $workflow->cancelRequest($serviceRequest, $request->user());

        return to_route('homeowner.requests.show', $serviceRequest)
            ->with('success', 'The request was cancelled. Pending offers were declined and the request is kept in your history.');
    }

    public function confirmCompletion(Request $request, ServiceRequest $serviceRequest, MarketplaceWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('confirmCompletion', $serviceRequest);
        $workflow->confirmCompletion($serviceRequest, $request->user());

        return to_route('homeowner.requests.show', $serviceRequest)
            ->with('success', 'Thank you for confirming. You can now rate the handyman.');
    }

    public function destroyPhoto(ServiceRequest $serviceRequest, RequestPhoto $photo, PhotoStorage $photos): RedirectResponse
    {
        Gate::authorize('update', $serviceRequest);
        abort_unless($photo->request_id === $serviceRequest->id, 404);

        $photos->delete($photo);

        return back()->with('success', 'The photo was removed.');
    }

    /**
     * @return Collection<int, Category>
     */
    private function availableCatalog()
    {
        return Category::query()
            ->active()
            ->with(['services' => fn ($query) => $query->active()->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->filter(fn (Category $category): bool => $category->services->isNotEmpty())
            ->values();
    }
}
