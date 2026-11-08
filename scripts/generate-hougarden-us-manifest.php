<?php
/**
 * Build database/hougarden_us_manifest.json — 33 US demo listings (Zillow-style metros, not scraped data).
 * Usage: php scripts/generate-hougarden-us-manifest.php
 */

declare(strict_types=1);

$outPath = dirname(__DIR__) . '/database/hougarden_us_manifest.json';

$placeholderDescription =
    'Demo placeholder for website presentation only—not a real MLS listing. '
    . 'Sample US market data inspired by major listing sites; replace with licensed inventory before launch.';

/** Real home photos (Pexels) — see scripts/seed-pexels-property-photos.php */
/** @var list<string> */
$images = [
    'https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/2102587/pexels-photo-2102587.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/3288102/pexels-photo-3288102.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/323775/pexels-photo-323775.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/323780/pexels-photo-323780.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/186077/pexels-photo-186077.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/259588/pexels-photo-259588.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1029599/pexels-photo-1029599.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/280222/pexels-photo-280222.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/462235/pexels-photo-462235.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/534151/pexels-photo-534151.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/691582/pexels-photo-691582.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/2724749/pexels-photo-2724749.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/3559488/pexels-photo-3559488.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1457842/pexels-photo-1457842.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1438832/pexels-photo-1438832.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/2132126/pexels-photo-2132126.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/2581922/pexels-photo-2581922.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1387037/pexels-photo-1387037.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/259962/pexels-photo-259962.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/189349/pexels-photo-189349.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1582280/pexels-photo-1582280.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/731890/pexels-photo-731890.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1170584/pexels-photo-1170584.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/323705/pexels-photo-323705.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/2631746/pexels-photo-2631746.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/379799/pexels-photo-379799.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/53610/pexels-photo-53610.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/111580/pexels-photo-111580.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1571468/pexels-photo-1571468.jpeg?auto=compress&cs=tinysrgb&w=1400',
];

