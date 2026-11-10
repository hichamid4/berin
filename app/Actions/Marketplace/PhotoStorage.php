<?php

namespace App\Actions\Marketplace;

use App\Exceptions\BusinessRuleViolation;
use App\Models\Offer;
use App\Models\OfferPhoto;
use App\Models\RequestPhoto;
use App\Models\ServiceRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Private photo storage for requests and offers.
 *
 * Files are written to the private local disk first, then their metadata is inserted while the
 * parent row is locked so sort-order slots stay unique. Files are removed again if the insert fails.
 */
class PhotoStorage
{
    public const DISK = 'local';

    /**
     * Validation rules shared by request and offer photo uploads.
     *
     * @return array<int, string>
     */
    public static function rules(): array
    {
        return ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function attachToRequest(ServiceRequest $request, array $files): int
    {
        return $this->attach($files, "requests/{$request->id}", function (array $stored) use ($request): void {
            $locked = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
            $this->guardCapacity($locked->photos()->count(), count($stored), ServiceRequest::MAX_PHOTOS);
            $nextOrder = (int) $locked->photos()->max('sort_order') + 1;

            foreach ($stored as $index => $photo) {
                RequestPhoto::query()->create([...$photo, 'request_id' => $locked->id, 'sort_order' => $nextOrder + $index]);
            }
        });
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function attachToOffer(Offer $offer, array $files): int
    {
        return $this->attach($files, "offers/{$offer->id}", function (array $stored) use ($offer): void {
            $locked = Offer::query()->lockForUpdate()->findOrFail($offer->id);
            $this->guardCapacity($locked->photos()->count(), count($stored), Offer::MAX_PHOTOS);
            $nextOrder = (int) $locked->photos()->max('sort_order') + 1;

            foreach ($stored as $index => $photo) {
                OfferPhoto::query()->create([...$photo, 'offer_id' => $locked->id, 'sort_order' => $nextOrder + $index]);
            }
        });
    }

    public function delete(RequestPhoto|OfferPhoto $photo): void
    {
        DB::transaction(fn () => $photo->delete());
        Storage::disk($photo->storage_disk)->delete($photo->storage_path);
    }

    /**
     * @param  list<UploadedFile>  $files
     * @param  callable(list<array<string, mixed>>): void  $persist
     */
    private function attach(array $files, string $directory, callable $persist): int
    {
        if ($files === []) {
            return 0;
        }

        $stored = [];

        try {
            foreach ($files as $file) {
                $path = $file->storeAs($directory, Str::uuid().'.'.$file->extension(), self::DISK);
                $stored[] = [
                    'storage_disk' => self::DISK,
                    'storage_path' => $path,
                    'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                    'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                    'size_bytes' => $file->getSize(),
                ];
            }

            DB::transaction(fn () => $persist($stored));
        } catch (Throwable $exception) {
            Storage::disk(self::DISK)->delete(array_column($stored, 'storage_path'));

            throw $exception;
        }

        return count($stored);
    }

    private function guardCapacity(int $existing, int $adding, int $maximum): void
    {
        if ($existing + $adding > $maximum) {
            throw new BusinessRuleViolation("A maximum of {$maximum} photos is allowed. {$existing} already attached, so you can add ".max(0, $maximum - $existing).' more.');
        }
    }
}
