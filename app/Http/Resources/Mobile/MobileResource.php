<?php

namespace App\Http\Resources\Mobile;

use Carbon\CarbonInterface;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base for mobile API resources: one consistent UTC timestamp format for the app to parse.
 */
abstract class MobileResource extends JsonResource
{
    protected static function timestamp(?CarbonInterface $value): ?string
    {
        return $value?->toIso8601ZuluString('millisecond');
    }

    protected static function date(?CarbonInterface $value): ?string
    {
        return $value?->toDateString();
    }
}