/** @var list<array<string, mixed>> */
$raw = [
    // Austin (7)
    ['Austin', 'Craftsman near Zilker', '2101 Barton Springs Rd', 'Austin', 'Travis County', 'TX', '78704', 875000, 4, 3, 2450, 'New', 1],
    ['Austin', 'Downtown condo, Lady Bird Lake', '360 Nueces St #2804', 'Austin', 'Downtown', 'TX', '78701', 649000, 2, 2, 1180, null, 1],
    ['Austin', 'Modern home, Mueller', '4500 Zach Scott St', 'Austin', 'Mueller', 'TX', '78723', 725000, 3, 2.5, 2100, null, 0],
    ['Austin', 'Tarrytown traditional', '2705 Enfield Rd', 'Austin', 'Tarrytown', 'TX', '78703', 1595000, 5, 4, 3400, 'Exclusive', 1],
    ['Austin', 'East Austin bungalow', '1615 E 6th St', 'Austin', 'East Austin', 'TX', '78702', 525000, 3, 2, 1650, 'Open House', 0],
    ['Austin', 'Lake Travis view', '15201 Four Waters Pass', 'Austin', 'Lake Travis', 'TX', '78734', 1890000, 4, 3.5, 3200, null, 0],
    ['Austin', 'Build site, Circle C', '4901 Gaines Ranch Loop', 'Austin', 'Circle C', 'TX', '78749', 425000, null, null, null, 'Land', 0],
    // Phoenix (7)
    ['Phoenix', 'Arcadia ranch', '4432 E Camelback Rd', 'Phoenix', 'Arcadia', 'AZ', '85018', 1185000, 4, 3, 2800, null, 1],
    ['Phoenix', 'Biltmore high-rise', '2425 E Camelback Rd #1201', 'Phoenix', 'Biltmore', 'AZ', '85016', 495000, 2, 2, 1350, null, 0],
    ['Phoenix', 'North Central remodel', '20 W Catalina Dr', 'Phoenix', 'North Central', 'AZ', '85013', 675000, 3, 2, 1920, 'Price Drop', 0],
    ['Phoenix', 'Desert Ridge family home', '4545 E Mayo Blvd', 'Phoenix', 'Desert Ridge', 'AZ', '85050', 585000, 4, 3, 2300, null, 0],
    ['Phoenix', 'Ahwatukee hillside', '15405 S 40th St', 'Phoenix', 'Ahwatukee', 'AZ', '85048', 799000, 4, 3.5, 2650, null, 1],
    ['Phoenix', 'Downtown loft', '111 W Monroe St #804', 'Phoenix', 'Downtown', 'AZ', '85003', 389000, 1, 1, 890, null, 0],
    ['Phoenix', 'Desert Vista lot', '1901 E South Mountain Ave', 'Phoenix', 'South Mountain', 'AZ', '85042', 275000, null, null, null, 'Land', 0],
    // Atlanta (7)
    ['Atlanta', 'Buckhead estate', '3575 Knollwood Dr NW', 'Atlanta', 'Buckhead', 'GA', '30327', 2450000, 5, 5.5, 6200, 'Exclusive', 1],
    ['Atlanta', 'Midtown penthouse', '1065 Peachtree St NE #3201', 'Atlanta', 'Midtown', 'GA', '30309', 975000, 3, 3, 2200, null, 1],
    ['Atlanta', 'Grant Park craftsman', '596 Cherokee Ave SE', 'Atlanta', 'Grant Park', 'GA', '30312', 615000, 3, 2, 1800, null, 0],
    ['Atlanta', 'Virginia-Highland bungalow', '821 N Highland Ave NE', 'Atlanta', 'Virginia-Highland', 'GA', '30306', 725000, 4, 3, 2100, 'Open House', 0],
    ['Atlanta', 'Westside modern', '1150 Howell Mill Rd NW #1402', 'Atlanta', 'Westside', 'GA', '30318', 549000, 2, 2, 1250, null, 0],
    ['Atlanta', 'Decatur family home', '2566 Mead Rd', 'Atlanta', 'Decatur', 'GA', '30030', 485000, 4, 2.5, 2050, null, 0],
    ['Atlanta', 'Chastain Park ranch', '4595 Dudley Ln NE', 'Atlanta', 'Chastain Park', 'GA', '30327', 925000, 4, 3, 2550, null, 0],
    // Denver (6)
    ['Denver', 'Wash Park brick', '725 S Franklin St', 'Denver', 'Wash Park', 'CO', '80209', 1125000, 4, 3, 2400, null, 1],
    ['Denver', 'LoHi townhome', '3649 Navajo St', 'Denver', 'LoHi', 'CO', '80211', 825000, 3, 2.5, 1850, 'New', 0],
    ['Denver', 'Cherry Creek condo', '2500 E 2nd Ave #401', 'Denver', 'Cherry Creek', 'CO', '80206', 695000, 2, 2, 1280, null, 1],
    ['Denver', 'Stapleton contemporary', '7587 E 26th Ave', 'Denver', 'Central Park', 'CO', '80238', 765000, 4, 3, 2280, null, 0],
    ['Denver', 'RiNo loft', '3300 Walnut St #212', 'Denver', 'RiNo', 'CO', '80205', 535000, 2, 2, 1150, null, 0],
    ['Denver', 'Mountain view acreage', '10450 W Ute Ave', 'Denver', 'Bear Valley', 'CO', '80227', 1150000, 5, 4, 3100, null, 0],
    // Chicago (6)
    ['Chicago', 'Lincoln Park greystone', '2140 N Burling St', 'Chicago', 'Lincoln Park', 'IL', '60614', 1850000, 4, 3.5, 3200, 'Exclusive', 1],
    ['Chicago', 'Gold Coast co-op', '1500 N Lake Shore Dr #10C', 'Chicago', 'Gold Coast', 'IL', '60610', 625000, 2, 2, 1100, null, 1],
    ['Chicago', 'West Loop new construction', '845 W Madison St #2103', 'Chicago', 'West Loop', 'IL', '60607', 899000, 3, 2, 1750, null, 0],
    ['Chicago', 'Wicker Park two-flat', '2138 W Pierce Ave', 'Chicago', 'Wicker Park', 'IL', '60622', 775000, 5, 3, 2900, null, 0],
    ['Chicago', 'Hyde Park classic', '5440 S Hyde Park Blvd', 'Chicago', 'Hyde Park', 'IL', '60615', 425000, 3, 2, 1650, 'Open House', 0],
    ['Chicago', 'River North high-rise', '345 E Ohio St #2802', 'Chicago', 'River North', 'IL', '60611', 1150000, 3, 2.5, 1900, null, 0],
];

$listings = [];
foreach ($raw as $i => $row) {
    [$cat, $title, $addr, $city, $region, $state, $zip, $price, $beds, $baths, $sqft, $badge, $featured] = $row;
    $listings[] = [
        'location_category' => $cat,
        'title' => $title,
        'description' => $placeholderDescription,
        'address_line' => $addr,
        'city' => $city,
        'region' => $region,
        'state' => $state,
        'postal_code' => $zip,
        'price' => $price,
        'bedrooms' => $beds,
        'bathrooms' => $baths,
        'sqft' => $sqft,
        'badge' => $badge,
        'is_featured' => (int) $featured,
        'source_url' => '',
        'image_url' => $images[$i % count($images)],
    ];
}

$payload = [
    'meta' => [
        'purpose' => 'US demo placeholder listings (not scraped from Zillow or any MLS).',
        'currency' => 'USD',
        'country' => 'United States',
        'markets' => ['Austin', 'Phoenix', 'Atlanta', 'Denver', 'Chicago'],
        'generated' => gmdate('Y-m-d'),
    ],
    'listings' => $listings,
];

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    fwrite(STDERR, "json_encode failed\n");
    exit(1);
}

file_put_contents($outPath, $json . "\n");
echo "Wrote " . count($listings) . " listings to {$outPath}\n";
