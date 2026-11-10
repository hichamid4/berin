<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('services')
            ->withCount(['services as requests_count' => fn (Builder $query) => $query
                ->join('service_requests', 'service_requests.service_id', '=', 'services.id')])
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }
}
