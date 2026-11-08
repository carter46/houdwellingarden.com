<?php
/**
 * HouGarden public shell helpers (layout CSS + optional schema).
 */

declare(strict_types=1);

/** @deprecated Use app_run_schema_migrations() via bootstrap. Kept for scripts that call this by name. */
function hougarden_ensure_home_feed_columns(): void
{
    app_run_schema_migrations();
}

/**
 * Apply HouGarden shell styles to a public page.
 */
function hougarden_use_public_shell(string $activeNavKey, ?string $extraBodyClass = null): void
{
    $GLOBALS['activeNav'] = $activeNavKey;
    $base = trim((string) ($GLOBALS['bodyClass'] ?? ''));
    $shell = 'hougarden-site';
    if ($extraBodyClass !== null && $extraBodyClass !== '') {
        $shell .= ' ' . trim($extraBodyClass);
    }
    $GLOBALS['bodyClass'] = $base !== '' ? $base . ' ' . $shell : $shell;

    $css = asset_url('assets/css/hougarden.css');
    $existing = $GLOBALS['pageStylesheets'] ?? [];
    if (!is_array($existing)) {
        $existing = [];
    }
    if (!in_array($css, $existing, true)) {
        array_unshift($existing, $css);
    }
    $GLOBALS['pageStylesheets'] = $existing;
}

/**
 * @return list<array{label: string, href: string, icon: string, key: string}>
 */
/**
 * @return list<array{label: string, href: string}>
 */
function hougarden_topnav_items(): array
{
    return [
        ['label' => 'Buy', 'href' => base_url('properties.php')],
        ['label' => 'Markets', 'href' => base_url('markets.php')],
        ['label' => 'Sell', 'href' => base_url('contact.php?subject=' . rawurlencode('Sell / list a property'))],
        ['label' => 'About', 'href' => base_url('about.php')],
        ['label' => 'Contact', 'href' => base_url('contact.php')],
    ];
}

function hougarden_sidebar_items(): array
{
    return [
        [
            'key' => 'ask',
            'label' => 'Ask',
            'href' => base_url('contact.php?subject=' . rawurlencode('Ask HouGarden')),
            'icon' => 'chat',
        ],
        [
            'key' => 'about',
            'label' => 'About',
            'href' => base_url('about.php'),
            'icon' => 'info',
        ],
        [
            'key' => 'search',
            'label' => 'Search',
            'href' => base_url('properties.php'),
            'icon' => 'search',
        ],
        [
            'key' => 'saved',
            'label' => 'Saved',
            'href' => base_url('index.php?feed=saved'),
            'icon' => 'bookmark',
        ],
    ];
}
