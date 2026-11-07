<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatabaseSchemaCompatibilityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_mysql_has_the_generated_guard_and_composite_foreign_key(): void
    {
        $generatedColumn = DB::selectOne(
            'SELECT EXTRA, GENERATION_EXPRESSION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['offers', 'accepted_request_id'],
        );

        $this->assertNotNull($generatedColumn);
        $this->assertStringContainsString('STORED GENERATED', strtoupper($generatedColumn->EXTRA));
        $this->assertStringContainsString('request_id', strtolower($generatedColumn->GENERATION_EXPRESSION));

        $uniqueIndex = DB::selectOne(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND NON_UNIQUE = 0',
            ['offers', 'accepted_request_id'],
        );

        $this->assertNotNull($uniqueIndex);
        $this->assertSame('offers_one_accepted_per_request_unique', $uniqueIndex->INDEX_NAME);

        $foreignKeyColumns = DB::select(
            'SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME, ORDINAL_POSITION FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? ORDER BY ORDINAL_POSITION',
            ['service_requests', 'service_requests_selected_offer_fk'],
        );

        $this->assertCount(2, $foreignKeyColumns);
        $this->assertSame('id', $foreignKeyColumns[0]->COLUMN_NAME);
        $this->assertSame('request_id', $foreignKeyColumns[0]->REFERENCED_COLUMN_NAME);
        $this->assertSame('accepted_offer_id', $foreignKeyColumns[1]->COLUMN_NAME);
        $this->assertSame('id', $foreignKeyColumns[1]->REFERENCED_COLUMN_NAME);
        $this->assertSame('offers', $foreignKeyColumns[0]->REFERENCED_TABLE_NAME);
    }

    public function test_business_timestamps_use_datetime6_and_photo_paths_are_disk_scoped(): void
    {
        $timestamps = DB::select(
            'SELECT DATA_TYPE, DATETIME_PRECISION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AND DATA_TYPE IN (?, ?) ',
            [
                'users', 'password_reset_tokens', 'categories', 'services', 'skills', 'handyman_skill',
                'service_requests', 'offers', 'request_photos', 'offer_photos', 'reviews',
                'handyman_favorites', 'activity_events', 'timestamp', 'datetime',
            ],
        );

        $this->assertCount(27, $timestamps);
        foreach ($timestamps as $timestamp) {
            $this->assertSame('datetime', $timestamp->DATA_TYPE);
            $this->assertSame(6, (int) $timestamp->DATETIME_PRECISION);
        }

        foreach (['request_photos' => 'request_photos_disk_path_unique', 'offer_photos' => 'offer_photos_disk_path_unique'] as $table => $indexName) {
            $indexColumns = DB::select(
                'SELECT COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? ORDER BY SEQ_IN_INDEX',
                [$table, $indexName],
            );

            $this->assertSame(['storage_disk', 'storage_path'], array_map(
                static fn (object $column): string => $column->COLUMN_NAME,
                $indexColumns,
            ));
        }

        $comment = DB::selectOne(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['reviews', 'comment'],
        );

        $this->assertSame('YES', $comment->IS_NULLABLE);
    }

    public function test_photo_paths_may_repeat_across_disks_but_not_on_the_same_disk(): void
    {
        [$homeownerId, , , $serviceId] = $this->createMarketplaceFixture();
        $firstRequestId = $this->createRequest($homeownerId, $serviceId);
        $secondRequestId = $this->createRequest($homeownerId, $serviceId);
        $thirdRequestId = $this->createRequest($homeownerId, $serviceId);
        $sharedPath = 'requests/shared-image.jpg';

        foreach ([[$firstRequestId, 'local'], [$secondRequestId, 's3']] as [$requestId, $disk]) {
            DB::table('request_photos')->insert([
                'request_id' => $requestId,
                'storage_disk' => $disk,
                'storage_path' => $sharedPath,
                'mime_type' => 'image/jpeg',
                'size_bytes' => 1024,
                'sort_order' => 0,
                'created_at' => now(),
            ]);
        }

        $this->expectException(QueryException::class);
        DB::table('request_photos')->insert([
            'request_id' => $thirdRequestId,
            'storage_disk' => 'local',
            'storage_path' => $sharedPath,
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'sort_order' => 0,
            'created_at' => now(),
        ]);
    }

    public function test_mysql_rejects_a_second_accepted_offer_for_the_same_request(): void
    {
        [$homeownerId, $handymanOneId, $handymanTwoId, $serviceId] = $this->createMarketplaceFixture();
        $requestId = $this->createRequest($homeownerId, $serviceId);

        $firstOfferId = $this->createOffer($requestId, $handymanOneId, 'accepted');

        $this->assertSame(
            $requestId,
            DB::table('offers')->where('id', $firstOfferId)->value('accepted_request_id'),
        );

        $this->expectException(QueryException::class);
        $this->createOffer($requestId, $handymanTwoId, 'accepted');
    }

    public function test_selected_offer_foreign_key_rejects_an_offer_from_another_request(): void
    {
        [$homeownerId, $handymanId, , $serviceId] = $this->createMarketplaceFixture();
        $firstRequestId = $this->createRequest($homeownerId, $serviceId);
        $secondRequestId = $this->createRequest($homeownerId, $serviceId);
        $offerForSecondRequestId = $this->createOffer($secondRequestId, $handymanId, 'pending');

        $this->expectException(QueryException::class);

        DB::table('service_requests')->where('id', $firstRequestId)->update([
            'status' => 'booked',
            'accepted_offer_id' => $offerForSecondRequestId,
        ]);
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    private function createMarketplaceFixture(): array
    {
        $homeownerId = $this->createUser('homeowner');
        DB::table('homeowner_profiles')->insert(['user_id' => $homeownerId]);

        $handymanOneId = $this->createUser('handyman');
        DB::table('handyman_profiles')->insert(['user_id' => $handymanOneId]);

        $handymanTwoId = $this->createUser('handyman');
        DB::table('handyman_profiles')->insert(['user_id' => $handymanTwoId]);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Category '.Str::uuid(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $serviceId = DB::table('services')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Service '.Str::uuid(),
            'description' => 'Schema compatibility test service',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$homeownerId, $handymanOneId, $handymanTwoId, $serviceId];
    }

    private function createUser(string $role): int
    {
        $identifier = Str::uuid()->toString();

        return (int) DB::table('users')->insertGetId([
            'role' => $role,
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'test-'.Str::before($identifier, '-'),
            'email' => $role.'-'.Str::uuid().'@example.test',
            'password' => bcrypt(Str::random(40)),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createRequest(int $homeownerId, int $serviceId): int
    {
        return (int) DB::table('service_requests')->insertGetId([
            'homeowner_id' => $homeownerId,
            'service_id' => $serviceId,
            'title' => 'Schema compatibility test',
            'description' => 'Request created to verify MySQL relationship constraints.',
            'service_location' => 'Private test location',
            'service_city' => 'Test city',
            'desired_start_date' => now()->addWeek()->toDateString(),
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOffer(int $requestId, int $handymanId, string $status): int
    {
        return (int) DB::table('offers')->insertGetId([
            'request_id' => $requestId,
            'handyman_id' => $handymanId,
            'price' => '125.00',
            'currency' => 'MAD',
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->addDay()->toDateString(),
            'description' => 'Schema compatibility test offer.',
            'status' => $status,
            'accepted_at' => $status === 'accepted' ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
