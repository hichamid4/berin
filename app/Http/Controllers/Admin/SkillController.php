<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\CatalogDependencies;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SkillRequest;
use App\Models\ActivityEvent;
use App\Models\Category;
use App\Models\Skill;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SkillController extends Controller
{
    public function index(): View
    {
        $skills = Skill::query()
            ->with(['services:id,name'])
            ->withCount(['services', 'handymen'])
            ->orderBy('name')
            ->get();

        return view('admin.skills.index', compact('skills'));
    }

    public function create(): View
    {
        return $this->form(new Skill(['is_active' => true]));
    }

    public function store(SkillRequest $request): RedirectResponse
    {
        $skill = DB::transaction(function () use ($request): Skill {
            $skill = Skill::query()->create([
                ...$request->safe()->only(['name', 'description']),
                'is_active' => $request->boolean('is_active'),
            ]);
            $skill->services()->sync($request->validated('service_ids', []));

            return $skill;
        });

        ActivityEvent::record($request->user(), 'skill.created', 'skill', $skill->id, ['name' => $skill->name]);

        return to_route('admin.skills.index')->with('success', "Skill \"{$skill->name}\" was created.");
    }

    public function edit(Skill $skill): View
    {
        return $this->form($skill->load(['services', 'handymen.user']));
    }

    public function update(SkillRequest $request, Skill $skill): RedirectResponse
    {
        $changes = DB::transaction(function () use ($request, $skill): array {
            $skill->update([
                ...$request->safe()->only(['name', 'description']),
                'is_active' => $request->boolean('is_active'),
            ]);

            return $skill->services()->sync($request->validated('service_ids', []));
        });

        ActivityEvent::record($request->user(), 'skill.updated', 'skill', $skill->id, [
            'name' => $skill->name,
            'services_added' => count($changes['attached']),
            'services_removed' => count($changes['detached']),
        ]);

        return to_route('admin.skills.index')->with('success', "Skill \"{$skill->name}\" was updated.");
    }

    public function confirmDelete(Request $request, Skill $skill, CatalogDependencies $dependencies): View|string
    {
        return view('admin.catalog.confirm-delete', [
            'report' => $dependencies->inspectSkill($skill),
            'destroyUrl' => route('admin.skills.destroy', $skill),
            'toggleUrl' => route('admin.skills.toggle', $skill),
            'backUrl' => route('admin.skills.index'),
        ])->fragmentIf($request->ajax(), 'report');
    }

    public function destroy(Request $request, Skill $skill, CatalogDependencies $dependencies): RedirectResponse
    {
        $name = $skill->name;
        $dependencies->deleteSkill($skill, $request->user());

        return to_route('admin.skills.index')->with('success', "Skill \"{$name}\" was deleted.");
    }

    public function toggle(Request $request, Skill $skill): RedirectResponse
    {
        $skill->update(['is_active' => ! $skill->is_active]);
        $action = $skill->is_active ? 'activated' : 'deactivated';

        ActivityEvent::record($request->user(), "skill.{$action}", 'skill', $skill->id, ['name' => $skill->name]);

        return back()->with('success', "Skill \"{$skill->name}\" was {$action}."
            .($skill->is_active ? '' : ' Handymen can no longer add it; existing holders and service links are kept.'));
    }

    private function form(Skill $skill): View
    {
        return view('admin.skills.form', [
            'skill' => $skill,
            'categories' => Category::query()->with(['services' => fn ($query) => $query->orderBy('name')])->orderBy('name')->get(),
            'selectedServices' => old('service_ids', $skill->exists ? $skill->services->pluck('id')->all() : []),
        ]);
    }
}
