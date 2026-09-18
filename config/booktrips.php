<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Commission
    |--------------------------------------------------------------------------
    |
    | Share of a finished booking (pay-at-destination) that BookTrips invoices
    | the partner for, monthly.
    |
    */

    'commission_rate' => 0.10,

    /*
    |--------------------------------------------------------------------------
    | Booking rules
    |--------------------------------------------------------------------------
    */

    // Hours before an unanswered booking request is escalated to admins.
    'escalation_hours' => 24,

    // Block new bookings when the package already has that many guests booked
    // across overlapping dates.
    'capacity_guard' => env('BOOKTRIPS_CAPACITY_GUARD', true),

    /*
    |--------------------------------------------------------------------------
    | Disputes (reports between travellers and partners)
    |--------------------------------------------------------------------------
    |
    | How long the reported party has to give their side before a super admin
    | may hand down a verdict.
    |
    */

    'disputes' => [
        'response_hours' => (int) env('BOOKTRIPS_DISPUTE_RESPONSE_HOURS', 48),
        'strikes_to_suspend' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bank account partners transfer the monthly commission to
    |--------------------------------------------------------------------------
    |
    | Replace these values with the real company account before going live.
    |
    */

    'bank' => [
        'bank_name' => env('BOOKTRIPS_BANK_NAME', 'Commercial Bank of Ceylon'),
        'account_name' => env('BOOKTRIPS_BANK_ACCOUNT_NAME', 'BookTrips Lanka (Pvt) Ltd'),
        'account_number' => env('BOOKTRIPS_BANK_ACCOUNT_NUMBER', '8012-4573-9201'),
        'branch' => env('BOOKTRIPS_BANK_BRANCH', 'Colombo 03'),
        'swift' => env('BOOKTRIPS_BANK_SWIFT', 'CCEYLKLX'),
        'note' => 'Use your invoice number as the transfer reference.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalogue
    |--------------------------------------------------------------------------
    */

    'categories' => [
        ['slug' => 'camping', 'name' => 'Camping', 'blurb' => 'Tents, glamping and starlit nights', 'icon' => 'tent'],
        ['slug' => 'dayout', 'name' => 'Day out', 'blurb' => 'One-day escapes from the city', 'icon' => 'sun'],
        ['slug' => 'activities', 'name' => 'Activities', 'blurb' => 'Surf, raft, climb and play', 'icon' => 'compass'],
        ['slug' => 'hotels', 'name' => 'Hotels', 'blurb' => 'Stay packages with extras', 'icon' => 'hotel'],
        ['slug' => 'villas', 'name' => 'Villas', 'blurb' => 'Private houses for groups', 'icon' => 'home'],
        ['slug' => 'hiking', 'name' => 'Hiking', 'blurb' => 'Peaks, plains and forest trails', 'icon' => 'mountain'],
        ['slug' => 'beach', 'name' => 'Beach', 'blurb' => 'Coast stays and sea days', 'icon' => 'waves'],
        ['slug' => 'wildlife', 'name' => 'Wildlife', 'blurb' => 'Safaris and rainforest walks', 'icon' => 'binoculars'],
        ['slug' => 'adventure', 'name' => 'Adventure', 'blurb' => 'Rafting, canyoning and more', 'icon' => 'zap'],
        ['slug' => 'cultural', 'name' => 'Cultural', 'blurb' => 'Heritage cities and temples', 'icon' => 'landmark'],
        ['slug' => 'watersports', 'name' => 'Water sports', 'blurb' => 'Boats, boards and rivers', 'icon' => 'sailboat'],
        ['slug' => 'wellness', 'name' => 'Wellness', 'blurb' => 'Ayurveda, yoga and slow stays', 'icon' => 'flower'],
    ],

    'destinations' => [
        ['name' => 'Ella', 'district' => 'Badulla', 'region' => 'Hill country'],
        ['name' => 'Nuwara Eliya', 'district' => 'Nuwara Eliya', 'region' => 'Hill country'],
        ['name' => 'Kandy', 'district' => 'Kandy', 'region' => 'Hill country'],
        ['name' => 'Sigiriya', 'district' => 'Matale', 'region' => 'Cultural triangle'],
        ['name' => 'Dambulla', 'district' => 'Matale', 'region' => 'Cultural triangle'],
        ['name' => 'Anuradhapura', 'district' => 'Anuradhapura', 'region' => 'Cultural triangle'],
        ['name' => 'Polonnaruwa', 'district' => 'Polonnaruwa', 'region' => 'Cultural triangle'],
        ['name' => 'Galle', 'district' => 'Galle', 'region' => 'South coast'],
        ['name' => 'Mirissa', 'district' => 'Matara', 'region' => 'South coast'],
        ['name' => 'Unawatuna', 'district' => 'Galle', 'region' => 'South coast'],
        ['name' => 'Weligama', 'district' => 'Matara', 'region' => 'South coast'],
        ['name' => 'Tangalle', 'district' => 'Hambantota', 'region' => 'South coast'],
        ['name' => 'Yala', 'district' => 'Hambantota', 'region' => 'Dry zone'],
        ['name' => 'Udawalawe', 'district' => 'Ratnapura', 'region' => 'Dry zone'],
        ['name' => 'Horton Plains', 'district' => 'Nuwara Eliya', 'region' => 'Hill country'],
        ['name' => 'Haputale', 'district' => 'Badulla', 'region' => 'Hill country'],
        ['name' => 'Kitulgala', 'district' => 'Kegalle', 'region' => 'Wet zone'],
        ['name' => 'Bentota', 'district' => 'Galle', 'region' => 'South-west'],
        ['name' => 'Arugam Bay', 'district' => 'Ampara', 'region' => 'East coast'],
        ['name' => 'Trincomalee', 'district' => 'Trincomalee', 'region' => 'East coast'],
        ['name' => 'Pasikudah', 'district' => 'Batticaloa', 'region' => 'East coast'],
        ['name' => 'Kalpitiya', 'district' => 'Puttalam', 'region' => 'North-west'],
        ['name' => 'Sinharaja', 'district' => 'Ratnapura', 'region' => 'Wet zone'],
        ['name' => 'Knuckles', 'district' => 'Kandy', 'region' => 'Hill country'],
        ['name' => "Adam's Peak", 'district' => 'Ratnapura', 'region' => 'Hill country'],
        ['name' => 'Colombo', 'district' => 'Colombo', 'region' => 'West'],
        ['name' => 'Negombo', 'district' => 'Gampaha', 'region' => 'West'],
        ['name' => 'Jaffna', 'district' => 'Jaffna', 'region' => 'North'],
    ],

    'category_defaults' => [
        'camping' => [
            'included' => ['Tent or shelter', 'Camp dinner', 'Breakfast', 'Drinking water'],
            'excluded' => ['Transport to site', 'Sleeping bag', 'Alcohol'],
        ],
        'dayout' => [
            'included' => ['Guide', 'Lunch', 'Drinking water'],
            'excluded' => ['Entrance tickets', 'Hotel pickup', 'Personal expenses'],
        ],
        'activities' => [
            'included' => ['Instructor or guide', 'Safety gear', 'Drinking water'],
            'excluded' => ['Photos', 'Transport', 'Meals'],
        ],
        'hotels' => [
            'included' => ['Room', 'Breakfast', 'Wi-Fi'],
            'excluded' => ['Lunch and dinner', 'Spa treatments', 'Airport transfer'],
        ],
        'villas' => [
            'included' => ['Entire house', 'Housekeeping', 'Wi-Fi'],
            'excluded' => ['Meals', 'Driver', 'Extra beds'],
        ],
        'hiking' => [
            'included' => ['Guide', 'Trail briefing', 'Drinking water'],
            'excluded' => ['Park tickets', 'Transport', 'Meals'],
        ],
        'beach' => [
            'included' => ['Beach access', 'Basic gear', 'Drinking water'],
            'excluded' => ['Meals', 'Transport', 'Lessons unless listed'],
        ],
        'wildlife' => [
            'included' => ['Jeep or walk', 'Tracker', 'Drinking water'],
            'excluded' => ['Park tickets', 'Lunch', 'Private vehicle'],
        ],
        'adventure' => [
            'included' => ['Safety gear', 'Instructor', 'Lunch'],
            'excluded' => ['Photos', 'Transport from Colombo', 'Insurance'],
        ],
        'cultural' => [
            'included' => ['Guide', 'Site orientation'],
            'excluded' => ['Temple tickets', 'Meals', 'Offerings'],
        ],
        'watersports' => [
            'included' => ['Life jacket', 'Instructor or skipper', 'Drinking water'],
            'excluded' => ['Hotel pickup', 'Extra loops', 'Photos'],
        ],
        'wellness' => [
            'included' => ['Consultation', 'Listed treatments', 'Meals if stated'],
            'excluded' => ['Alcohol', 'Extra therapies', 'Transport'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File storage
    |--------------------------------------------------------------------------
    |
    | Partner photos and payment receipts move to S3 automatically once the
    | AWS_BUCKET environment variable is set. Locally (and in tests) they stay
    | on the public/local disks.
    |
    */

    'storage' => [
        'images_disk' => env('BOOKTRIPS_IMAGES_DISK', env('AWS_BUCKET') ? 's3' : 'public'),
        'receipts_disk' => env('BOOKTRIPS_RECEIPTS_DISK', env('AWS_BUCKET') ? 's3' : 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Package uploads
    |--------------------------------------------------------------------------
    */

    'uploads' => [
        // Photos a partner may attach to one package.
        'max_images' => 25,
        'max_image_kb' => 6144,
        'max_receipt_kb' => 8192,

        // Partner photos are branded with a translucent watermark after upload.
        'watermark' => [
            'enabled' => env('BOOKTRIPS_WATERMARK', true),
            'text' => env('BOOKTRIPS_WATERMARK_TEXT', 'Booktrips.lk'),
            // Alpha out of 127 — higher means fainter (about 20% opacity).
            'alpha' => (int) env('BOOKTRIPS_WATERMARK_ALPHA', 100),
            // A TrueType font keeps the text crisp. The first readable path wins,
            // otherwise the built-in GD font is scaled up instead.
            'font' => env('BOOKTRIPS_WATERMARK_FONT'),
            'font_candidates' => [
                'C:/Windows/Fonts/arialbd.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Outbound HTTP
    |--------------------------------------------------------------------------
    |
    | Point this at a CA bundle (cacert.pem) when the host running PHP has no
    | curl.cainfo configured — common on bare Windows installs. TLS verification
    | always stays on; this only tells cURL which root certificates to trust.
    |
    */

    'http' => [
        'ca_bundle' => env('BOOKTRIPS_CA_BUNDLE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS (Text.lk) — partner phone verification
    |--------------------------------------------------------------------------
    */

    'sms' => [
        'base_url' => env('TEXTLK_BASE_URL', 'https://app.text.lk/api/v3'),
        'api_key' => env('TEXTLK_API_KEY'),
        // Text.lk allows alphanumeric sender ids up to 11 characters.
        'sender_id' => env('TEXTLK_SENDER_ID', 'BookTrips'),

        'otp' => [
            'length' => 6,
            'ttl_minutes' => 10,
            'max_attempts' => 5,
            'resend_cooldown_seconds' => 60,
            'max_per_hour' => 5,
            'session_minutes' => 30,
            'message' => 'Your BookTrips.lk verification code is :code. It expires in :minutes minutes.',
        ],
    ],

];
