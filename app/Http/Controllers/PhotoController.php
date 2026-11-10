<?php

namespace App\Http\Controllers;

use App\Models\HandymanProfile;
use App\Models\OfferPhoto;
use App\Models\RequestPhoto;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams privately stored photos after authorizing access at the request or offer boundary.
 */
class PhotoController extends Controller
{
    public function requestPhoto(RequestPhoto $photo): StreamedResponse
    {
        Gate::authorize('view', $photo->request);

        return $this->stream($photo->storage_disk, $photo->storage_path, $photo->mime_type);
    }

    public function offerPhoto(OfferPhoto $photo): StreamedResponse
    {
        Gate::authorize('view', $photo->offer);

        return $this->stream($photo->storage_disk, $photo->storage_path, $photo->mime_type);
    }

    /**
     * Handyman profile photos are public because the profile itself is public.
     */
    public function handymanPhoto(HandymanProfile $handyman): StreamedResponse
    {
        abort_if($handyman->photo_path === null || ! $handyman->user?->isActive(), 404);

        return $this->stream('local', $handyman->photo_path, null, 'public, max-age=3600');
    }

    private function stream(string $disk, string $path, ?string $mimeType, string $cacheControl = 'private, max-age=600'): StreamedResponse
    {
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, null, array_filter([
            'Content-Type' => $mimeType,
            'Cache-Control' => $cacheControl,
            'X-Content-Type-Options' => 'nosniff',
        ]));
    }
}
