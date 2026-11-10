<?php

namespace Database\Seeders;

use App\Models\ActivityEvent;
use App\Models\HandymanProfile;
use App\Models\HomeownerProfile;
use App\Models\Offer;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\OfferStatus;
use App\RequestStatus;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seeds interconnected demonstration marketplace records that obey the approved
 * request/offer/review lifecycle. Repeatable: requests are matched on owner and
 * title, offers on request and handyman, reviews on their offer.
 */
class DemoMarketplaceSeeder extends Seeder
{
    /**
     * Each request: homeowner/handyman values are positions in DemoAccountSeeder lists.
     * Day offsets are relative to today (negative = past).
     *
     * @var list<array<string, mixed>>
     */
    private const REQUESTS = [
        [
            'homeowner' => 0, 'service' => 'Leak repair', 'status' => 'completed', 'created' => -85, 'start' => -80, 'duration' => 1,
            'title' => 'Leaking kitchen tap and damp under the sink cabinet',
            'description' => 'The mixer tap drips constantly and the cabinet floor under the sink is damp. I would like the leak found and fixed, and the tap replaced if needed (I can buy the tap).',
            'location' => 'Résidence Al Manar, apt 7, Boulevard d\'Anfa', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 0, 'price' => 450, 'status' => 'accepted', 'days' => 1, 'description' => 'I will locate the leak with a moisture meter, replace the flexible hoses and the mixer cartridge, and check the drain seal. Parts included except the tap itself.'],
                ['handyman' => 8, 'price' => 600, 'status' => 'superseded', 'days' => 1, 'description' => 'Full inspection of the kitchen supply line and replacement of the tap and connections.'],
            ],
            'review' => ['rating' => 5, 'comment' => 'Youssef found the hidden leak behind the cabinet in twenty minutes and replaced the joint cleanly. Very professional and punctual.'],
        ],
        [
            'homeowner' => 1, 'service' => 'Interior wall painting', 'status' => 'completed', 'created' => -78, 'start' => -70, 'duration' => 4,
            'title' => 'Repaint living room and corridor (about 60 m²)',
            'description' => 'Walls have some hairline cracks and old marks. I want them filled, sanded and painted in a warm white, ceiling included. Furniture can be moved to the centre of the room.',
            'location' => 'Villa 12, Lotissement Les Dunes, Aïn Diab', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 2, 'price' => 3800, 'status' => 'accepted', 'days' => 4, 'description' => 'Preparation, crack filling, one primer coat and two coats of washable matt paint. Paint and protection sheets included.'],
                ['handyman' => 9, 'price' => 4200, 'status' => 'superseded', 'days' => 5, 'description' => 'Premium washable paint with full ceiling treatment and furniture protection.'],
            ],
            'review' => ['rating' => 4, 'comment' => 'Good finish and very clean edges around the doors. It took one day longer than announced, but the result is worth it.'],
        ],
        [
            'homeowner' => 2, 'service' => 'Split AC installation', 'status' => 'completed', 'created' => -72, 'start' => -65, 'duration' => 1,
            'title' => 'Install two split AC units in the bedrooms',
            'description' => 'Two 12,000 BTU units already purchased. The outdoor units should go on the rear balcony; the pipe runs are around 4 metres each.',
            'location' => 'Rue Abou Hanifa, 3rd floor, Maârif', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 4, 'price' => 2600, 'status' => 'accepted', 'days' => 1, 'description' => 'Installation of both units with copper lines, drain routing, vacuum test and commissioning. Brackets and consumables included.'],
                ['handyman' => 10, 'price' => 2900, 'status' => 'superseded', 'days' => 1, 'description' => 'Installation with dedicated electrical line for each unit.'],
            ],
            'review' => ['rating' => 5, 'comment' => 'Mehdi and his colleague installed both units in one morning, tidy pipework and no mess left behind. Highly recommended.'],
        ],
        [
            'homeowner' => 3, 'service' => 'Furniture assembly', 'status' => 'completed', 'created' => -66, 'start' => -62, 'duration' => 1,
            'title' => 'Assemble a 3-door wardrobe and two bedside tables',
            'description' => 'Flat-pack furniture delivered last week. The wardrobe must be fixed to the wall for safety.',
            'location' => 'Rue des Pléiades, Oasis', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 3, 'price' => 700, 'status' => 'accepted', 'days' => 1, 'description' => 'Assembly of all three items, wall anchoring of the wardrobe and removal of packaging.'],
            ],
            'review' => ['rating' => 5, 'comment' => 'Fast, careful and even took away all the cardboard. Everything is level and solid.'],
        ],
        [
            'homeowner' => 4, 'service' => 'Deep home cleaning', 'status' => 'completed', 'created' => -60, 'start' => -56, 'duration' => 1,
            'title' => 'Deep cleaning before a family visit (apartment 120 m²)',
            'description' => 'Three bedrooms, two bathrooms and a kitchen. I would like windows, oven and tile grout included.',
            'location' => 'Boulevard Abdelmoumen, Résidence Palmier, apt 14', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 5, 'price' => 900, 'status' => 'accepted', 'days' => 1, 'description' => 'Team of two for a full day: kitchen degreasing, bathrooms, windows inside and out, floors and grout.'],
                ['handyman' => 9, 'price' => 850, 'status' => 'superseded', 'days' => 1, 'description' => 'Full deep cleaning of all rooms with eco-friendly products.'],
            ],
            'review' => ['rating' => 4, 'comment' => 'The apartment looked brand new. A couple of window frames were missed but Samira came back the next day to finish them.'],
        ],
        [
            'homeowner' => 5, 'service' => 'Electrical panel upgrade', 'status' => 'completed', 'created' => -55, 'start' => -48, 'duration' => 1,
            'title' => 'Replace the old fuse board with a modern breaker panel',
            'description' => 'The 1990s fuse board trips regularly. I want a new panel with residual-current protection and labelled circuits.',
            'location' => 'Avenue Annakhil, Résidence Riad Garden, Hay Riad', 'city' => 'Rabat',
            'offers' => [
                ['handyman' => 1, 'price' => 3200, 'status' => 'accepted', 'days' => 1, 'description' => 'New 24-module panel, 30 mA differential protection, circuit labelling and compliance test. Material included.'],
            ],
            'review' => ['rating' => 5, 'comment' => 'Khalid explained everything, labelled every breaker and left a clean installation. No more tripping.'],
        ],
        [
            'homeowner' => 6, 'service' => 'Floor & wall tiling', 'status' => 'completed', 'created' => -50, 'start' => -42, 'duration' => 5,
            'title' => 'Retile the bathroom floor and shower walls',
            'description' => 'About 6 m² of floor and 9 m² of walls. Tiles are already bought (60x60 porcelain). Old tiles need to be removed.',
            'location' => 'Quartier Le Parc, Rue des Jasmins', 'city' => 'Mohammedia',
            'offers' => [
                ['handyman' => 8, 'price' => 5400, 'status' => 'accepted', 'days' => 5, 'description' => 'Removal of old tiles, levelling, waterproofing of the shower area, tiling and epoxy grouting.'],
                ['handyman' => 6, 'price' => 5900, 'status' => 'superseded', 'days' => 4, 'description' => 'Complete retiling with waterproof membrane and new silicone joints.'],
            ],
            'review' => ['rating' => 3, 'comment' => 'The tiling itself is good quality, but the site was left dusty each evening and the schedule slipped by two days.'],
        ],
        [
            'homeowner' => 7, 'service' => 'Garden maintenance', 'status' => 'completed', 'created' => -45, 'start' => -40, 'duration' => 2,
            'title' => 'Garden cleanup and hedge trimming for a villa',
            'description' => 'Around 300 m² of garden: lawn mowing, trimming of the cypress hedge, pruning of two olive trees and removal of green waste.',
            'location' => 'Ville Verte, Villa 48', 'city' => 'Bouskoura',
            'offers' => [
                ['handyman' => 7, 'price' => 1200, 'status' => 'accepted', 'days' => 2, 'description' => 'Two days with a helper: mowing, hedge shaping, olive pruning and green waste disposal.'],
            ],
            'review' => ['rating' => 5, 'comment' => 'Nadia transformed the garden and gave useful advice on watering. We have booked her for monthly maintenance.'],
        ],
        [
            'homeowner' => 8, 'service' => 'Water heater installation & repair', 'status' => 'completed', 'created' => -40, 'start' => -36, 'duration' => 1,
            'title' => 'Replace an 80 L electric water heater',
            'description' => 'The current heater leaks from the bottom. I would like a new 80 L unit supplied and installed, with the old one taken away.',
            'location' => 'Rue de Strasbourg, Belvédère, 2nd floor', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 0, 'price' => 1500, 'status' => 'accepted', 'days' => 1, 'description' => 'Installation of your new heater with safety group, flexible hoses and removal of the old unit. Heater not included.'],
            ],
            'review' => ['rating' => 4, 'comment' => 'Reliable and tidy work. Arrived a little late but called ahead to let me know.'],
        ],
        [
            'homeowner' => 1, 'service' => 'Custom shelving & cabinets', 'status' => 'completed', 'created' => -36, 'start' => -28, 'duration' => 6,
            'title' => 'Custom bookshelf wall for a home office',
            'description' => 'Floor-to-ceiling shelving on a 3.6 m wall in oak-finish MDF, with closed cabinets at the bottom and integrated LED strip.',
            'location' => 'Villa 12, Lotissement Les Dunes, Aïn Diab', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 3, 'price' => 6800, 'status' => 'accepted', 'days' => 6, 'description' => 'Design, workshop fabrication and on-site installation in oak-finish MDF with soft-close doors. LED strip and transformer included.'],
                ['handyman' => 11, 'price' => 7400, 'status' => 'superseded', 'days' => 8, 'description' => 'Solid beech shelving with hand-finished oil, made in my Marrakech workshop.'],
            ],
            'review' => ['rating' => 5, 'comment' => 'Beautiful craftsmanship, perfectly level and the LED lighting looks stunning in the evening.'],
        ],
        [
            'homeowner' => 7, 'service' => 'Roof terrace waterproofing', 'status' => 'completion_pending', 'created' => -25, 'start' => -12, 'duration' => 5,
            'title' => 'Waterproof the roof terrace before the winter rains',
            'description' => 'Roof terrace of about 90 m² with small cracks and a stain appearing on the ceiling below. Needs a durable waterproofing system.',
            'location' => 'Ville Verte, Villa 48', 'city' => 'Bouskoura',
            'offers' => [
                ['handyman' => 6, 'price' => 8500, 'status' => 'accepted', 'days' => 5, 'description' => 'Crack repair, two-layer liquid membrane with reinforcement mesh and protective top coat. 5-year guarantee.'],
                ['handyman' => 8, 'price' => 9200, 'status' => 'superseded', 'days' => 6, 'description' => 'Bituminous membrane system with new drain outlets.'],
            ],
        ],
        [
            'homeowner' => 2, 'service' => 'Post-renovation cleaning', 'status' => 'completion_pending', 'created' => -14, 'start' => -3, 'duration' => 1,
            'title' => 'Post-renovation cleaning of a 3-room apartment',
            'description' => 'Painters have just finished. There is fine dust everywhere plus some paint splashes on the tiles and windows.',
            'location' => 'Rue Abou Hanifa, 3rd floor, Maârif', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 5, 'price' => 1100, 'status' => 'accepted', 'days' => 1, 'description' => 'Full post-works cleaning: dust extraction, paint splash removal, windows and floor polish.'],
            ],
        ],
        [
            'homeowner' => 0, 'service' => 'Lighting installation', 'status' => 'booked', 'created' => -12, 'start' => 4, 'duration' => 1,
            'title' => 'Install LED spots in the kitchen and hallway',
            'description' => 'Eight recessed LED spots in the kitchen ceiling and four in the hallway, with a dimmer switch for the kitchen.',
            'location' => 'Résidence Al Manar, apt 7, Boulevard d\'Anfa', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 1, 'price' => 1800, 'status' => 'accepted', 'days' => 1, 'description' => 'Twelve dimmable LED spots, wiring, cut-outs and dimmer switch. Spots included (warm white, 4000K optional).'],
                ['handyman' => 10, 'price' => 1650, 'status' => 'superseded', 'days' => 1, 'description' => 'Installation of your spots with new wiring and dimmer.'],
            ],
        ],
        [
            'homeowner' => 4, 'service' => 'Door installation & repair', 'status' => 'booked', 'created' => -10, 'start' => 3, 'duration' => 1,
            'title' => 'Repair a sagging bedroom door and replace the lock',
            'description' => 'The door scrapes the floor and the lock no longer latches. Prefer to keep the existing wooden door.',
            'location' => 'Boulevard Abdelmoumen, Résidence Palmier, apt 14', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 3, 'price' => 550, 'status' => 'accepted', 'days' => 1, 'description' => 'Re-hanging with new hinges, planing of the bottom edge and a new mortise lock with three keys.'],
            ],
        ],
        [
            'homeowner' => 9, 'service' => 'Irrigation system installation', 'status' => 'booked', 'created' => -8, 'start' => 6, 'duration' => 2,
            'title' => 'Drip irrigation for terrace planters',
            'description' => 'Fourteen large planters on a 40 m² terrace. I want a programmable drip system connected to the outdoor tap.',
            'location' => 'Tamaris, Résidence Océan Bleu, villa 6', 'city' => 'Dar Bouazza',
            'offers' => [
                ['handyman' => 7, 'price' => 2300, 'status' => 'accepted', 'days' => 2, 'description' => 'Battery timer, pressure regulator, main line and adjustable drippers for each planter. Material and setup included.'],
                ['handyman' => 0, 'price' => 2000, 'status' => 'superseded', 'days' => 1, 'description' => 'Drip line installation from the outdoor tap with a mechanical timer.'],
            ],
        ],
        [
            'homeowner' => 3, 'service' => 'Bathroom renovation', 'status' => 'open', 'created' => -6, 'start' => 20, 'duration' => 10,
            'title' => 'Full refit of a 5 m² bathroom',
            'description' => 'Complete renovation: remove old fixtures and tiles, new plumbing, walk-in shower, wall-hung toilet, vanity unit and porcelain tiles. Fixtures to be chosen together.',
            'location' => 'Rue des Pléiades, Oasis', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 8, 'price' => 24500, 'status' => 'pending', 'days' => 10, 'description' => 'Turnkey renovation including demolition, plumbing, waterproofing, tiling and installation of the fixtures you choose (fixtures not included).'],
                ['handyman' => 6, 'price' => 26000, 'status' => 'pending', 'days' => 9, 'description' => 'Complete refit with premium waterproofing and large-format tiles; plumbing handled by my partner.'],
                ['handyman' => 0, 'price' => 23000, 'status' => 'declined', 'days' => 12, 'description' => 'Plumbing and fixtures installation; tiling subcontracted.'],
            ],
        ],
        [
            'homeowner' => 5, 'service' => 'Drain unblocking', 'status' => 'open', 'created' => -5, 'start' => 3, 'duration' => 1,
            'title' => 'Blocked kitchen drain, water backs up into the sink',
            'description' => 'The kitchen sink drains very slowly and sometimes backs up. Chemical products did not help.',
            'location' => 'Avenue Annakhil, Résidence Riad Garden, Hay Riad', 'city' => 'Rabat',
            'offers' => [
                ['handyman' => 0, 'price' => 400, 'status' => 'pending', 'days' => 1, 'description' => 'Mechanical unblocking with a drain auger and inspection of the siphon and drain slope.'],
            ],
        ],
        [
            'homeowner' => 6, 'service' => 'Exterior facade painting', 'status' => 'open', 'created' => -4, 'start' => 25, 'duration' => 7,
            'title' => 'Facade painting of a two-storey house',
            'description' => 'Around 180 m² of facade with some flaking paint. Needs washing, repair of small cracks and a weather-resistant finish in off-white.',
            'location' => 'Quartier Le Parc, Rue des Jasmins', 'city' => 'Mohammedia',
            'offers' => [
                ['handyman' => 2, 'price' => 14500, 'status' => 'pending', 'days' => 7, 'description' => 'High-pressure wash, crack repair, fixing primer and two coats of acrylic facade paint. Scaffolding included.'],
            ],
        ],
        [
            'homeowner' => 8, 'service' => 'AC servicing & gas recharge', 'status' => 'open', 'created' => -3, 'start' => 10, 'duration' => 1,
            'title' => 'Annual service for three split AC units',
            'description' => 'Three units (living room and two bedrooms). One of them cools poorly and may need a gas recharge.',
            'location' => 'Rue de Strasbourg, Belvédère, 2nd floor', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 4, 'price' => 750, 'status' => 'pending', 'days' => 1, 'description' => 'Filter and coil cleaning for all three units, leak check and R410A top-up for the weak unit.'],
            ],
        ],
        [
            'homeowner' => 1, 'service' => 'Tadelakt & decorative finishes', 'status' => 'open', 'created' => -2, 'start' => 30, 'duration' => 6,
            'title' => 'Tadelakt finish for the guest bathroom walls',
            'description' => 'About 18 m² of walls in a sand-coloured tadelakt, including the shower area. Looking for an artisan with references.',
            'location' => 'Villa 12, Lotissement Les Dunes, Aïn Diab', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 9, 'price' => 5200, 'status' => 'pending', 'days' => 6, 'description' => 'Traditional Marrakech lime tadelakt, polished with black soap, in the colour of your choice.'],
                ['handyman' => 2, 'price' => 4800, 'status' => 'withdrawn', 'days' => 5, 'description' => 'Tadelakt-effect decorative plaster.'],
            ],
        ],
        [
            'homeowner' => 9, 'service' => 'Move-in / move-out cleaning', 'status' => 'open', 'created' => -1, 'start' => 7, 'duration' => 1,
            'title' => 'Move-out cleaning of a studio in Gauthier',
            'description' => 'Empty 35 m² studio to be returned to the landlord next week. Kitchen, bathroom, windows and floors.',
            'location' => 'Rue Jean Jaurès, Gauthier, studio 5B', 'city' => 'Casablanca',
            'offers' => [],
        ],
        [
            'homeowner' => 7, 'service' => 'Socket & switch installation', 'status' => 'open', 'created' => -1, 'start' => 12, 'duration' => 1,
            'title' => 'Add sockets and a dedicated oven circuit in the kitchen',
            'description' => 'Four new double sockets above the worktop and a dedicated 32 A line for a built-in oven.',
            'location' => 'Ville Verte, Villa 48', 'city' => 'Bouskoura',
            'offers' => [],
        ],
        [
            'homeowner' => 4, 'service' => 'Wallpaper installation', 'status' => 'cancelled', 'created' => -30, 'start' => -15, 'duration' => 1, 'cancelled' => -27,
            'title' => 'Wallpaper feature wall in the nursery',
            'description' => 'One 4 m wall with a patterned wallpaper (already purchased, 3 rolls).',
            'location' => 'Boulevard Abdelmoumen, Résidence Palmier, apt 14', 'city' => 'Casablanca',
            'offers' => [
                ['handyman' => 9, 'price' => 1300, 'status' => 'declined', 'days' => 1, 'description' => 'Wall preparation and pattern-matched hanging of your wallpaper.'],
            ],
        ],
    ];

    /**
     * Homeowner favorites as [homeowner position, handyman position].
     *
     * @var list<array{0: int, 1: int}>
     */
    private const FAVORITES = [
        [0, 0], [0, 1], [1, 2], [1, 3], [2, 4], [2, 5], [3, 3], [3, 8],
        [4, 5], [4, 3], [5, 1], [6, 8], [7, 7], [7, 6], [8, 0],
    ];

    public function run(): void
    {
        $today = CarbonImmutable::today();
        $homeowners = $this->profilesByPosition(HomeownerProfile::class, DemoAccountSeeder::HOMEOWNERS);
        $handymen = $this->profilesByPosition(HandymanProfile::class, DemoAccountSeeder::HANDYMEN);
        $services = Service::query()->with('skills:id')->get()->keyBy('name');

        DB::transaction(function () use ($today, $homeowners, $handymen, $services): void {
            foreach (self::REQUESTS as $definition) {
                $homeowner = $homeowners[$definition['homeowner']];
                $service = $services[$definition['service']] ?? throw new RuntimeException("Unknown service {$definition['service']}");
                $createdAt = $today->addDays($definition['created'])->setTime(10, 15);
                $startDate = $today->addDays($definition['start']);

                $request = ServiceRequest::query()->firstOrNew(['homeowner_id' => $homeowner->user_id, 'title' => $definition['title']]);
                $request->fill([
                    'service_id' => $service->id,
                    'description' => $definition['description'],
                    'service_location' => $definition['location'],
                    'service_city' => $definition['city'],
                    'desired_start_date' => $startDate,
                ]);

                if (! $request->exists) {
                    $request->status = RequestStatus::Open;
                    $request->created_at = $createdAt;
                    $request->updated_at = $createdAt;
                }

                $request->save();
                $this->recordEvent('request.created', 'service_request', $request->id, $homeowner->user_id, $createdAt, ['title' => $request->title]);

                $acceptedOffer = null;

                foreach ($definition['offers'] as $position => $offerDefinition) {
                    $handyman = $handymen[$offerDefinition['handyman']];
                    $this->assertSkillMatch($service, $handyman);

                    $offerCreatedAt = $createdAt->addDays(1)->addHours($position * 3);
                    $status = OfferStatus::from($offerDefinition['status']);
                    $acceptedAt = $status === OfferStatus::Accepted ? $createdAt->addDays(2)->setTime(18, 0) : null;

                    $offer = Offer::query()->firstOrNew(['request_id' => $request->id, 'handyman_id' => $handyman->user_id]);
                    $offer->fill([
                        'price' => $offerDefinition['price'],
                        'currency' => 'MAD',
                        'start_date' => $startDate,
                        'end_date' => $startDate->addDays($offerDefinition['days'] - 1),
                        'description' => $offerDefinition['description'],
                        'status' => $status,
                        'accepted_at' => $acceptedAt,
                    ]);

                    if (! $offer->exists) {
                        $offer->created_at = $offerCreatedAt;
                        $offer->updated_at = $acceptedAt ?? $offerCreatedAt;
                    }

                    $offer->save();
                    $this->recordEvent('offer.submitted', 'offer', $offer->id, $handyman->user_id, $offerCreatedAt, ['price' => $offer->price, 'currency' => 'MAD']);

                    if ($status === OfferStatus::Accepted) {
                        $acceptedOffer = $offer;
                        $this->recordEvent('offer.accepted', 'offer', $offer->id, $homeowner->user_id, $acceptedAt);
                    }
                }

                $this->applyLifecycle($request, $definition, $acceptedOffer, $today, $startDate);

                if (isset($definition['review']) && $acceptedOffer !== null) {
                    $reviewedAt = $request->homeowner_confirmed_at->addDay()->setTime(20, 45);
                    $review = Review::query()->updateOrCreate(
                        ['offer_id' => $acceptedOffer->id],
                        ['rating' => $definition['review']['rating'], 'comment' => $definition['review']['comment']],
                    );

                    if ($review->wasRecentlyCreated) {
                        $review->forceFill(['created_at' => $reviewedAt, 'updated_at' => $reviewedAt])->save();
                    }

                    $this->recordEvent('review.submitted', 'review', $acceptedOffer->id, $homeowner->user_id, $reviewedAt, ['rating' => $review->rating]);
                }
            }

            foreach (self::FAVORITES as [$homeownerPosition, $handymanPosition]) {
                $homeowners[$homeownerPosition]->favoriteHandymen()->syncWithoutDetaching([
                    $handymen[$handymanPosition]->user_id,
                ]);
            }
        });
    }

    /**
     * Move the request into its approved lifecycle state once its offers exist.
     *
     * @param  array<string, mixed>  $definition
     */
    private function applyLifecycle(ServiceRequest $request, array $definition, ?Offer $acceptedOffer, CarbonImmutable $today, CarbonImmutable $startDate): void
    {
        $status = RequestStatus::from($definition['status']);
        $finishedAt = $startDate->addDays($definition['duration'] - 1)->setTime(17, 30);

        $attributes = match ($status) {
            RequestStatus::Open => ['accepted_offer_id' => null, 'handyman_completed_at' => null, 'homeowner_confirmed_at' => null, 'cancelled_at' => null],
            RequestStatus::Booked => ['accepted_offer_id' => $acceptedOffer?->id, 'handyman_completed_at' => null, 'homeowner_confirmed_at' => null, 'cancelled_at' => null],
            RequestStatus::CompletionPending => ['accepted_offer_id' => $acceptedOffer?->id, 'handyman_completed_at' => $finishedAt, 'homeowner_confirmed_at' => null, 'cancelled_at' => null],
            RequestStatus::Completed => ['accepted_offer_id' => $acceptedOffer?->id, 'handyman_completed_at' => $finishedAt, 'homeowner_confirmed_at' => $finishedAt->addDay()->setTime(9, 0), 'cancelled_at' => null],
            RequestStatus::Cancelled => ['accepted_offer_id' => null, 'handyman_completed_at' => null, 'homeowner_confirmed_at' => null, 'cancelled_at' => $today->addDays($definition['cancelled'])->setTime(12, 0)],
        };

        if (in_array($status, [RequestStatus::Booked, RequestStatus::CompletionPending, RequestStatus::Completed], true) && $acceptedOffer === null) {
            throw new RuntimeException("Request '{$request->title}' needs an accepted offer for status {$status->value}.");
        }

        $request->forceFill([...$attributes, 'status' => $status])->save();

        if ($status === RequestStatus::Completed) {
            $this->recordEvent('request.completed', 'service_request', $request->id, $request->homeowner_id, $request->homeowner_confirmed_at);
        }

        if ($status === RequestStatus::Cancelled) {
            $this->recordEvent('request.cancelled', 'service_request', $request->id, $request->homeowner_id, $request->cancelled_at);
        }
    }

    private function assertSkillMatch(Service $service, HandymanProfile $handyman): void
    {
        $shared = $service->skills->pluck('id')->intersect($handyman->skills->pluck('id'));

        if ($shared->isEmpty()) {
            throw new RuntimeException("Handyman {$handyman->user_id} has no skill required by service '{$service->name}'.");
        }
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function recordEvent(string $action, string $subjectType, int $subjectId, ?int $actorId, DateTimeInterface $occurredAt, ?array $metadata = null): void
    {
        ActivityEvent::query()->firstOrCreate(
            ['action' => $action, 'subject_type' => $subjectType, 'subject_id' => $subjectId],
            ['actor_user_id' => $actorId, 'metadata' => $metadata, 'created_at' => $occurredAt],
        );
    }

    /**
     * @template TProfile of HomeownerProfile|HandymanProfile
     *
     * @param  class-string<TProfile>  $profileClass
     * @param  list<array<string, mixed>>  $definitions
     * @return array<int, TProfile>
     */
    private function profilesByPosition(string $profileClass, array $definitions): array
    {
        $emails = array_column($definitions, 'email');
        $usersByEmail = User::query()->whereIn('email', $emails)->pluck('id', 'email');
        $profiles = $profileClass::query()
            ->when($profileClass === HandymanProfile::class, fn ($query) => $query->with('skills:id'))
            ->whereIn('user_id', $usersByEmail->values())
            ->get()
            ->keyBy('user_id');

        return collect($definitions)->map(fn (array $definition) => $profiles[$usersByEmail[$definition['email']]])->all();
    }
}
