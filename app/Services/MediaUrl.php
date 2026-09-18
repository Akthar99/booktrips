<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Single place that turns stored image values into browsable URLs.
 *
 * Values may come in three shapes because listings predate the S3 move:
 *  - an absolute URL (uploaded after the S3 migration),
 *  - a legacy "/storage/packages/..." path (local-disk era),
 *  - a bare storage key ("packages/...").
 */
class MediaUrl
{
    /**
     * The disk new photos are stored on.
     */
    public function disk(): string
    {
        return (string) config('booktrips.storage.images_disk', 'public');
    }

    /**
     * Resolve a stored value to a browsable URL.
     */
    public function url(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('~^https?://~i', $value) === 1) {
            return $value;
        }

        if (str_starts_with($value, '/storage/')) {
            return $this->publicUrl(substr($value, strlen('/storage/')));
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        return $this->publicUrl($value);
    }

    /**
     * Extract the storage key from a stored value, or null when the value
     * points somewhere we do not own (external links must never be deleted).
     */
    public function key(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (str_starts_with($value, '/storage/')) {
            return substr($value, strlen('/storage/'));
        }

        if (preg_match('~^https?://~i', $value) === 1) {
            $host = parse_url($value, PHP_URL_HOST);

            if (! is_string($host) || ! in_array($host, $this->knownHosts(), true)) {
                return null;
            }

            $path = (string) parse_url($value, PHP_URL_PATH);

            return $path === '' ? null : ltrim($path, '/');
        }

        if (str_starts_with($value, '/')) {
            return null;
        }

        return $value;
    }

    /**
     * Delete a stored image from the images disk.
     */
    public function forget(?string $value): void
    {
        $key = $this->key($value);

        if ($key !== null) {
            Storage::disk($this->disk())->delete($key);
        }
    }

    /**
     * Delete the images a package no longer uses.
     *
     * @param  array<int, string>  $before
     * @param  array<int, string>  $after
     */
    public function deleteRemoved(array $before, array $after): void
    {
        foreach (array_diff($before, $after) as $value) {
            $this->forget($value);
        }
    }

    /**
     * Build the public URL for a storage key.
     */
    public function publicUrl(string $key): string
    {
        $key = ltrim($key, '/');

        try {
            return (string) Storage::disk($this->disk())->url($key);
        } catch (Throwable) {
            // Best-effort fallback when the host cannot generate URLs itself.
            $bucket = (string) config('filesystems.disks.s3.bucket');

            if ($this->disk() === 's3' && $bucket !== '') {
                $region = (string) config('filesystems.disks.s3.region', 'us-east-1');

                return "https://{$bucket}.s3.{$region}.amazonaws.com/{$key}";
            }

            return '/storage/'.$key;
        }
    }

    /**
     * Hosts this application is allowed to delete objects from.
     *
     * @return array<int, string>
     */
    private function knownHosts(): array
    {
        $hosts = [parse_url($this->publicUrl(''), PHP_URL_HOST), parse_url((string) config('app.url'), PHP_URL_HOST)];

        $bucket = (string) config('filesystems.disks.s3.bucket');

        if ($bucket !== '') {
            $region = (string) config('filesystems.disks.s3.region', 'us-east-1');
            $hosts[] = "{$bucket}.s3.{$region}.amazonaws.com";
        }

        return array_values(array_unique(array_filter($hosts, 'is_string')));
    }
}
