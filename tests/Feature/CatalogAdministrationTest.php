<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HandymanProfile;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Skill;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CatalogAdministrationTest extends TestCase
{
    use DatabaseTransactions;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator = User::factory()->role(UserRole::Administrator)->create();
    }

    public function test_non_administrators_cannot_reach_catalog_management(): void
    {
        $homeowner = User::factory()->create();
        $service = Service::factory()->create();

        $this->actingAs($homeowner)->get(route('admin.services.index'))->assertForbidden();
        $this->actingAs($homeowner)->delete(route('admin.services.destroy', $service))->assertForbidden();
        $this->assertModelExists($service);
    }

    public function test_administrator_creates_a_service_with_required_skills(): void
    {
        $category = Category::factory()->create();
        $skills = Skill::factory()->count(2)->create();

        $this->actingAs($this->administrator)->post(route('admin.services.store'), [
            'category_id' => $category->id,
            'name' => 'Shower screen fitting',
            'description' => 'Installation of glass shower screens with sealed joints.',
            'is_active' => '1',
            'skill_ids' => $skills->pluck('id')->all(),
        ])->assertRedirect(route('admin.services.index'));

        $service = Service::query()->where('name', 'Shower screen fitting')->firstOrFail();
        $this->assertTrue($service->is_active);
        $this->assertEqualsCanonicalizing($skills->pluck('id')->all(), $service->skills()->pluck('skills.id')->all());
    }

    public function test_a_service_requires_at_least_one_skill(): void
    {
        $this->actingAs($this->administrator)->post(route('admin.services.store'), [
            'category_id' => Category::factory()->create()->id,
            'name' => 'Unreachable service',
            'description' => 'A service that no handyman could ever see.',
            'skill_ids' => [],
        ])->assertSessionHasErrors(['skill_ids' => 'Select at least one skill so that qualified handymen can see requests for this service.']);
    }

    public function test_unlinking_a_skill_from_a_service_keeps_the_skill(): void
    {
        [$kept, $removed] = Skill::factory()->count(2)->create();
        $service = Service::factory()->create();
        $service->skills()->attach([$kept->id, $removed->id]);

        $this->actingAs($this->administrator)->put(route('admin.services.update', $service), [
            'category_id' => $service->category_id,
            'name' => $service->name,
            'description' => $service->description,
            'is_active' => '1',
            'skill_ids' => [$kept->id],
        ])->assertRedirect(route('admin.services.index'));

        $this->assertSame([$kept->id], $service->skills()->pluck('skills.id')->all());
        $this->assertModelExists($removed);
    }

    public function test_dependency_report_is_returned_as_a_modal_fragment(): void
    {
        $skill = Skill::factory()->create();
        HandymanProfile::factory()->create()->skills()->attach($skill);

        $this->actingAs($this->administrator)
            ->get(route('admin.skills.delete', $skill), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Held by 1 handyman')
            ->assertSee('Deactivate instead')
            ->assertDontSee('Delete permanently')
            ->assertDontSee('<html', false);
    }

    public function test_service_whose_skills_are_held_by_a_handyman_cannot_be_deleted(): void
    {
        $skill = Skill::factory()->create();
        $service = Service::factory()->create();
        $service->skills()->attach($skill);
        HandymanProfile::factory()->create()->skills()->attach($skill);

        $this->actingAs($this->administrator)
            ->from(route('admin.services.index'))
            ->delete(route('admin.services.destroy', $service))
            ->assertRedirect(route('admin.services.index'))
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'linked to skills held by 1 handyman'));

        $this->assertModelExists($service);
        $this->assertDatabaseHas('service_skill', ['service_id' => $service->id, 'skill_id' => $skill->id]);
    }

    public function test_service_referenced_by_requests_cannot_be_deleted_but_can_be_deactivated(): void
    {
        $service = Service::factory()->create();
        $service->skills()->attach(Skill::factory()->create());
        ServiceRequest::factory()->create(['service_id' => $service->id]);

        $this->actingAs($this->administrator)
            ->delete(route('admin.services.destroy', $service))
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'referenced by 1 service request'));
        $this->assertModelExists($service);

        $this->actingAs($this->administrator)->patch(route('admin.services.toggle', $service))->assertSessionHas('success');
        $this->assertFalse($service->fresh()->is_active);
        $this->assertDatabaseHas('activity_events', ['action' => 'service.deactivated', 'subject_id' => $service->id]);
    }

    public function test_unused_service_is_deleted_without_deleting_its_skills(): void
    {
        $skill = Skill::factory()->create();
        $service = Service::factory()->create();
        $service->skills()->attach($skill);

        $this->actingAs($this->administrator)
            ->delete(route('admin.services.destroy', $service))
            ->assertRedirect(route('admin.services.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($service);
        $this->assertModelExists($skill);
        $this->assertDatabaseMissing('service_skill', ['service_id' => $service->id]);
    }

    public function test_skill_in_use_cannot_be_deleted_and_unused_skill_can(): void
    {
        $usedByService = Skill::factory()->create();
        Service::factory()->create()->skills()->attach($usedByService);
        $unused = Skill::factory()->create();

        $this->actingAs($this->administrator)
            ->delete(route('admin.skills.destroy', $usedByService))
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'required by 1 service'));
        $this->assertModelExists($usedByService);

        $this->actingAs($this->administrator)->delete(route('admin.skills.destroy', $unused))->assertSessionHas('success');
        $this->assertModelMissing($unused);
    }

    public function test_category_with_historical_requests_is_protected(): void
    {
        $category = Category::factory()->create();
        $service = Service::factory()->for($category)->create();
        ServiceRequest::factory()->create(['service_id' => $service->id]);

        $this->actingAs($this->administrator)
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'Deactivate it instead'));

        $this->assertModelExists($category);
        $this->assertModelExists($service);
    }

    public function test_unused_category_is_deleted_with_its_unused_services(): void
    {
        $skill = Skill::factory()->create();
        $category = Category::factory()->create();
        $service = Service::factory()->for($category)->create();
        $service->skills()->attach($skill);

        $this->actingAs($this->administrator)->delete(route('admin.categories.destroy', $category))->assertSessionHas('success');

        $this->assertModelMissing($category);
        $this->assertModelMissing($service);
        $this->assertModelExists($skill);
    }
}
