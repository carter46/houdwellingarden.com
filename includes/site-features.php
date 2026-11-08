<?php
/**
 * Public site feature flags & integrations (admin → settings table).
 */

declare(strict_types=1);

function setting_bool(string $key, bool $default = true): bool
{
    $raw = setting_get($key);
    if ($raw === null || $raw === '') {
        return $default;
    }
    return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
}

function setting_set_bool(string $key, bool $value): void
{
    setting_set($key, $value ? '1' : '0');
}

function smartsupp_chat_key(): string
{
    $fromDb = trim((string) (setting_get('smartsupp_key') ?? ''));
    if ($fromDb !== '') {
        return $fromDb;
    }
    return trim((string) app_config('smartsupp.key', ''));
}

function smartsupp_chat_enabled(): bool
{
    if (!setting_bool('smartsupp_enabled', true)) {
        return false;
    }
    return smartsupp_chat_key() !== '';
}

function property_page_show_agent(): bool
{
    return setting_bool('property_show_agent', true);
}

function property_page_show_inquiry_form(): bool
{
    return setting_bool('property_show_inquiry', true);
}

/**
 * Strip import/demo boilerplate from property descriptions on the public site.
 */
function property_public_description(array $property): string
{
    $desc = trim((string) ($property['description'] ?? ''));
    if ($desc === '') {
        return '';
    }
    $lower = strtolower($desc);
    if (str_contains($lower, 'sourced for houdwell garden demo')
        || str_contains($lower, 'hougarden /au style')
        || (str_starts_with($lower, 'listing in ') && str_contains($lower, 'hougarden.com'))) {
        return '';
    }
    return $desc;
}

/**
 * @return list<array{title: string, items: list<array{q: string, a: string}>}>
 */
function faq_groups_public(): array
{
    $raw = trim((string) (setting_get('faq_json') ?? ''));
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && $decoded !== []) {
            return $decoded;
        }
    }

    $brand = site_name();
    return [
        [
            'title' => 'Buying & showings',
            'items' => [
                [
                    'q' => 'How do I book a private inspection?',
                    'a' => 'Use Enquire on any listing or the Contact page. Your message is saved in our system and emailed to the team.',
                ],
                [
                    'q' => 'Are listings live from the database?',
                    'a' => 'Yes. Every public page reads from MySQL. Updates in admin appear on the site immediately.',
                ],
                [
                    'q' => 'Which areas do you cover?',
                    'a' => 'Browse by location on the homepage—demo inventory is grouped for Austin, Phoenix, Atlanta, Denver, and Chicago.',
                ],
            ],
        ],
        [
            'title' => 'Working with ' . $brand,
            'items' => [
                [
                    'q' => 'How do I sell or list a property?',
                    'a' => 'Contact us with your address and timeline. Listings are managed through the secure admin console.',
                ],
                [
                    'q' => 'Who receives my enquiry?',
                    'a' => 'Authorised administrators review inquiries. You receive an acknowledgment email when mail is configured.',
                ],
            ],
        ],
    ];
}

function about_content_public(): array
{
    $intro = trim((string) (setting_get('about_intro') ?? ''));
    if ($intro === '') {
        $intro = site_name() . ' helps buyers and sellers explore US homes with clear search, city-led discovery, and live inventory in MySQL. Current listings are labeled demo placeholders until you add real MLS data.';
    }
    $mission = trim((string) (setting_get('about_mission') ?? ''));
    if ($mission === '') {
        $mission = 'We combine HouGarden-inspired search and location browsing with a single database of listings, agents, and enquiries so what you see on the site is what the team manages in admin.';
    }

    $statsRaw = setting_get('about_stats_json');
    $stats = [];
    if ($statsRaw) {
        $decoded = json_decode((string) $statsRaw, true);
        if (is_array($decoded)) {
            $stats = $decoded;
        }
    }
    if ($stats === []) {
        try {
            $total = (int) db()->query(
                "SELECT COUNT(*) FROM properties WHERE status IN ('available','pending','under_contract','sold','private')"
            )->fetchColumn();
        } catch (Throwable $e) {
            $total = 0;
        }
        $stats = [
            ['value' => (string) max($total, 1) . '+', 'label' => 'Live listings'],
            ['value' => '5', 'label' => 'Featured locations'],
            ['value' => 'US', 'label' => 'Market focus'],
            ['value' => '1', 'label' => 'Database source of truth'],
        ];
    }

    return [
        'intro' => $intro,
        'mission' => $mission,
        'stats' => $stats,
        'hero_title' => trim((string) (setting_get('about_hero_title') ?? '')) ?: 'Property search, the modern way',
        'hero_lead' => trim((string) (setting_get('about_hero_lead') ?? '')) ?: 'Search-led discovery across Austin, Phoenix, Atlanta, Denver, and Chicago—with inventory your team controls in admin.',
        'pillars' => about_pillars_public(),
        'cta_title' => trim((string) (setting_get('about_cta_title') ?? '')) ?: 'Ready to explore?',
        'cta_body' => trim((string) (setting_get('about_cta_body') ?? '')) ?: 'Browse live listings or send a message—our team responds using the contact details in Admin → Settings.',
    ];
}

/**
 * @return list<array{title: string, body: string, icon: string}>
 */
function about_pillars_public(): array
{
    $raw = trim((string) (setting_get('about_pillars_json') ?? ''));
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && $decoded !== []) {
            $out = [];
            foreach ($decoded as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $title = trim((string) ($row['title'] ?? ''));
                $body = trim((string) ($row['body'] ?? ''));
                if ($title === '' || $body === '') {
                    continue;
                }
                $icon = trim((string) ($row['icon'] ?? 'info'));
                $out[] = ['title' => $title, 'body' => $body, 'icon' => $icon !== '' ? $icon : 'info'];
            }
            if ($out !== []) {
                return $out;
            }
        }
    }

    $brand = site_name();

    return [
        [
            'title' => 'Search-first discovery',
            'body' => 'Natural-language search, market pages, and filters—every result is pulled from the same MySQL inventory you manage in admin.',
            'icon' => 'search',
        ],
        [
            'title' => 'City-led markets',
            'body' => 'Featured US metros on the homepage and region cards you can retitle, re-image, and reorder without touching code.',
            'icon' => 'location_on',
        ],
        [
            'title' => 'Transparent enquiries',
            'body' => 'Property and contact forms create inquiry records, notify administrators, and acknowledge clients when mail is configured.',
            'icon' => 'mail',
        ],
    ];
}
