<?php
/**
 * Inline SVG icons (replaces Material Symbols on public + admin HouGarden UI).
 */
declare(strict_types=1);

/**
 * @param array<string, string|int>|null $attrs
 */
function hg_icon(string $name, string $class = '', ?array $attrs = null): string
{
    $inner = hg_icon_paths()[$name] ?? hg_icon_paths()['help'];
    $cls = trim('hg-icon ' . $class);
    $extra = '';
    if (is_array($attrs)) {
        foreach ($attrs as $k => $v) {
            $extra .= ' ' . htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8') . '="'
                . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '"';
        }
    }

    return '<svg class="' . e($cls) . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"'
        . ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
        . ' aria-hidden="true"' . $extra . '>' . $inner . '</svg>';
}

/** @return array<string, string> */
function hg_icon_paths(): array
{
    static $map = null;
    if (is_array($map)) {
        return $map;
    }

    $map = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/>',
        'chat' => '<path d="M21 15a4 4 0 0 1-4 4H7l-4 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>',
        'tune' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'person' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c1.5-3.5 4.5-5.5 7-5.5s5.5 2 7 5.5"/>',
        'location_on' => '<path d="M12 21s6-5.5 6-11a6 6 0 1 0-12 0c0 5.5 6 11 6 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'bookmark' => '<path d="M6 4h12v16l-6-4-6 4V4z"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 10v6M12 7h.01"/>',
        'photo_camera' => '<path d="M4 8h3l2-2h6l2 2h3v11H4V8z"/><circle cx="12" cy="13" r="3.5"/>',
        'bed' => '<path d="M3 14v-2a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v2"/><path d="M3 14v5M21 14v5M3 10V7h6v7H3zM15 7h6v7h-6z"/>',
        'bathtub' => '<path d="M4 14h16v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3z"/><path d="M6 14V8a2 2 0 0 1 2-2h1v8M18 14V6h-2"/>',
        'shower' => '<path d="M8 4v4M12 4v2M16 4v4"/><path d="M6 20v-6a6 6 0 0 1 12 0v6"/>',
        'square_foot' => '<rect x="4" y="4" width="16" height="16" rx="1"/><path d="M4 9h16M9 4v16"/>',
        'calendar_today' => '<rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 3v4M16 3v4M4 11h16"/>',
        'landscape' => '<path d="M3 18h18M6 14l3-4 3 3 4-6 3 7"/>',
        'favorite' => '<path d="M12 20s-7-4.5-7-10a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 5.5-7 10-7 10z"/>',
        'expand_more' => '<path d="M6 9l6 6 6-6"/>',
        'check_circle' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l2.5 2.5L16 9"/>',
        'add' => '<path d="M12 5v14M5 12h14"/>',
        'arrow_forward' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'dashboard' => '<rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="5" rx="1"/><rect x="13" y="10" width="8" height="11" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/>',
        'home_work' => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
        'category' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
        'map' => '<path d="M9 4l6 2 5-2v15l-5 2-6-2-5 2V4z"/><path d="M9 4v15M15 6v15"/>',
        'checklist' => '<path d="M9 6h12M9 12h12M9 18h12"/><path d="M5 6l1 1 2-3M5 12l1 1 2-3M5 18l1 1 2-3"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'public' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 0 1 4.5 1.5c0 2-2.5 2-2.5 4"/><path d="M12 17h.01"/>',
    ];

    return $map;
}
