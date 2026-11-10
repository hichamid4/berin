<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Service;
use App\Models\Skill;
use Illuminate\Database\Seeder;

/**
 * Seeds the reference catalog: categories, services, skills and the service-to-skill mapping.
 * Safe to run repeatedly; rows are matched on their unique names.
 */
class CatalogSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const SKILLS = [
        'Pipe fitting' => 'Installing and repairing water supply and drainage pipework.',
        'Leak detection' => 'Locating hidden leaks in walls, floors and fixtures.',
        'Water heater installation' => 'Fitting and servicing gas and electric water heaters.',
        'Electrical wiring' => 'Running, replacing and securing domestic circuits.',
        'Lighting installation' => 'Fitting ceiling lights, spots, LED strips and outdoor lighting.',
        'Electrical panel maintenance' => 'Upgrading breakers and distribution boards to current standards.',
        'Interior painting' => 'Preparing and painting interior walls and ceilings.',
        'Exterior painting' => 'Weather-resistant facade and balcony painting.',
        'Wallpapering & decorative finishes' => 'Wallpaper, tadelakt and decorative plaster work.',
        'Furniture assembly' => 'Assembling flat-pack and modular furniture.',
        'Custom woodworking' => 'Made-to-measure shelving, cabinets and wooden fittings.',
        'Door & window fitting' => 'Installing and adjusting doors, frames and window units.',
        'Air conditioning installation' => 'Mounting split units and routing refrigerant lines.',
        'HVAC maintenance' => 'Servicing, cleaning and recharging cooling and heating systems.',
        'Deep cleaning' => 'Thorough cleaning of kitchens, bathrooms and living areas.',
        'Post-renovation cleaning' => 'Removing construction dust and residue after works.',
        'Garden maintenance' => 'Lawn care, pruning, planting and seasonal upkeep.',
        'Irrigation systems' => 'Designing and installing drip and sprinkler irrigation.',
        'Tiling' => 'Laying floor and wall tiles, zellige and mosaic.',
        'Plastering & masonry' => 'Wall repair, plastering, brickwork and waterproofing.',
    ];

    /**
     * @var array<string, array<string, array{description: string, skills: list<string>}>>
     */
    private const CATALOG = [
        'Plumbing' => [
            'Leak repair' => [
                'description' => 'Diagnosis and repair of dripping taps, leaking joints and hidden pipe leaks, including moisture checks after the repair.',
                'skills' => ['Leak detection', 'Pipe fitting'],
            ],
            'Bathroom fixture installation' => [
                'description' => 'Installation of sinks, toilets, showers and mixer taps with sealed connections and a water-tightness test.',
                'skills' => ['Pipe fitting'],
            ],
            'Water heater installation & repair' => [
                'description' => 'Replacement or repair of gas and electric water heaters, including safety valve and connection checks.',
                'skills' => ['Water heater installation', 'Pipe fitting'],
            ],
            'Drain unblocking' => [
                'description' => 'Mechanical unblocking of kitchen and bathroom drains with a camera check for recurring blockages.',
                'skills' => ['Pipe fitting', 'Leak detection'],
            ],
        ],
        'Electrical' => [
            'Electrical fault repair' => [
                'description' => 'Tracing and fixing tripping breakers, dead sockets and faulty circuits with a safety test afterwards.',
                'skills' => ['Electrical wiring', 'Electrical panel maintenance'],
            ],
            'Lighting installation' => [
                'description' => 'Fitting chandeliers, recessed spots, LED strips and garden lighting with concealed wiring.',
                'skills' => ['Lighting installation', 'Electrical wiring'],
            ],
            'Electrical panel upgrade' => [
                'description' => 'Replacement of old fuse boards with modern breaker panels and residual-current protection.',
                'skills' => ['Electrical panel maintenance'],
            ],
            'Socket & switch installation' => [
                'description' => 'Adding or moving sockets, switches and dedicated appliance circuits.',
                'skills' => ['Electrical wiring'],
            ],
        ],
        'Painting & Decoration' => [
            'Interior wall painting' => [
                'description' => 'Surface preparation, filling and two coats of washable paint for rooms, corridors and ceilings.',
                'skills' => ['Interior painting'],
            ],
            'Exterior facade painting' => [
                'description' => 'Cleaning, priming and painting of facades and balconies with weather-resistant coatings.',
                'skills' => ['Exterior painting'],
            ],
            'Wallpaper installation' => [
                'description' => 'Measuring, cutting and hanging wallpaper or murals with aligned patterns and clean edges.',
                'skills' => ['Wallpapering & decorative finishes'],
            ],
            'Tadelakt & decorative finishes' => [
                'description' => 'Traditional Moroccan tadelakt, Venetian plaster and textured decorative wall finishes.',
                'skills' => ['Wallpapering & decorative finishes', 'Plastering & masonry'],
            ],
        ],
        'Carpentry & Furniture' => [
            'Furniture assembly' => [
                'description' => 'Assembly and secure wall-fixing of wardrobes, beds, desks and kitchen units.',
                'skills' => ['Furniture assembly'],
            ],
            'Custom shelving & cabinets' => [
                'description' => 'Made-to-measure shelving, built-in wardrobes and storage designed for your space.',
                'skills' => ['Custom woodworking'],
            ],
            'Door installation & repair' => [
                'description' => 'Hanging new interior doors, replacing locks and hinges, and fixing doors that stick or sag.',
                'skills' => ['Door & window fitting'],
            ],
            'Window frame repair' => [
                'description' => 'Repair and sealing of wooden and aluminium window frames, including handles and seals.',
                'skills' => ['Door & window fitting', 'Custom woodworking'],
            ],
        ],
        'Air Conditioning & Heating' => [
            'Split AC installation' => [
                'description' => 'Wall mounting of split air-conditioning units, pipe routing, vacuum test and commissioning.',
                'skills' => ['Air conditioning installation', 'Electrical wiring'],
            ],
            'AC servicing & gas recharge' => [
                'description' => 'Filter cleaning, coil wash, refrigerant top-up and performance check before summer.',
                'skills' => ['HVAC maintenance'],
            ],
            'Heating system maintenance' => [
                'description' => 'Servicing of boilers, radiators and heat-pump units for safe and efficient winter use.',
                'skills' => ['HVAC maintenance', 'Water heater installation'],
            ],
        ],
        'Cleaning & Housekeeping' => [
            'Deep home cleaning' => [
                'description' => 'Top-to-bottom cleaning of every room, including kitchen appliances, tiles and windows.',
                'skills' => ['Deep cleaning'],
            ],
            'Post-renovation cleaning' => [
                'description' => 'Removal of dust, paint splashes and debris after building or decoration works.',
                'skills' => ['Post-renovation cleaning', 'Deep cleaning'],
            ],
            'Carpet & upholstery cleaning' => [
                'description' => 'Steam and extraction cleaning of carpets, rugs, sofas and mattresses.',
                'skills' => ['Deep cleaning'],
            ],
            'Move-in / move-out cleaning' => [
                'description' => 'Complete cleaning of an empty apartment before handing over or moving in.',
                'skills' => ['Deep cleaning', 'Post-renovation cleaning'],
            ],
        ],
        'Gardening & Outdoor' => [
            'Garden maintenance' => [
                'description' => 'Lawn mowing, hedge trimming, pruning and seasonal planting for gardens of any size.',
                'skills' => ['Garden maintenance'],
            ],
            'Irrigation system installation' => [
                'description' => 'Drip and sprinkler irrigation with programmable timers to save water.',
                'skills' => ['Irrigation systems', 'Pipe fitting'],
            ],
            'Terrace & balcony landscaping' => [
                'description' => 'Planters, artificial lawn, decking and plant selection for terraces and balconies.',
                'skills' => ['Garden maintenance', 'Irrigation systems'],
            ],
        ],
        'Masonry & Renovation' => [
            'Floor & wall tiling' => [
                'description' => 'Laying ceramic, porcelain and zellige tiles with levelled surfaces and clean grouting.',
                'skills' => ['Tiling'],
            ],
            'Bathroom renovation' => [
                'description' => 'Complete bathroom refurbishment: demolition, plumbing, tiling and fixture installation.',
                'skills' => ['Tiling', 'Pipe fitting', 'Plastering & masonry'],
            ],
            'Wall plastering & repair' => [
                'description' => 'Repair of cracks and damp patches, skim plastering and preparation for painting.',
                'skills' => ['Plastering & masonry'],
            ],
            'Roof terrace waterproofing' => [
                'description' => 'Membrane and liquid waterproofing of flat roofs and terraces against winter rain.',
                'skills' => ['Plastering & masonry'],
            ],
        ],
    ];

    public function run(): void
    {
        $skills = collect(self::SKILLS)->map(
            fn (string $description, string $name): Skill => Skill::query()->updateOrCreate(
                ['name' => $name],
                ['description' => $description, 'is_active' => true],
            ),
        );

        foreach (self::CATALOG as $categoryName => $services) {
            $category = Category::query()->updateOrCreate(['name' => $categoryName], ['is_active' => true]);

            foreach ($services as $serviceName => $definition) {
                $service = Service::query()->updateOrCreate(
                    ['category_id' => $category->id, 'name' => $serviceName],
                    ['description' => $definition['description'], 'is_active' => true],
                );

                $service->skills()->syncWithoutDetaching(
                    collect($definition['skills'])->map(fn (string $skill): int => $skills[$skill]->id)->all(),
                );
            }
        }
    }
}
