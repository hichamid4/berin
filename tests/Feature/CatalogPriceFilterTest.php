<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Offer;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\OfferStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CatalogPriceFilterTest extends TestCase
{
    use DatabaseTransactions;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // A dedicated category keeps the assertions independent of demonstration data.
        $this->category = Category::factory()->create(['name' => 'Price filter test category']);
    }

    private function serviceWithOffers(string $name, array $offers): Service
    {
        $service = Service::factory()->for($this->category)->create(['name' => $name]);

        foreach ($offers as [$price, $status]) {
            $request = ServiceRequest::factory()->for($service)->create();
            Offer::factory()->for($request, 'request')->create([
                'price' => $price,
                'status' => $status,
                'accepted_at' => $status === OfferStatus::Accepted ? now() : null,
            ]);
        }

        return $service;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function catalog(array $filters = []): TestResponse
    {
        return $this->get(route('services.index', ['category' => $this->category->id, ...$filters]));
    }

    public function test_services_are_kept_only_when_a_live_or_accepted_offer_falls_in_the_range(): void
    {
        $this->serviceWithOffers('Budget tiling', [[300, OfferStatus::Pending], [450, OfferStatus::Accepted]]);
        $this->serviceWithOffers('Premium kitchen fitting', [[4200, OfferStatus::Pending]]);
        $this->serviceWithOffers('Mid-range painting', [[900, OfferStatus::Accepted], [5000, OfferStatus::Pending]]);

        $this->catalog(['min_price' => 400, 'max_price' => 1000])
            ->assertOk()
            ->assertSee('Budget tiling')
            ->assertSee('Mid-range painting')
            ->assertDontSee('Premium kitchen fitting')
            ->assertSee('2 services found')
            ->assertSee('with offers between 400 and 1 000 MAD');
    }

    public function test_minimum_only_and_maximum_only_ranges_are_inclusive(): void
    {
        $this->serviceWithOffers('Exactly five hundred', [[500, OfferStatus::Pending]]);
        $this->serviceWithOffers('Two hundred job', [[200, OfferStatus::Pending]]);

        $this->catalog(['min_price' => 500])->assertSee('Exactly five hundred')->assertDontSee('Two hundred job')->assertSee('with offers from 500 MAD');
        $this->catalog(['max_price' => 500])->assertSee('Exactly five hundred')->assertSee('Two hundred job')->assertSee('with offers up to 500 MAD');
        $this->catalog(['max_price' => 499.99])->assertDontSee('Exactly five hundred')->assertSee('Two hundred job');
    }

    public function test_withdrawn_declined_and_superseded_offers_do_not_count(): void
    {
        $this->serviceWithOffers('Only stale offers', [
            [600, OfferStatus::Withdrawn],
            [650, OfferStatus::Declined],
            [700, OfferStatus::Superseded],
        ]);
        $this->serviceWithOffers('Live offer service', [[650, OfferStatus::Pending]]);

        $this->catalog(['min_price' => 500, 'max_price' => 800])
            ->assertSee('Live offer service')
            ->assertDontSee('Only stale offers');
    }

    public function test_services_without_offers_are_listed_normally_but_excluded_by_a_price_filter(): void
    {
        Service::factory()->for($this->category)->create(['name' => 'Brand new service']);
        $this->serviceWithOffers('Service with offers', [[800, OfferStatus::Pending]]);

        $this->catalog()->assertSee('Brand new service')->assertSee('Service with offers');
        $this->catalog(['min_price' => 0])->assertDontSee('Brand new service')->assertSee('Service with offers');
    }

    public function test_cards_show_the_offer_price_range_from_real_offers_only(): void
    {
        $this->serviceWithOffers('Range card service', [
            [350, OfferStatus::Pending],
            [1250, OfferStatus::Accepted],
            [9000, OfferStatus::Withdrawn],
        ]);
        $this->serviceWithOffers('Single price service', [[700, OfferStatus::Pending]]);
        Service::factory()->for($this->category)->create(['name' => 'No offer service']);

        $response = $this->catalog()->assertOk();
        $response->assertSee('350 – 1 250 MAD');
        $response->assertDontSee('9 000 MAD');
        $response->assertSee('700 MAD');
        $this->assertSame(2, substr_count($response->getContent(), 'data-offer-range'));
    }

    public function test_price_filter_combines_with_search_and_keeps_inactive_services_hidden(): void
    {
        $this->serviceWithOffers('Garden irrigation', [[500, OfferStatus::Pending]]);
        $this->serviceWithOffers('Garden lighting', [[2500, OfferStatus::Pending]]);
        $hidden = $this->serviceWithOffers('Garden pond (inactive)', [[600, OfferStatus::Pending]]);
        $hidden->update(['is_active' => false]);

        $this->catalog(['q' => 'Garden', 'max_price' => 1000])
            ->assertSee('Garden irrigation')
            ->assertDontSee('Garden lighting')
            ->assertDontSee('Garden pond (inactive)');
    }

    public function test_category_links_keep_the_price_range(): void
    {
        $this->serviceWithOffers('Linked service', [[500, OfferStatus::Pending]]);

        $this->catalog(['min_price' => 100, 'max_price' => 900])
            ->assertSee(e(route('services.index', ['category' => $this->category->id, 'min_price' => 100, 'max_price' => 900])), false);
    }

    public function test_invalid_ranges_return_to_the_catalog_with_clear_messages(): void
    {
        $this->get(route('services.index', ['min_price' => 900, 'max_price' => 100]))
            ->assertRedirect(route('services.index'))
            ->assertSessionHasErrors(['max_price' => 'The maximum price must be greater than or equal to the minimum price.']);

        $this->get(route('services.index', ['min_price' => -5]))
            ->assertSessionHasErrors(['min_price' => 'The minimum price cannot be negative.']);

        $this->get(route('services.index', ['max_price' => 'cheap']))
            ->assertSessionHasErrors(['max_price' => 'Enter the maximum price as a number of dirhams, for example 1500.']);

        $this->followingRedirects()->get(route('services.index', ['min_price' => 900, 'max_price' => 100]))
            ->assertOk()
            ->assertSee('The maximum price must be greater than or equal to the minimum price.');
    }

    public function test_prices_typed_with_spaces_or_a_decimal_comma_are_understood(): void
    {
        $this->serviceWithOffers('Formatted price service', [[1500.5, OfferStatus::Pending]]);

        $this->catalog(['min_price' => '1 500,50', 'max_price' => '1 500,50'])->assertOk()->assertSee('Formatted price service');
    }

    public function test_the_service_page_and_the_filter_share_the_same_price_definition(): void
    {
        $service = $this->serviceWithOffers('Consistent service', [
            [400, OfferStatus::Pending],
            [800, OfferStatus::Accepted],
            [100, OfferStatus::Withdrawn],
        ]);

        $this->get(route('services.show', $service))->assertSee('400 – 800')->assertSee('across 2 offers');
        $this->catalog(['max_price' => 150])->assertDontSee('Consistent service');
    }
}
