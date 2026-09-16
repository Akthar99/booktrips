<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeoSearchService
{
    /**
     * Proxy a place search to Nominatim, caching results per query.
     *
     * @return array<int, array{label: string, lat: float, lng: float, name: string}>
     */
    public function search(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return [];
        }

        return Cache::remember('geo:'.md5(mb_strtolower($query)), now()->addMinutes(30), function () use ($query): array {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'BookTrips/1.0 (booktrips.lk)',
                ])->timeout(8)->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 6,
                    'countrycodes' => 'lk',
                    'q' => $query,
                ]);

                if (! $response->successful()) {
                    return [];
                }

                $rows = $response->json();

                if (! is_array($rows)) {
                    return [];
                }

                return collect($rows)
                    ->filter(fn (mixed $row): bool => is_array($row))
                    ->map(fn (array $row): array => [
                        'label' => (string) ($row['display_name'] ?? ''),
                        'lat' => (float) ($row['lat'] ?? 0),
                        'lng' => (float) ($row['lon'] ?? 0),
                        'name' => (string) ($row['name'] ?? explode(',', (string) ($row['display_name'] ?? ''))[0]),
                    ])
                    ->filter(fn (array $row): bool => $row['label'] !== '' && $row['lat'] !== 0.0)
                    ->values()
                    ->all();
            } catch (Throwable $exception) {
                report($exception);

                return [];
            }
        });
    }
}
