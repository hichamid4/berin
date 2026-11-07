<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Product/authentication event times use DATETIME(6) at the app's UTC default.
     * Framework cache and queue tables retain Laravel's stock timestamp types.
     *
     * @var array<string, array<string, array{nullable: bool, current: bool}>>
     */
    private array $dateColumns = [
        'users' => [
            'email_verified_at' => ['nullable' => true, 'current' => false],
            'blocked_at' => ['nullable' => true, 'current' => false],
            'created_at' => ['nullable' => true, 'current' => false],
            'updated_at' => ['nullable' => true, 'current' => false],
            'deleted_at' => ['nullable' => true, 'current' => false],
        ],
        'password_reset_tokens' => [
            'created_at' => ['nullable' => true, 'current' => false],
        ],
        'categories' => [
            'created_at' => ['nullable' => true, 'current' => false],
            'updated_at' => ['nullable' => true, 'current' => false],
        ],
        'services' => [
            'created_at' => ['nullable' => true, 'current' => false],
            'updated_at' => ['nullable' => true, 'current' => false],
        ],
        'skills' => [
            'created_at' => ['nullable' => true, 'current' => false],
            'updated_at' => ['nullable' => true, 'current' => false],
        ],
        'handyman_skill' => [
            'created_at' => ['nullable' => false, 'current' => true],
        ],
        'service_requests' => [
            'handyman_completed_at' => ['nullable' => true, 'current' => false],
            'homeowner_confirmed_at' => ['nullable' => true, 'current' => false],
            'cancelled_at' => ['nullable' => true, 'current' => false],
            'created_at' => ['nullable' => true, 'current' => false],
            'updated_at' => ['nullable' => true, 'current' => false],
        ],
        'offers' => [
            'accepted_at' => ['nullable' => true, 'current' => false],
            'created_at' => ['nullable' => true, 'current' => false],
            'updated_at' => ['nullable' => true, 'current' => false],
        ],
        'request_photos' => [
            'created_at' => ['nullable' => false, 'current' => true],
        ],
        'offer_photos' => [
            'created_at' => ['nullable' => false, 'current' => true],
        ],
        'reviews' => [
            'created_at' => ['nullable' => true, 'current' => false],
            'updated_at' => ['nullable' => true, 'current' => false],
        ],
        'handyman_favorites' => [
            'created_at' => ['nullable' => false, 'current' => true],
        ],
        'activity_events' => [
            'created_at' => ['nullable' => false, 'current' => true],
        ],
    ];

    public function up(): void
    {
        $this->changeDateColumns(toDateTime: true);

        Schema::table('request_photos', function (Blueprint $table): void {
            $table->dropUnique('request_photos_storage_path_unique');
            $table->unique(['storage_disk', 'storage_path'], 'request_photos_disk_path_unique');
        });

        Schema::table('offer_photos', function (Blueprint $table): void {
            $table->dropUnique('offer_photos_storage_path_unique');
            $table->unique(['storage_disk', 'storage_path'], 'offer_photos_disk_path_unique');
        });
    }

    public function down(): void
    {
        Schema::table('request_photos', function (Blueprint $table): void {
            $table->dropUnique('request_photos_disk_path_unique');
            $table->unique('storage_path', 'request_photos_storage_path_unique');
        });

        Schema::table('offer_photos', function (Blueprint $table): void {
            $table->dropUnique('offer_photos_disk_path_unique');
            $table->unique('storage_path', 'offer_photos_storage_path_unique');
        });

        $this->changeDateColumns(toDateTime: false);
    }

    private function changeDateColumns(bool $toDateTime): void
    {
        foreach ($this->dateColumns as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $toDateTime): void {
                foreach ($columns as $columnName => $options) {
                    $column = $toDateTime
                        ? $table->dateTime($columnName, 6)
                        : $table->timestamp($columnName);

                    $column->nullable($options['nullable']);

                    if ($options['current']) {
                        $column->useCurrent();
                    }

                    $column->change();
                }
            });
        }
    }
};
