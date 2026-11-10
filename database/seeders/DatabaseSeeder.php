<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * The reference catalog is seeded everywhere; fictional demo accounts and
     * marketplace records are only seeded outside production.
     */
    public function run(): void
    {
        $this->call(CatalogSeeder::class);

        if (app()->isProduction()) {
            $this->command?->warn('Production environment detected: demonstration accounts and marketplace data were not seeded.');

            return;
        }

        $this->call([
            DemoAccountSeeder::class,
            DemoMarketplaceSeeder::class,
        ]);
    }
}
