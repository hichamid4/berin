<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\CatalogDependencies;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\ActivityEvent;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('services')
            ->withCount(['services as active_services_count' => fn (Builder $query) => $query->where('is_active', true)])
            ->withCount(['services as requests_count' => fn (Builder $query) => $query
                ->join('service_requests', 'service_requests.service_id', '=', 'services.id')])
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true])]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::query()->create([
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityEvent::record($request->user(), 'category.created', 'category', $category->id, ['name' => $category->name]);

        return to_route('admin.categories.index')->with('success', "Category \"{$category->name}\" was created.");
    }

    public function edit(Category $category): View
    {
        $category->load(['services' => fn ($query) => $query->orderBy('name')]);

        return view('admin.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update([
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityEvent::record($request->user(), 'category.updated', 'category', $category->id, ['name' => $category->name]);

        return to_route('admin.categories.index')->with('success', "Category \"{$category->name}\" was updated.");
    }

    /**
     * Show the server-side dependency report before deletion (modal fragment or full page).
     */
    public function confirmDelete(Request $request, Category $category, CatalogDependencies $dependencies): View|string
    {
        return view('admin.catalog.confirm-delete', [
            'report' => $dependencies->inspectCategory($category),
            'destroyUrl' => route('admin.categories.destroy', $category),
            'toggleUrl' => route('admin.categories.toggle', $category),
            'backUrl' => route('admin.categories.index'),
        ])->fragmentIf($request->ajax(), 'report');
    }

    public function destroy(Request $request, Category $category, CatalogDependencies $dependencies): RedirectResponse
    {
        $name = $category->name;
        $dependencies->deleteCategory($category, $request->user());

        return to_route('admin.categories.index')->with('success', "Category \"{$name}\" was deleted.");
    }

    public function toggle(Request $request, Category $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);
        $action = $category->is_active ? 'activated' : 'deactivated';

        ActivityEvent::record($request->user(), "category.{$action}", 'category', $category->id, ['name' => $category->name]);

        return back()->with('success', "Category \"{$category->name}\" was {$action}."
            .($category->is_active ? '' : ' Its services are hidden from the public catalog and new requests, while history is kept.'));
    }
}
