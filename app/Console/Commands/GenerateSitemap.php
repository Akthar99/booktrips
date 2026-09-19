<?php

namespace App\Console\Commands;

use App\Models\Package;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;

/**
 * Rebuilds public/sitemap.xml from the active catalogue.
 *
 * Reads the database directly (no crawling), so the sitemap is exact and can
 * run on every deploy or nightly via the scheduler — see the Ploi cron entry
 * running `php artisan schedule:run` every minute.
 */
class GenerateSitemap extends Command
{
    protected $signature = 'booktrips:sitemap:generate';

    protected $description = 'Rebuild public/sitemap.xml from the active catalogue';

    public function handle(): int
    {
        $entries = [
            $this->entry(route('home'), 'daily', '1.0'),
            $this->entry(route('search'), 'daily', '0.9'),
            $this->entry(route('map'), 'weekly', '0.7'),
            $this->entry(route('partners'), 'monthly', '0.6'),
            $this->entry(route('about'), 'monthly', '0.5'),
            $this->entry(route('privacy'), 'yearly', '0.3'),
            $this->entry(route('terms'), 'yearly', '0.3'),
        ];

        $count = 0;

        Package::query()
            ->active()
            ->orderBy('id')
            ->chunk(200, function ($packages) use (&$entries, &$count): void {
                foreach ($packages as $package) {
                    $entries[] = $this->entry(
                        route('packages.show', $package->slug),
                        'weekly',
                        '0.8',
                        $package->updated_at,
                    );

                    $count++;
                }
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .implode("\n", $entries)."\n"
            .'</urlset>'."\n";

        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info("Sitemap rebuilt — {$count} active packages.");

        return self::SUCCESS;
    }

    /**
     * Render a single <url> entry.
     */
    private function entry(string $location, string $changeFrequency, string $priority, ?CarbonInterface $lastModified = null): string
    {
        $xml = '    <url>'."\n".'        <loc>'.e($location).'</loc>'."\n";

        if ($lastModified !== null) {
            $xml .= '        <lastmod>'.$lastModified->toAtomString().'</lastmod>'."\n";
        }

        return $xml
            .'        <changefreq>'.$changeFrequency.'</changefreq>'."\n"
            .'        <priority>'.$priority.'</priority>'."\n"
            .'    </url>';
    }
}
