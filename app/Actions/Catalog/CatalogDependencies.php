<?php

namespace App\Actions\Catalog;

use App\Exceptions\BusinessRuleViolation;
use App\Models\ActivityEvent;
use App\Models\Category;
use App\Models\HandymanProfile;
use App\Models\Offer;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dependency-aware inspection and deletion for categories, services and skills.
 *
 * Every deletion re-runs its inspection inside a transaction while holding row locks on
 * the records whose relationships matter, so a concurrent assignment cannot slip through.
 * Historical marketplace data is never cascaded: protected records must be deactivated.
 */
class CatalogDependencies
{
    public function inspectSkill(Skill $skill): DependencyReport
    {
        $services = $skill->services()->orderBy('name')->pluck('name');
        $handymen = $this->handymanNames(fn (Builder $query) => $query->where('skills.id', $skill->id));

        $blockers = [];

        if ($services->isNotEmpty()) {
            $blockers[] = DependencyReport::entry(
                'Required by '.$services->count().' '.str('service')->plural($services->count()),
                'Remove this skill from these services first, or deactivate the skill.',
                $services,
            );
        }

        if ($handymen->isNotEmpty()) {
            $blockers[] = DependencyReport::entry(
                'Held by '.$handymen->count().' '.str('handyman')->plural($handymen->count()),
                'These handymen declared the skill; deleting it would change which requests they can see.',
                $handymen,
            );
        }

        return new DependencyReport('skill', $skill->name, $skill->is_active, $blockers);
    }

    public function inspectService(Service $service): DependencyReport
    {
        $skills = $service->skills()->orderBy('name')->get(['skills.id', 'skills.name']);

        return new DependencyReport(
            'service',
            $service->name,
            $service->is_active,
            $this->serviceBlockers(collect([$service->id]), $skills->pluck('id')),
            $skills->isEmpty() ? [] : [DependencyReport::entry(
                'Unlinks '.$skills->count().' required '.str('skill')->plural($skills->count()),
                'Only the service-to-skill links are removed. The skills themselves are kept.',
                $skills->pluck('name'),
            )],
        );
    }

    public function inspectCategory(Category $category): DependencyReport
    {
        $services = $category->services()->orderBy('name')->get(['id', 'name']);
        $skillIds = DB::table('service_skill')->whereIn('service_id', $services->pluck('id'))->distinct()->pluck('skill_id');

        $effects = [];

        if ($services->isNotEmpty()) {
            $effects[] = DependencyReport::entry(
                'Deletes '.$services->count().' '.str('service')->plural($services->count()),
                'These services belong to the category and would be deleted with it. Their skills are kept.',
                $services->pluck('name'),
            );
        }

        return new DependencyReport(
            'category',
            $category->name,
            $category->is_active,
            $this->serviceBlockers($services->pluck('id'), $skillIds),
            $effects,
        );
    }

    public function deleteSkill(Skill $skill, User $administrator): void
    {
        $this->runGuarded(function () use ($skill, $administrator): void {
            $locked = Skill::query()->lockForUpdate()->findOrFail($skill->id);
            $report = $this->inspectSkill($locked);

            if (! $report->canDelete()) {
                throw new BusinessRuleViolation($report->refusalMessage());
            }

            $locked->delete();
            ActivityEvent::record($administrator, 'skill.deleted', 'skill', $locked->id, ['name' => $locked->name]);
        });
    }

    public function deleteService(Service $service, User $administrator): void
    {
        $this->runGuarded(function () use ($service, $administrator): void {
            $locked = Service::query()->lockForUpdate()->findOrFail($service->id);
            Skill::query()->whereIn('id', $locked->skills()->select('skills.id'))->lockForUpdate()->get();

            $report = $this->inspectService($locked);

            if (! $report->canDelete()) {
                throw new BusinessRuleViolation($report->refusalMessage());
            }

            $locked->skills()->detach();
            $locked->delete();
            ActivityEvent::record($administrator, 'service.deleted', 'service', $locked->id, ['name' => $locked->name]);
        });
    }

