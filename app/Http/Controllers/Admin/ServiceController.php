<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\CatalogDependencies;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CatalogServiceRequest;
use App\Models\ActivityEvent;
use App\Models\Category;
use App\Models\Service;
use App\Models\Skill;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $categoryId = $request->integer('category') ?: null;
        $search = trim($request->string('q')->toString());

        $services = Service::query()
            ->with(['category', 'skills'])
            ->withCount('requests')
            ->when($categoryId, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.services.index', compact('services', 'categories', 'categoryId', 'search'));
    }

    public function create(Request $request): View
    {
        return $this->form(new Service([
            'is_active' => true,
            'category_id' => $request->integer('category') ?: null,
        ]));
    }

    public function store(CatalogServiceRequest $request): RedirectResponse
    {
        $service = DB::transaction(function () use ($request): Service {
            $service = Service::query()->create([
                ...$request->safe()->only(['category_id', 'name', 'description']),
                'is_active' => $request->boolean('is_active'),
            ]);
            $service->skills()->sync($request->validated('skill_ids'));

            return $service;
        });

        ActivityEvent::record($request->user(), 'service.created', 'service', $service->id, [
            'name' => $service->name,
            'skills' => count($request->validated('skill_ids')),
        ]);

        return to_route('admin.services.index')->with('success', "Service \"{$service->name}\" was created.");
    }

    public function edit(Service $service): View
    {
        return $this->form($service->load('skills'));
    }

    public function update(CatalogServiceRequest $request, Service $service): RedirectResponse
    {
        $changes = DB::transaction(function () use ($request, $service): array {
            $service->update([
                ...$request->safe()->only(['category_id', 'name', 'description']),
                'is_active' => $request->boolean('is_active'),
            ]);

            return $service->skills()->sync($request->validated('skill_ids'));
        });

        ActivityEvent::record($request->user(), 'service.updated', 'service', $service->id, [
            'name' => $service->name,
            'skills_added' => count($changes['attached']),
            'skills_removed' => count($changes['detached']),
        ]);

        return to_route('admin.services.index')->with('success', "Service \"{$service->name}\" was updated.");
    }

    public function confirmDelete(Request $request, Service $service, CatalogDependencies $dependencies): View|string
    {
        return view('admin.catalog.confirm-delete', [
            'report' => $dependencies->inspectService($service),
            'destroyUrl' => route('admin.services.destroy', $service),
            'toggleUrl' => route('admin.services.toggle', $service),
            'backUrl' => route('admin.services.index'),
        ])->fragmentIf($request->ajax(), 'report');
    }

    public function destroy(Request $request, Service $service, CatalogDependencies $dependencies): RedirectResponse
    {
        $name = $service->name;
        $dependencies->deleteService($service, $request->user());

        return to_route('admin.services.index')->with('success', "Service \"{$name}\" was deleted. Its skills were kept.");
    }

    public function toggle(Request $request, Service $service): RedirectResponse
    {
        $service->update(['is_active' => ! $service->is_active]);
        $action = $service->is_active ? 'activated' : 'deactivated';

        ActivityEvent::record($request->user(), "service.{$action}", 'service', $service->id, ['name' => $service->name]);

        return back()->with('success', "Service \"{$service->name}\" was {$action}."
            .($service->is_active ? '' : ' It no longer accepts new requests; existing requests and offers are unchanged.'));
    }

    private function form(Service $service): View
    {
        return view('admin.services.form', [
            'service' => $service,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name', 'is_active']),
            'skills' => Skill::query()->withCount('handymen')->orderBy('name')->get(),
            'selectedSkills' => old('skill_ids', $service->exists ? $service->skills->pluck('id')->all() : []),
        ]);
    }
}
