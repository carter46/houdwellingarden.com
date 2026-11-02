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
                    'a' => 'Browse by location on the homepage—listings are grouped for Bellevue Hill, Rose Bay, South Yarra, Ocean Grove, and Victoria.',
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
        $intro = site_name() . ' helps buyers and sellers navigate premium Australian property with clear search, location-led discovery, and live inventory backed by MySQL—not static placeholders.';
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
            ['value' => 'AU', 'label' => 'Market focus'],
            ['value' => '1', 'label' => 'Database source of truth'],
        ];
    }

    return [
        'intro' => $intro,
        'mission' => $mission,
        'stats' => $stats,
        'hero_title' => trim((string) (setting_get('about_hero_title') ?? '')) ?: 'Property search, the modern way',
        'hero_lead' => trim((string) (setting_get('about_hero_lead') ?? '')) ?: 'Local expertise across Sydney and Victoria, with inventory you can trust.',
    ];
}
