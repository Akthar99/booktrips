<?php

namespace Database\Seeders;

use App\Enums\BusinessType;
use App\Enums\DiscountType;
use App\Enums\PriceType;
use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    /**
     * Seed the Sri Lankan catalogue carried over from the prototype.
     *
     * Placeholder partner accounts are created inactive and unverified, with
     *
     * @booktrips.invalid addresses so no mail can ever reach them. Real partners
     * apply through /partners/apply and can be given the catalogue later.
     */
    public function run(): void
    {
        if (app()->isProduction() && ! $this->command->option('force')) {
            $this->command->warn('CatalogSeeder is skipped in production. Run with --force if you really want demo data.');

            return;
        }

        foreach ($this->businesses() as $key => $definition) {
            $owner = User::query()->firstOrCreate(
                ['email' => "{$key}@seed.booktrips.invalid"],
                [
                    'name' => $definition['owner'],
                    'phone' => null,
                    'password' => Str::random(40),
                    'role' => UserRole::Business,
                    'active' => false,
                    'email_verified_at' => null,
                ],
            );

            $business = Business::query()->firstOrCreate(
                ['user_id' => $owner->id],
                [
                    'name' => $definition['name'],
                    'type' => $definition['type'],
                    'description' => $definition['description'],
                    'address' => $definition['address'],
                    'city' => $definition['city'],
                    'district' => $definition['district'],
                    'phone' => null,
                    'email' => null,
                    'website' => null,
                    'cover_image' => $definition['cover_image'],
                    'approved' => true,
                ],
            );

            foreach ($this->packages()[$key] ?? [] as $package) {
                Package::query()->firstOrCreate(
                    ['slug' => $package['slug']],
                    [...$package, 'business_id' => $business->id],
                );
            }
        }

        $this->command->info('Catalogue seeded: '.Business::query()->count().' partners, '.Package::query()->count().' packages.');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function businesses(): array
    {
        return [
            'wildroots' => [
                'owner' => 'Tharindu Jayasuriya',
                'name' => 'Wild Roots Camping Co.',
                'type' => BusinessType::CampingSite,
                'description' => 'Hill-country camps with proper tents, camp meals and local guides.',
                'address' => 'Uva Glen, Ella',
                'city' => 'Ella',
                'district' => 'Badulla',
                'cover_image' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=1400&q=80',
            ],
            'islandtrails' => [
                'owner' => 'Ishara Fernando',
                'name' => 'Island Trails',
                'type' => BusinessType::TourOperator,
                'description' => 'Day outs and multi-day plans from Colombo, Kandy and the south.',
                'address' => 'Marine Drive, Colombo 03',
                'city' => 'Colombo',
                'district' => 'Colombo',
                'cover_image' => 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=1400&q=80',
            ],
            'yalagate' => [
                'owner' => 'Ruwani Silva',
                'name' => 'Yala Gate Lodges',
                'type' => BusinessType::Hotel,
                'description' => 'Safari-gate hotel with jeep partners and half-board packages.',
                'address' => 'Kirinda Road, Tissamaharama',
                'city' => 'Tissamaharama',
                'district' => 'Hambantota',
                'cover_image' => 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=1400&q=80',
            ],
            'southcoast' => [
                'owner' => 'Dinesh Wickramasinghe',
                'name' => 'South Coast Villas',
                'type' => BusinessType::Villa,
                'description' => 'Private beach villas in Mirissa, Weligama and Tangalle.',
                'address' => 'Mirissa Beach Road',
                'city' => 'Mirissa',
                'district' => 'Matara',
                'cover_image' => 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=1400&q=80',
            ],
            'peakpaddle' => [
                'owner' => 'Kasun Bandara',
                'name' => 'Peak & Paddle Adventures',
                'type' => BusinessType::ActivityProvider,
                'description' => 'Rafting, canyoning and forest hikes out of Kitulgala.',
                'address' => 'Kelani River Road, Kitulgala',
                'city' => 'Kitulgala',
                'district' => 'Kegalle',
                'cover_image' => 'https://images.unsplash.com/photo-1530866495561-507c9faab2ed?w=1400&q=80',
            ],
            'hillcountry' => [
                'owner' => 'Anjali Ratnayake',
                'name' => 'Hill Country Stays',
                'type' => BusinessType::Hotel,
                'description' => 'Tea-country hotels with plantation walks and fireplace rooms.',
                'address' => 'Upper Lake Road, Nuwara Eliya',
                'city' => 'Nuwara Eliya',
                'district' => 'Nuwara Eliya',
                'cover_image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1400&q=80',
            ],
            'oceanfold' => [
                'owner' => 'Maya Gunasekara',
                'name' => 'Ocean Fold Villas',
                'type' => BusinessType::Villa,
                'description' => 'Clifftop and lagoon villas for families and small groups.',
                'address' => 'Goyambokka, Tangalle',
                'city' => 'Tangalle',
                'district' => 'Hambantota',
                'cover_image' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1400&q=80',
            ],
            'heritagewalks' => [
                'owner' => 'Sanjaya Weerasinghe',
                'name' => 'Heritage Walks LK',
                'type' => BusinessType::TourOperator,
                'description' => 'Temple evenings, village lunches and cultural triangle days.',
                'address' => 'Rajapihilla Mawatha, Kandy',
                'city' => 'Kandy',
                'district' => 'Kandy',
                'cover_image' => 'https://images.unsplash.com/photo-1548013146-72479768bada?w=1400&q=80',
            ],
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function packages(): array
    {
        return [
            'wildroots' => [
                $this->package([
                    'title' => 'Ella Gap camping night',
                    'slug' => 'ella-gap-camping-night',
                    'category' => 'camping',
                    'highlight' => 'Camp on a ridge above Ella with a guided Little Adam\'s Peak sunrise.',
                    'description' => 'A one-night camp looking over Ella Gap. Tents are already pitched, dinner is cooked over the fire, and a guide walks you to Little Adam\'s Peak for sunrise. Built for Colombo crews who want a real outdoor night without buying gear.',
                    'location' => 'Ella',
                    'district' => 'Badulla',
                    'duration_days' => 2,
                    'duration_nights' => 1,
                    'price_lkr' => 12500,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 12,
                    'featured' => true,
                    'images' => $this->images('1504280390367-361c6d9f38f4', '1478131143131-4f7aa68cbfd9', '1537905569824-f89f14cceb68'),
                    'included' => ['Dome tent & sleeping mat', 'Camp dinner and breakfast', 'Sunrise hike guide', 'Tea and snacks'],
                    'excluded' => ['Transport to Ella', 'Sleeping bag (rentable)', 'Alcohol'],
                    'amenities' => ['Shared toilets', 'Campfire', 'Mountain view', 'Guide'],
                    'meeting_point' => 'Ella town clock tower, 2:30 pm',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Pitch, viewpoints, fire dinner', 'description' => 'Meet in town, jeep to camp, short walk to a viewpoint, then rice & curry by the fire.'],
                        ['day' => 2, 'title' => "Little Adam's Peak sunrise", 'description' => 'Pre-dawn walk, tea at the top, breakfast back at camp, down by 9:30 am.'],
                    ],
                ]),
                $this->package([
                    'title' => 'Knuckles mini World\'s End camp',
                    'slug' => 'knuckles-mini-worlds-end-camp',
                    'category' => 'camping',
                    'highlight' => 'Two nights in the Knuckles with a ridge walk and stream bathe.',
                    'description' => 'A proper backpack-light camp: we carry tents to a clearing near Mini World\'s End, cook over fire, and walk to viewpoints. Fitness needed. Not for first-time campers who want glamping.',
                    'location' => 'Knuckles',
                    'district' => 'Kandy',
                    'duration_days' => 3,
                    'duration_nights' => 2,
                    'price_lkr' => 16500,
                    'price_type' => PriceType::PerPerson,
                    'min_guests' => 4,
                    'max_guests' => 10,
                    'featured' => true,
                    'images' => $this->images('1478131143131-4f7aa68cbfd9', '1487730117039-0d7e87d6e690', '1504851149312-7a075b496cc7'),
                    'included' => ['Tents', 'All meals', 'Guide and porter share', 'Permits help'],
                    'excluded' => ['Sleeping bag', 'Transport to Deanston'],
                    'amenities' => ['Wild camp', 'Stream water', 'Guide'],
                    'meeting_point' => 'Deanston checkpoint, 9:00 am day 1',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Walk in', 'description' => '3–4 hour walk to camp, swim, dinner.'],
                        ['day' => 2, 'title' => 'Ridge day', 'description' => 'Mini World\'s End and side trails.'],
                        ['day' => 3, 'title' => 'Walk out', 'description' => 'Breakfast, down by 1 pm.'],
                    ],
                ]),
                $this->package([
                    'title' => 'Ella train & Nine Arch day out',
                    'slug' => 'ella-train-nine-arch-day-out',
                    'category' => 'dayout',
                    'highlight' => 'Nine Arch viewpoint, a short walk, and a café lunch — no camping.',
                    'description' => 'For people who want Ella without sleeping in a tent. We time the viewpoint for a train crossing when the timetable behaves, walk to a café, and send you back to the bus or your hotel.',
                    'location' => 'Ella',
                    'district' => 'Badulla',
                    'price_lkr' => 3900,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 10,
                    'images' => $this->images('1566296314736-6eaac1ca0cb9', '1578662996442-48f60103fc96', '1500530855697-b816dceb13d4'),
                    'included' => ['Guide', 'Café lunch'],
                    'excluded' => ['Train tickets', 'Hotels'],
                    'amenities' => ['Easy walk'],
                    'meeting_point' => 'Ella clock tower, 8:00 am',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Bridge and lunch', 'description' => 'Walk 8:15, lunch 12:00, free time, done 2:30.'],
                    ],
                ]),
            ],
            'islandtrails' => [
                $this->package([
                    'title' => 'Sigiriya & village day out',
                    'slug' => 'sigiriya-village-day-out',
                    'category' => 'dayout',
                    'highlight' => 'Rock fortress, village lunch on a lotus leaf, back before night.',
                    'description' => 'A classic cultural triangle day from Colombo or Kandy: climb Sigiriya (tickets extra), walk a rural track, eat a village lunch, and stop at Dambulla cave temple if time allows. AC van, guide and lunch included.',
                    'location' => 'Sigiriya',
                    'district' => 'Matale',
                    'price_lkr' => 11500,
                    'price_type' => PriceType::PerPerson,
                    'min_guests' => 2,
                    'max_guests' => 10,
                    'featured' => true,
                    'images' => $this->images('1564501049412-61c2a3083791', '1548013146-72479768bada', '1524492412937-b28074a5d7c1'),
                    'included' => ['AC van', 'Guide', 'Village lunch', 'Water'],
                    'excluded' => ['Sigiriya & Dambulla tickets', 'Breakfast', 'Personal expenses'],
                    'amenities' => ['Hotel pickup (Colombo/Kandy)', 'Guide'],
                    'meeting_point' => 'Pickup from your Colombo or Kandy hotel, 5:30–6:00 am',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Rock, lunch, caves', 'description' => 'Dawn drive, climb, village lunch, optional Dambulla, return by 8 pm.'],
                    ],
                ]),
                $this->package([
                    'title' => 'Horton Plains World\'s End hike',
                    'slug' => 'horton-plains-worlds-end-hike',
                    'category' => 'hiking',
                    'highlight' => 'Cloud-forest loop to World\'s End and Baker\'s Falls before the mist.',
                    'description' => 'Leave Nuwara Eliya before dawn, walk the 9 km circuit with a mountain guide, tea after. Park tickets paid at the gate. Not a technical climb, but cold and wet — we bring ponchos.',
                    'location' => 'Horton Plains',
                    'district' => 'Nuwara Eliya',
                    'price_lkr' => 9500,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 8,
                    'images' => $this->images('1464822759023-fed622ff2c3b', '1551632811-561732d1e306', '1501555085422-4cf465c1edff'),
                    'included' => ['Pickup in Nuwara Eliya', 'Guide', 'Poncho', 'Flask tea'],
                    'excluded' => ['Park tickets', 'Breakfast (pack your own or hotel)'],
                    'amenities' => ['Small group', 'Early start'],
                    'meeting_point' => 'Grand Hotel roundabout, 4:45 am',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Circuit walk', 'description' => 'Gate open, World\'s End first, Baker\'s Falls, back to town by 11:30 am.'],
                    ],
                ]),
                $this->package([
                    'title' => 'Madu River & cinnamon day out',
                    'slug' => 'madu-river-cinnamon-day-out',
                    'category' => 'dayout',
                    'highlight' => 'Boat through mangroves, cinnamon island, and a fish-massage stop you can skip.',
                    'description' => 'Leave after breakfast from Balapitiya. A slow boat, a cinnamon demonstration, and lunch on the river. Family friendly. We do not push temple donations.',
                    'location' => 'Bentota',
                    'district' => 'Galle',
                    'price_lkr' => 4900,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 12,
                    'images' => $this->images('1544551763-46a013bb70d5', '1530053969600-caed2596d242', '1473496169904-658ba7c44d8a'),
                    'included' => ['Boat', 'Guide', 'Lunch'],
                    'excluded' => ['Hotel pickup', 'Fish therapy'],
                    'amenities' => ['Life jackets', 'Shaded boat'],
                    'meeting_point' => 'Balapitiya jetty, 9:30 am',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'River loop', 'description' => 'Boat 10–1 including lunch stop, done 2 pm.'],
                    ],
                ]),
            ],
            'yalagate' => [
                $this->package([
                    'title' => 'Yala leopard weekend',
                    'slug' => 'yala-leopard-weekend',
                    'category' => 'wildlife',
                    'highlight' => 'One night at the gate, two jeep safaris, half board.',
                    'description' => 'Check in at Yala Gate Lodges, evening jeep into Block 1, dinner, then a dawn safari. Rooms are AC, jeep is shared (max 6). Pay the park ticket at the gate — we handle the rest.',
                    'location' => 'Yala',
                    'district' => 'Hambantota',
                    'duration_days' => 2,
                    'duration_nights' => 1,
                    'price_lkr' => 34500,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 6,
                    'featured' => true,
                    'images' => $this->images('1516426122078-c23e76319801', '1547471080-7cc2caa01a7e', '1484406566174-9da000314612'),
                    'included' => ['AC room', 'Dinner & breakfast', '2 shared jeep safaris', 'Water and snacks in jeep'],
                    'excluded' => ['Park tickets', 'Lunch', 'Private jeep (ask)'],
                    'amenities' => ['Pool', 'Restaurant', 'Parking', 'Safari desk'],
                    'meeting_point' => 'Yala Gate Lodges reception, from 1 pm',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Arrive and evening safari', 'description' => 'Check-in, tea, 3 pm jeep, dinner back at the lodge.'],
                        ['day' => 2, 'title' => 'Dawn safari', 'description' => '5:30 am departure, breakfast after, checkout 11 am.'],
                    ],
                ]),
                $this->package([
                    'title' => 'Udawalawe elephant safari day',
                    'slug' => 'udawalawe-elephant-safari-day',
                    'category' => 'wildlife',
                    'highlight' => 'Morning jeep, almost-guaranteed elephants, back for lunch.',
                    'description' => 'A half-day jeep in Udawalawe. Better elephant odds than Yala, fewer leopards. Shared jeep, water, and a stop at the elephant transit home viewpoint if open. Tickets at the gate.',
                    'location' => 'Udawalawe',
                    'district' => 'Ratnapura',
                    'price_lkr' => 7200,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 6,
                    'images' => $this->images('1549366021-9f761d450615', '1557050543-4d5f4e07ef46', '1564760055775-d63b17a55c44'),
                    'included' => ['Shared jeep', 'Tracker', 'Water'],
                    'excluded' => ['Park tickets', 'Lunch'],
                    'amenities' => ['Hotel pickup in Udawalawe/Embilipitiya'],
                    'meeting_point' => 'Udawalawe junction, 5:15 am',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Dawn jeep', 'description' => 'Gate 6:00, out 10:00, optional transit home.'],
                    ],
                ]),
            ],
            'southcoast' => [
                $this->package([
                    'title' => 'Mirissa beach villa, 2 nights',
                    'slug' => 'mirissa-beach-villa-2-nights',
                    'category' => 'villas',
                    'highlight' => 'Whole villa for your group, steps from Secret Beach.',
                    'description' => 'A three-bedroom villa with a small pool, cook on request, and a five-minute walk to the sand. Priced per night for the house — ideal for 4–8 friends or a family. Whale watching boats leave from the harbour next door.',
                    'location' => 'Mirissa',
                    'district' => 'Matara',
                    'duration_days' => 3,
                    'duration_nights' => 2,
                    'price_lkr' => 42000,
                    'price_type' => PriceType::PerNight,
                    'min_guests' => 2,
                    'max_guests' => 8,
                    'featured' => true,
                    'images' => $this->images('1499793983690-e29da59ef1c2', '1582719478250-c89cae4dc85b', '1520250497591-112f2f40a3f4'),
                    'included' => ['Entire villa', 'Daily housekeeping', 'Wi-Fi', 'Welcome king coconut'],
                    'excluded' => ['Meals', 'Whale trip', 'Airport transfer'],
                    'amenities' => ['Private pool', 'Kitchen', 'AC rooms', 'Parking', 'Beach walk'],
                    'meeting_point' => 'Host meets you at the gate — pin sent after booking',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Check-in from 2 pm', 'description' => 'Settle in, beach, sunset at Coconut Tree Hill.'],
                        ['day' => 2, 'title' => 'Your day', 'description' => 'Optional whale trip, surf at Weligama, or do nothing.'],
                        ['day' => 3, 'title' => 'Checkout 11 am', 'description' => 'Late checkout sometimes possible if the next group is after 3.'],
                    ],
                ]),
                $this->package([
                    'title' => 'Arugam Bay surf weekend',
                    'slug' => 'arugam-bay-surf-weekend',
                    'category' => 'beach',
                    'highlight' => 'Two nights, board hire and a lesson at Main Point.',
                    'description' => 'Stay in a simple beach guesthouse, get a foam or short board, and one 90-minute lesson. The rest of the time is yours — Whiskey Point at sunrise, coconut roti, lagoon sunset.',
                    'location' => 'Arugam Bay',
                    'district' => 'Ampara',
                    'duration_days' => 3,
                    'duration_nights' => 2,
                    'price_lkr' => 18500,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 6,
                    'images' => $this->images('1502680390469-be45c2d090e3', '1455264746236-934ac869832f', '1507525428034-b723cf961d3e'),
                    'included' => ['Fan room', 'Breakfast x2', 'Board hire 2 days', '1 surf lesson'],
                    'excluded' => ['Dinner', 'Transport from Colombo'],
                    'amenities' => ['Beachfront', 'Board rack', 'Outdoor shower'],
                    'meeting_point' => 'Main Point beach road, from 1 pm',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Arrive and evening surf', 'description' => 'Check-in, lesson or free surf depending on tide.'],
                        ['day' => 2, 'title' => 'Full surf day', 'description' => 'Dawn session, free afternoon.'],
                        ['day' => 3, 'title' => 'One more wave', 'description' => 'Morning surf, checkout 11 am.'],
                    ],
                ]),
            ],
            'peakpaddle' => [
                $this->package([
                    'title' => 'Kitulgala white water day out',
                    'slug' => 'kitulgala-white-water-day-out',
                    'category' => 'adventure',
                    'highlight' => 'Kelani river rafting plus a jungle lunch — the classic Saturday out of Colombo.',
                    'description' => 'A full day on the Kelani: safety brief, rafting grade 2–3 (seasonal), a swim stop and a local lunch. Helmets, life jackets and guides included. Good for mixed-ability groups.',
                    'location' => 'Kitulgala',
                    'district' => 'Kegalle',
                    'price_lkr' => 8900,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 16,
                    'featured' => true,
                    'images' => $this->images('1530866495561-507c9faab2ed', '1501555085422-4cf465c1edff', '1472745942893-4b9f730c766e'),
                    'included' => ['Raft, helmet, life jacket', 'River guide', 'Lunch', 'River transport'],
                    'excluded' => ['Pickup from Colombo (add-on)', 'Photos'],
                    'amenities' => ['Changing rooms', 'Lockers', 'Certified guides'],
                    'meeting_point' => 'Peak & Paddle base, Kitulgala, 8:30 am',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Raft, swim, lunch', 'description' => 'Briefing at 8:45, on the water by 9:30, lunch at 12:30, optional short waterfall walk, finish 3 pm.'],
                    ],
                ]),
                $this->package([
                    'title' => 'Sinharaja rainforest walk',
                    'slug' => 'sinharaja-rainforest-walk',
                    'category' => 'hiking',
                    'highlight' => 'Half-day birding walk with a village tracker in the buffer zone.',
                    'description' => 'Meet at Kudawa, walk a moderate trail with a local tracker who knows blue magpie calls. Leeches after rain — we supply socks. Tickets at the gate. Finish with koththu or rice at a village kadé.',
                    'location' => 'Sinharaja',
                    'district' => 'Ratnapura',
                    'price_lkr' => 6500,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 8,
                    'images' => $this->images('1441974231531-c6227db76b6e', '1470071459604-3b5ec3a7fe05', '1511497584788-876760111969'),
                    'included' => ['Tracker', 'Leech socks', 'Village lunch'],
                    'excluded' => ['Park tickets', 'Transport from Colombo'],
                    'amenities' => ['Small group', 'Birding focus'],
                    'meeting_point' => 'Kudawa ticket office, 6:30 am',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Morning forest', 'description' => 'Walk 6:45–11:30, lunch, optional second short trail.'],
                    ],
                ]),
                $this->package([
                    'title' => 'Bentota water sports day',
                    'slug' => 'bentota-water-sports-day',
                    'category' => 'watersports',
                    'highlight' => 'Jet ski, banana boat and a Madu river boat in one ticket.',
                    'description' => 'A south-west classic. Morning on Bentota river / beach for jet ski and banana boat, then a Madu River boat past cinnamon and island temples. Life jackets for everyone. Not a drinking cruise.',
                    'location' => 'Bentota',
                    'district' => 'Galle',
                    'price_lkr' => 14500,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 10,
                    'images' => $this->images('1544551763-46a013bb70d5', '1530053969600-caed2596d242', '1473496169904-658ba7c44d8a'),
                    'included' => ['Jet ski 15 min', 'Banana boat', 'Madu River boat 2 hrs', 'Lunch'],
                    'excluded' => ['Hotel pickup', 'Extra ski loops'],
                    'amenities' => ['Life jackets', 'Changing hut'],
                    'meeting_point' => 'Bentota river mouth, 9:00 am',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'River, lunch, Madu', 'description' => 'Sports 9–12, lunch, Madu 1:30–3:30.'],
                    ],
                ]),
            ],
            'hillcountry' => [
                $this->package([
                    'title' => 'Galle Fort boutique night',
                    'slug' => 'galle-fort-boutique-night',
                    'category' => 'hotels',
                    'highlight' => 'One night inside the ramparts with breakfast on the wall.',
                    'description' => 'A 6-room boutique hotel in Galle Fort. Package includes breakfast, a host-led sunset rampart walk, and late checkout on Sundays. Rooms are small, characterful, and a two-minute walk from the lighthouse.',
                    'location' => 'Galle',
                    'district' => 'Galle',
                    'duration_days' => 2,
                    'duration_nights' => 1,
                    'price_lkr' => 28000,
                    'price_type' => PriceType::PerNight,
                    'max_guests' => 2,
                    'featured' => true,
                    'discount_type' => DiscountType::Percentage,
                    'discount_value' => 15,
                    'discount_enabled' => true,
                    'discount_start' => '2026-09-01',
                    'discount_end' => '2026-10-31',
                    'images' => $this->images('1551882547-ff40c63fe5fa', '1571896349842-33c89424de2d', '1566073771259-6a8506099945'),
                    'included' => ['Boutique room', 'Breakfast', 'Rampart sunset walk'],
                    'excluded' => ['Lunch and dinner', 'Parking (outside fort)'],
                    'amenities' => ['AC', 'Wi-Fi', 'Heritage building', 'Walkable fort'],
                    'meeting_point' => 'Pedlar Street — pin after booking',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Fort evening', 'description' => 'Check-in 2 pm, walk the walls at 5:30, dinner on your own.'],
                        ['day' => 2, 'title' => 'Slow morning', 'description' => 'Breakfast, lanes, checkout 12 pm.'],
                    ],
                ]),
                $this->package([
                    'title' => 'Nuwara Eliya tea country day out',
                    'slug' => 'nuwara-eliya-tea-country-day-out',
                    'category' => 'dayout',
                    'highlight' => 'Factory tour, pluck-your-own leaf, and a bungalow lunch.',
                    'description' => 'A gentle hill-country day: Damro or Pedro factory, a short plucking walk, bungalow rice & curry, then Gregory Lake or a greenhouse stop. Suits parents, office groups and anyone who does not want a hard hike.',
                    'location' => 'Nuwara Eliya',
                    'district' => 'Nuwara Eliya',
                    'price_lkr' => 7900,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 14,
                    'images' => $this->images('1593693397690-362cb3027654', '1563911302283-d2bc129e7560', '1506905925346-21bda4d32df4'),
                    'included' => ['Factory tour', 'Bungalow lunch', 'Tea tasting', 'Van in town'],
                    'excluded' => ['Pickup from Colombo', 'Lake boat'],
                    'amenities' => ['Indoor lunch', 'Easy walking'],
                    'meeting_point' => 'Nuwara Eliya bus stand, 8:30 am',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Tea, lunch, lake', 'description' => 'Factory 9:00, lunch 12:30, lake 3:00, finish 5:00.'],
                    ],
                ]),
                $this->package([
                    'title' => 'Beruwala Ayurveda reset, 3 nights',
                    'slug' => 'beruwala-ayurveda-reset',
                    'category' => 'wellness',
                    'highlight' => 'Doctor consult, two treatments a day, vegetarian meals.',
                    'description' => 'A short Ayurveda stay on the south-west coast. Not a party hotel. Doctor on day one sets oil treatments, herbal steam and a simple diet. Good after a long work stretch in Colombo.',
                    'location' => 'Bentota',
                    'district' => 'Galle',
                    'duration_days' => 4,
                    'duration_nights' => 3,
                    'price_lkr' => 39000,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 2,
                    'images' => $this->images('1544161515-4ab6ce6db874', '1600334129128-685c5582fd35', '1540555700478-4be289fbecef'),
                    'included' => ['Garden room', 'All vegetarian meals', 'Doctor consult', '2 treatments / day'],
                    'excluded' => ['Alcohol (not served)', 'Extra therapies'],
                    'amenities' => ['Treatment rooms', 'Yoga shala', 'Beach access'],
                    'meeting_point' => 'Beruwala / Bentota clinic — pin after booking',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Arrive and consult', 'description' => 'Check-in 1 pm, doctor, first oil treatment.'],
                        ['day' => 2, 'title' => 'Treatments', 'description' => 'Morning and evening sessions, rest, light walks.'],
                        ['day' => 3, 'title' => 'Treatments', 'description' => 'Same rhythm.'],
                        ['day' => 4, 'title' => 'Checkout', 'description' => 'Breakfast, leave by 11 am.'],
                    ],
                ]),
                $this->package([
                    'title' => 'Nuwara Eliya fireplace hotel, 1 night',
                    'slug' => 'nuwara-eliya-fireplace-hotel',
                    'category' => 'hotels',
                    'highlight' => 'Colonial-style room, breakfast, and a lake-loop walk map.',
                    'description' => 'A warm room when Colombo is too hot. Package is room + breakfast for two. Ask at desk for Horton Plains transfers — sold as a separate BookTrips activity.',
                    'location' => 'Nuwara Eliya',
                    'district' => 'Nuwara Eliya',
                    'duration_days' => 2,
                    'duration_nights' => 1,
                    'price_lkr' => 22000,
                    'price_type' => PriceType::PerNight,
                    'max_guests' => 3,
                    'images' => $this->images('1566073771259-6a8506099945', '1520250497591-112f2f40a3f4', '1590490360182-c33d57733427'),
                    'included' => ['Room', 'Breakfast', 'Wi-Fi'],
                    'excluded' => ['Heater surcharge if any', 'Lunch'],
                    'amenities' => ['Restaurant', 'Parking', 'Hill view'],
                    'meeting_point' => 'Upper Lake Road reception',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Check-in 2 pm', 'description' => 'Lake or Pedro estate.'],
                        ['day' => 2, 'title' => 'Breakfast, checkout 11', 'description' => 'Optional Horton Plains add-on.'],
                    ],
                ]),
            ],
            'oceanfold' => [
                $this->package([
                    'title' => 'Tangalle ocean villa escape',
                    'slug' => 'tangalle-ocean-villa-escape',
                    'category' => 'villas',
                    'highlight' => 'Clifftop four-bedroom villa, cook included, two-night minimum.',
                    'description' => 'A house on the Goyambokka cliff with a pool that looks at the Indian Ocean. Private cook for breakfast and dinner, housekeeper, and a driver on request. Built for families and friend groups who want space, not a hotel corridor.',
                    'location' => 'Tangalle',
                    'district' => 'Hambantota',
                    'duration_days' => 3,
                    'duration_nights' => 2,
                    'price_lkr' => 65000,
                    'price_type' => PriceType::PerNight,
                    'min_guests' => 4,
                    'max_guests' => 10,
                    'featured' => true,
                    'images' => $this->images('1499793983690-e29da59ef1c2', '1512917774080-9991f1c4c750', '1613490493576-7fde63acd811'),
                    'included' => ['Entire villa', 'Cook (breakfast & dinner ingredients extra)', 'Housekeeping', 'Wi-Fi'],
                    'excluded' => ['Food shopping', 'Driver'],
                    'amenities' => ['Infinity-ish pool', 'Ocean view', 'AC bedrooms', 'Parking'],
                    'meeting_point' => 'Goyambokka — pin after booking',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Arrive after 2 pm', 'description' => 'Swim, sunset, dinner at home.'],
                        ['day' => 2, 'title' => 'Beach or Hiriketiya', 'description' => 'Optional day trip 40 minutes west.'],
                        ['day' => 3, 'title' => 'Checkout 11 am', 'description' => 'Breakfast included.'],
                    ],
                ]),
            ],
            'heritagewalks' => [
                $this->package([
                    'title' => 'Kandy temple & cultural evening',
                    'slug' => 'kandy-temple-cultural-evening',
                    'category' => 'cultural',
                    'highlight' => 'Temple of the Tooth, lakeside walk, and a Kandyan dance show.',
                    'description' => 'A compact evening plan if you already live in or arrived in Kandy. Skip-the-guesswork tickets for the dance, a calm temple visit, and a guide who actually explains the relic chamber instead of rushing.',
                    'location' => 'Kandy',
                    'district' => 'Kandy',
                    'price_lkr' => 5500,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 12,
                    'images' => $this->images('1548013146-72479768bada', '1524492412937-b28074a5d7c1', '1582719508461-905c673771fd'),
                    'included' => ['Guide', 'Dance show ticket', 'Temple orientation'],
                    'excluded' => ['Temple ticket', 'Dinner'],
                    'amenities' => ['Evening timing', 'Hotel pickup in Kandy'],
                    'meeting_point' => 'Queens Hotel steps, 3:30 pm',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Temple then dance', 'description' => 'Temple 4 pm, lake 6 pm, show 7:30, done 9 pm.'],
                    ],
                ]),
                $this->package([
                    'title' => 'Adam\'s Peak sunrise climb',
                    'slug' => 'adams-peak-sunrise-climb',
                    'category' => 'hiking',
                    'highlight' => 'Guided night climb on the Nallathanniya stairs, season only.',
                    'description' => 'Meet at 1:30 am, climb with a guide who knows the rest points, stand for sunrise, descend after tea. Not a trek through jungle — it is stairs, cold wind, and a crowd in season. We bring headlamps if you forget yours.',
                    'location' => 'Adam\'s Peak',
                    'district' => 'Ratnapura',
                    'price_lkr' => 4500,
                    'price_type' => PriceType::PerPerson,
                    'max_guests' => 15,
                    'images' => $this->images('1464822759023-fed622ff2c3b', '1483728642387-6c3bdd6c93e5', '1418065460487-3e41a6c84dc5'),
                    'included' => ['Guide', 'Spare headlamp', 'Hot tea at the top'],
                    'excluded' => ['Transport', 'Poncho'],
                    'amenities' => ['Night start', 'Group'],
                    'meeting_point' => 'Nallathanniya bus stop, 1:15 am',
                    'itinerary' => [
                        ['day' => 1, 'title' => 'Night ascent', 'description' => 'Climb 2–5 am, sunrise, down by 9:30 am.'],
                    ],
                ]),
            ],
        ];
    }

    /**
     * Defaults shared by every catalogue package.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function package(array $overrides): array
    {
        $coords = $this->coords()[$overrides['location']] ?? [7.8731, 80.7718];

        return [
            'category' => 'dayout',
            'highlight' => '',
            'address' => $overrides['location'],
            'lat' => $coords[0],
            'lng' => $coords[1],
            'schedule_type' => 'always',
            'schedule_start' => null,
            'schedule_end' => null,
            'weekdays' => [0, 1, 2, 3, 4, 5, 6],
            'duration_days' => 1,
            'duration_nights' => 0,
            'price_type' => PriceType::PerPackage,
            'discount_type' => DiscountType::None,
            'discount_value' => 0,
            'discount_enabled' => false,
            'discount_start' => null,
            'discount_end' => null,
            'min_guests' => 1,
            'max_guests' => 8,
            'included' => [],
            'excluded' => [],
            'itinerary' => [],
            'amenities' => [],
            'images' => [],
            'meeting_point' => null,
            'cancellation_policy' => 'Free cancellation up to 48 hours before start. After that, 50% is due at destination if you no-show.',
            'rating' => 0,
            'review_count' => 0,
            'featured' => false,
            'active' => true,
            ...$overrides,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function images(string ...$ids): array
    {
        $urls = [];

        foreach ($ids as $id) {
            $urls[] = "https://images.unsplash.com/photo-{$id}?w=1400&q=80";
        }

        return $urls;
    }

    /**
     * @return array<string, array{float, float}>
     */
    private function coords(): array
    {
        return [
            'Ella' => [6.8667, 81.0466],
            'Kitulgala' => [6.989, 80.411],
            'Yala' => [6.372, 81.518],
            'Mirissa' => [5.9483, 80.459],
            'Sigiriya' => [7.957, 80.760],
            'Horton Plains' => [6.802, 80.807],
            'Galle' => [6.032, 80.217],
            'Arugam Bay' => [6.8406, 81.836],
            'Nuwara Eliya' => [6.9497, 80.7891],
            'Sinharaja' => [6.406, 80.501],
            'Bentota' => [6.4215, 80.001],
            'Knuckles' => [7.455, 80.791],
            'Kandy' => [7.2906, 80.6337],
            'Tangalle' => [6.024, 80.791],
            'Udawalawe' => [6.438, 80.888],
            "Adam's Peak" => [6.8096, 80.4994],
        ];
    }
}
