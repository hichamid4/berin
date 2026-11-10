<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $categoryId = $request->integer('category') ?: null;

        $services = Service::query()
            ->with(['category', 'skills'])
            ->withCount('requests')
            ->when($categoryId, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.services.index', compact('services', 'categories', 'categoryId'));
    }
}
