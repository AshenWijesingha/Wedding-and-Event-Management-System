<?php

namespace Database\Seeders\Data;

use Illuminate\Support\Str;

/**
 * Real leading Sri Lankan hotels and their reception halls, used as the single
 * source of truth for venue + package seeding (see DemoDataSeeder /
 * ShowcaseSeeder).
 *
 * Hotel names, cities and well-known hall names are real. Capacities and prices
 * are INDICATIVE, representative figures for demonstration — not official quotes
 * from the properties. Services reflect facilities these venues genuinely offer.
 *
 * All prices are in LKR (the primary tenant is configured for Rs / LKR).
 */
class SriLankaHotels
{
    /**
     * Flat list of reception halls, one per Venue record (~24).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function halls(): array
    {
        $halls = [];

        foreach (self::hotels() as $hotel) {
            $banner = '/images/venues/' . $hotel['slug'] . '.svg';

            foreach ($hotel['halls'] as $hall) {
                $name = $hotel['name'] . ' — ' . $hall['hall'];

                $halls[] = [
                    'name' => $name,
                    'slug' => $hotel['slug'] . '-' . Str::slug($hall['hall']),
                    'description' => $hall['description'] . ' Located at ' . $hotel['name'] . ', ' . $hotel['city'] . '.',
                    'capacity_min' => $hall['capacity_min'],
                    'capacity_max' => $hall['capacity_max'],
                    'base_price' => $hall['base_price'],
                    'weekend_surcharge' => $hall['weekend_surcharge'],
                    'amenities' => array_values(array_unique(array_merge($hotel['services'], $hall['services'] ?? []))),
                    'images' => [$banner],
                    'status' => 'active',
                ];
            }
        }

        return $halls;
    }

    /**
     * Reception service packages, linked to halls via the venue_packages pivot.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function packages(): array
    {
        return [
            [
                'name' => 'Poruwa Wedding — Silver',
                'slug' => 'poruwa-wedding-silver',
                'description' => 'Traditional Sri Lankan Poruwa ceremony with essential reception services for an elegant celebration.',
                'base_price' => 850000,
                'min_guests' => 100,
                'max_guests' => 300,
                'guest_pricing' => [['from' => 100, 'to' => 300, 'price_per_guest' => 6500]],
                'included_services' => ['Decorated Poruwa', 'Welcome drinks', 'Buffet dinner', 'Wedding cake', 'Sound system', 'Wedding coordinator'],
                'status' => 'active',
            ],
            [
                'name' => 'Poruwa Wedding — Gold',
                'slug' => 'poruwa-wedding-gold',
                'description' => 'A grander Poruwa celebration with premium menus, floral styling and live entertainment.',
                'base_price' => 1450000,
                'min_guests' => 150,
                'max_guests' => 500,
                'guest_pricing' => [['from' => 150, 'to' => 500, 'price_per_guest' => 8500]],
                'included_services' => ['Decorated Poruwa & stage', 'Welcome cocktails', 'Premium buffet & live stations', 'Tiered wedding cake', 'Floral arrangements', 'Live band & DJ', 'Bridal suite', 'Wedding coordinator'],
                'status' => 'active',
            ],
            [
                'name' => 'Poruwa Wedding — Platinum',
                'slug' => 'poruwa-wedding-platinum',
                'description' => 'The flagship luxury wedding experience with bespoke styling, fine dining and full event production.',
                'base_price' => 2750000,
                'min_guests' => 250,
                'max_guests' => 1000,
                'guest_pricing' => [['from' => 250, 'to' => 1000, 'price_per_guest' => 12500]],
                'included_services' => ['Bespoke Poruwa & themed décor', 'Champagne reception', 'Fine-dining plated & buffet menus', 'Designer wedding cake', 'Premium floral & lighting design', 'Live band, DJ & dance floor', 'LED video walls', 'Honeymoon suite', 'Dedicated planning team', 'Valet parking'],
                'status' => 'active',
            ],
            [
                'name' => 'Nikah Reception',
                'slug' => 'nikah-reception',
                'description' => 'An elegant Nikah ceremony and reception with halal fine-dining and tasteful styling.',
                'base_price' => 1250000,
                'min_guests' => 150,
                'max_guests' => 600,
                'guest_pricing' => [['from' => 150, 'to' => 600, 'price_per_guest' => 7500]],
                'included_services' => ['Nikah seating & décor', 'Halal premium buffet', 'Welcome beverages', 'Floral styling', 'Sound system', 'Event coordinator'],
                'status' => 'active',
            ],
            [
                'name' => 'Homecoming Reception',
                'slug' => 'homecoming-reception',
                'description' => 'A refined homecoming dinner to celebrate the newlyweds with family and friends.',
                'base_price' => 950000,
                'min_guests' => 100,
                'max_guests' => 400,
                'guest_pricing' => [['from' => 100, 'to' => 400, 'price_per_guest' => 7000]],
                'included_services' => ['Stage & backdrop décor', 'Buffet dinner', 'Wedding cake', 'DJ & sound', 'Floral arrangements', 'Coordinator'],
                'status' => 'active',
            ],
            [
                'name' => 'Corporate Gala Dinner',
                'slug' => 'corporate-gala-dinner',
                'description' => 'A premium corporate gala package with full AV production for awards nights and conferences.',
                'base_price' => 1100000,
                'min_guests' => 50,
                'max_guests' => 800,
                'guest_pricing' => [['from' => 50, 'to' => 800, 'price_per_guest' => 6000]],
                'included_services' => ['Stage & podium', 'Full AV & LED screens', 'Buffet & beverage service', 'Registration desk', 'Branding & signage support', 'Event manager'],
                'status' => 'active',
            ],
        ];
    }

    /**
     * Source hotel data grouped by property. Services at hotel level apply to all
     * of its halls; per-hall services are merged on top.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function hotels(): array
    {
        $colomboServices = ['In-house catering', 'Customizable menus', 'AV & stage', 'Bridal suite', 'Valet parking', 'Dedicated wedding coordinator', 'Air conditioning', 'Wi-Fi'];

        return [
            [
                'name' => 'Cinnamon Grand Colombo',
                'slug' => 'cinnamon-grand-colombo',
                'city' => 'Colombo 03',
                'services' => $colomboServices,
                'halls' => [
                    ['hall' => 'Oak Room', 'description' => 'A timeless wood-panelled banquet room favoured for prestigious weddings and private dinners.', 'capacity_min' => 150, 'capacity_max' => 450, 'base_price' => 1650000, 'weekend_surcharge' => 350000, 'services' => ['Dance floor', 'LED video wall']],
                    ['hall' => "King's Court", 'description' => 'A grand pillared ballroom with classical detailing for large-scale receptions.', 'capacity_min' => 250, 'capacity_max' => 800, 'base_price' => 2200000, 'weekend_surcharge' => 450000, 'services' => ['Dance floor', 'Stage lighting']],
                    ['hall' => 'Grand Ballroom', 'description' => 'The hotel’s flagship ballroom for opulent weddings and corporate galas.', 'capacity_min' => 300, 'capacity_max' => 1000, 'base_price' => 2500000, 'weekend_surcharge' => 500000, 'services' => ['LED video wall', 'Dance floor']],
                ],
            ],
            [
                'name' => 'Shangri-La Colombo',
                'slug' => 'shangri-la-colombo',
                'city' => 'Colombo 02',
                'services' => $colomboServices,
                'halls' => [
                    ['hall' => 'Grand Ballroom', 'description' => 'One of the largest pillarless ballrooms in Colombo, with ocean-facing pre-function space.', 'capacity_min' => 400, 'capacity_max' => 1200, 'base_price' => 2600000, 'weekend_surcharge' => 500000, 'services' => ['LED video wall', 'Dance floor', 'Stage lighting']],
                    ['hall' => 'Colombo Ballroom', 'description' => 'A sophisticated mid-size ballroom for elegant weddings and banquets.', 'capacity_min' => 200, 'capacity_max' => 600, 'base_price' => 1900000, 'weekend_surcharge' => 400000, 'services' => ['Dance floor']],
                    ['hall' => 'Lotus Hall', 'description' => 'An intimate, refined function room ideal for ceremonies and smaller receptions.', 'capacity_min' => 80, 'capacity_max' => 250, 'base_price' => 1100000, 'weekend_surcharge' => 250000],
                ],
            ],
            [
                'name' => 'Galle Face Hotel',
                'slug' => 'galle-face-hotel',
                'city' => 'Colombo 03',
                'services' => $colomboServices,
                'halls' => [
                    ['hall' => 'Regency Ballroom', 'description' => 'A heritage ballroom in Sri Lanka’s iconic seafront hotel, established 1864.', 'capacity_min' => 150, 'capacity_max' => 500, 'base_price' => 1800000, 'weekend_surcharge' => 400000, 'services' => ['Dance floor', 'Ocean view']],
                    ['hall' => 'The Verandah', 'description' => 'A colonial-era open verandah overlooking the Indian Ocean for memorable receptions.', 'capacity_min' => 60, 'capacity_max' => 200, 'base_price' => 1200000, 'weekend_surcharge' => 280000, 'services' => ['Ocean view']],
                    ['hall' => '1864 Lawn', 'description' => 'A seafront lawn for sunset ceremonies and open-air celebrations.', 'capacity_min' => 100, 'capacity_max' => 400, 'base_price' => 1400000, 'weekend_surcharge' => 320000, 'services' => ['Outdoor setting', 'Ocean view']],
                ],
            ],
            [
                'name' => 'The Kingsbury Colombo',
                'slug' => 'the-kingsbury-colombo',
                'city' => 'Colombo 01',
                'services' => $colomboServices,
                'halls' => [
                    ['hall' => 'Grand Ballroom', 'description' => 'A contemporary pillarless ballroom on the Colombo waterfront for grand occasions.', 'capacity_min' => 250, 'capacity_max' => 750, 'base_price' => 2100000, 'weekend_surcharge' => 450000, 'services' => ['LED video wall', 'Dance floor']],
                    ['hall' => 'Winchester', 'description' => 'A stylish function suite for boutique weddings and corporate events.', 'capacity_min' => 100, 'capacity_max' => 300, 'base_price' => 1300000, 'weekend_surcharge' => 300000],
                    ['hall' => 'Sky Lounge', 'description' => 'A rooftop venue with panoramic harbour views for cocktail receptions.', 'capacity_min' => 50, 'capacity_max' => 180, 'base_price' => 1150000, 'weekend_surcharge' => 260000, 'services' => ['Rooftop', 'City view', 'Cocktail bar']],
                ],
            ],
            [
                'name' => 'Hilton Colombo',
                'slug' => 'hilton-colombo',
                'city' => 'Colombo 02',
                'services' => $colomboServices,
                'halls' => [
                    ['hall' => 'Grand Ballroom', 'description' => 'A spacious ballroom well suited to large weddings, conferences and gala dinners.', 'capacity_min' => 250, 'capacity_max' => 900, 'base_price' => 2200000, 'weekend_surcharge' => 450000, 'services' => ['LED video wall', 'Dance floor']],
                    ['hall' => 'Oak Room', 'description' => 'An elegant banquet room for refined receptions and private functions.', 'capacity_min' => 120, 'capacity_max' => 350, 'base_price' => 1500000, 'weekend_surcharge' => 320000],
                    ['hall' => 'Bay Leaf Terrace', 'description' => 'A garden terrace for relaxed open-air celebrations and cocktail evenings.', 'capacity_min' => 60, 'capacity_max' => 220, 'base_price' => 1150000, 'weekend_surcharge' => 250000, 'services' => ['Outdoor setting']],
                ],
            ],
            [
                'name' => 'Heritance Kandalama',
                'slug' => 'heritance-kandalama',
                'city' => 'Dambulla',
                'services' => ['In-house catering', 'Customizable menus', 'AV & stage', 'Bridal suite', 'Free parking', 'Dedicated wedding coordinator', 'Wi-Fi', 'Lakeside setting'],
                'halls' => [
                    ['hall' => 'Kandalama Ballroom', 'description' => 'A banquet hall within Geoffrey Bawa’s iconic forest hotel overlooking Kandalama Lake.', 'capacity_min' => 150, 'capacity_max' => 450, 'base_price' => 1600000, 'weekend_surcharge' => 320000, 'services' => ['Dance floor', 'Lake view']],
                    ['hall' => 'Kachchan Hall', 'description' => 'A versatile function room for weddings and corporate retreats amid nature.', 'capacity_min' => 80, 'capacity_max' => 250, 'base_price' => 1100000, 'weekend_surcharge' => 240000],
                    ['hall' => 'Lake Terrace', 'description' => 'An open-air terrace for destination weddings beside the reservoir and rock.', 'capacity_min' => 60, 'capacity_max' => 200, 'base_price' => 1250000, 'weekend_surcharge' => 280000, 'services' => ['Outdoor setting', 'Lake view']],
                ],
            ],
            [
                'name' => 'Jetwing Lighthouse',
                'slug' => 'jetwing-lighthouse',
                'city' => 'Galle',
                'services' => ['In-house catering', 'Customizable menus', 'AV & stage', 'Bridal suite', 'Free parking', 'Dedicated wedding coordinator', 'Wi-Fi', 'Oceanfront setting'],
                'halls' => [
                    ['hall' => 'Sailfish Ballroom', 'description' => 'A coastal ballroom in this Geoffrey Bawa-designed clifftop hotel near Galle Fort.', 'capacity_min' => 120, 'capacity_max' => 400, 'base_price' => 1500000, 'weekend_surcharge' => 300000, 'services' => ['Dance floor', 'Ocean view']],
                    ['hall' => 'Cinnamon Lawn', 'description' => 'A seaside garden lawn for sunset ceremonies and beach-side receptions.', 'capacity_min' => 80, 'capacity_max' => 300, 'base_price' => 1250000, 'weekend_surcharge' => 280000, 'services' => ['Outdoor setting', 'Ocean view']],
                    ['hall' => 'Clifftop Deck', 'description' => 'A dramatic clifftop deck above the ocean for intimate cocktail celebrations.', 'capacity_min' => 40, 'capacity_max' => 150, 'base_price' => 1050000, 'weekend_surcharge' => 240000, 'services' => ['Outdoor setting', 'Ocean view']],
                ],
            ],
            [
                'name' => "Earl's Regency",
                'slug' => 'earls-regency-kandy',
                'city' => 'Kandy',
                'services' => ['In-house catering', 'Customizable menus', 'AV & stage', 'Bridal suite', 'Free parking', 'Dedicated wedding coordinator', 'Wi-Fi', 'Hill-country setting'],
                'halls' => [
                    ['hall' => 'Regency Ballroom', 'description' => 'A grand ballroom nestled in the hills near Kandy for lavish weddings.', 'capacity_min' => 200, 'capacity_max' => 600, 'base_price' => 1550000, 'weekend_surcharge' => 320000, 'services' => ['Dance floor', 'Mountain view']],
                    ['hall' => 'Mahaweli Hall', 'description' => 'A riverside function hall for weddings and conferences by the Mahaweli.', 'capacity_min' => 100, 'capacity_max' => 350, 'base_price' => 1150000, 'weekend_surcharge' => 250000, 'services' => ['River view']],
                    ['hall' => 'Hill Country Lawn', 'description' => 'A landscaped lawn surrounded by hills for open-air celebrations.', 'capacity_min' => 80, 'capacity_max' => 280, 'base_price' => 1100000, 'weekend_surcharge' => 240000, 'services' => ['Outdoor setting', 'Mountain view']],
                ],
            ],
        ];
    }
}
