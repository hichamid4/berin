<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Contracts\View\View;

class ReviewController extends Controller
{
    public function index(): View
    {
        $reviews = Review::query()
            ->with(['offer.handyman.user', 'offer.request.service', 'offer.request.homeowner.user'])
            ->latest()
            ->paginate(15);

        $ratingBreakdown = Review::query()->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating');
        $averageRating = Review::query()->avg('rating');

        return view('admin.reviews.index', compact('reviews', 'ratingBreakdown', 'averageRating'));
    }
}