    public function deleteCategory(Category $category, User $administrator): void
    {
        $this->runGuarded(function () use ($category, $administrator): void {
            $locked = Category::query()->lockForUpdate()->findOrFail($category->id);
            $services = Service::query()->where('category_id', $locked->id)->lockForUpdate()->get();
            Skill::query()
                ->whereIn('id', DB::table('service_skill')->whereIn('service_id', $services->pluck('id'))->select('skill_id'))
                ->lockForUpdate()
                ->get();

            $report = $this->inspectCategory($locked);

            if (! $report->canDelete()) {
                throw new BusinessRuleViolation($report->refusalMessage());
            }

            foreach ($services as $service) {
                $service->skills()->detach();
                $service->delete();
            }

            $locked->delete();
            ActivityEvent::record($administrator, 'category.deleted', 'category', $locked->id, [
                'name' => $locked->name,
                'services_deleted' => $services->count(),
            ]);
        });
    }

    /**
     * Blockers shared by service and category deletion.
     *
     * @param  Collection<int, int>  $serviceIds
     * @param  Collection<int, int>  $skillIds
     * @return list<array{title: string, detail: string, items: list<string>, total: int}>
     */
    private function serviceBlockers(Collection $serviceIds, Collection $skillIds): array
    {
        $blockers = [];

        $handymen = $skillIds->isEmpty()
            ? collect()
            : $this->handymanNames(fn (Builder $query) => $query->whereIn('skills.id', $skillIds));

        if ($handymen->isNotEmpty()) {
            $blockers[] = DependencyReport::entry(
                'Linked to skills held by '.$handymen->count().' '.str('handyman')->plural($handymen->count()),
                'Handymen use these skills to receive matching requests. Remove the skills from the service first, or deactivate it.',
                $handymen,
            );
        }

        $requestCounts = ServiceRequest::query()
            ->whereIn('service_id', $serviceIds)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        if ($requestCounts->sum() > 0) {
            $offerCount = Offer::query()->whereIn('request_id', ServiceRequest::query()->whereIn('service_id', $serviceIds)->select('id'))->count();
            $reviewCount = Review::query()->whereIn('offer_id', Offer::query()->whereIn('request_id', ServiceRequest::query()->whereIn('service_id', $serviceIds)->select('id'))->select('id'))->count();

            $blockers[] = DependencyReport::entry(
                'Referenced by '.$requestCounts->sum().' service '.str('request')->plural($requestCounts->sum()),
                "Historical marketplace records are retained: {$offerCount} ".str('offer')->plural($offerCount)." and {$reviewCount} ".str('review')->plural($reviewCount).' depend on these requests.',
                $requestCounts->map(fn (int $total, string $status): string => $total.' '.str_replace('_', ' ', $status)),
                (int) $requestCounts->sum(),
            );
        }

        return $blockers;
    }

    /**
     * @param  callable(Builder): Builder  $skillConstraint
     * @return Collection<int, string>
     */
    private function handymanNames(callable $skillConstraint): Collection
    {
        return HandymanProfile::query()
            ->whereHas('skills', $skillConstraint)
            ->with('user:id,first_name,last_name')
            ->get()
            ->map(fn (HandymanProfile $profile): string => $profile->user->fullName())
            ->sort()
            ->values();
    }

    /**
     * Run a deletion transactionally and translate foreign-key refusals into an explanation.
     */
    private function runGuarded(callable $operation): void
    {
        try {
            DB::transaction($operation);
        } catch (QueryException $exception) {
            if (in_array($exception->errorInfo[1] ?? null, [1451, 1452], true)) {
                throw new BusinessRuleViolation('The record is still referenced by other data and was not deleted. Refresh the page to see its current dependencies.');
            }

            throw $exception;
        }
    }
}
