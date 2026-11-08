<?php
/**
 * HouGarden US homepage / search location markets (Admin → Regions).
 *
 * @return list<array{label: string, filter: array<string, string>, image: string}>
 */
declare(strict_types=1);

/**
 * @return array<string, string>
 */
function hougarden_region_search_filter(array $region): array
{
    $key = trim((string) ($region['home_filter_key'] ?? ''));
    $val = trim((string) ($region['home_filter_value'] ?? ''));
    if ($key === 'location' && $val !== '') {
        return ['location' => $val];
    }
    if ($key === 'market' && $val !== '') {
        return ['market' => $val];
    }

    $name = trim((string) ($region['name'] ?? ''));
    if ($name !== '') {
        return ['location' => $name];
    }

    return [];
}

function hougarden_region_card_image(array $region): string
{
    $path = trim((string) ($region['image_path'] ?? ''));
    if ($path !== '') {
        if (preg_match('#^https?://#i', $path)) {
            $rel = ltrim(str_replace('\\', '/', $path), '/');
            if (is_file(APP_ROOT . '/' . $rel)) {
                return media_url($rel);
            }

            return $path;
        }
        $rel = ltrim(str_replace('\\', '/', $path), '/');
        if (is_file(APP_ROOT . '/' . $rel)) {
            return media_url($path);
        }
    }

    $slug = strtolower((string) preg_replace('/[^a-z0-9]+/', '-', (string) ($region['slug'] ?? '')));
    $slug = trim($slug, '-');
    if ($slug !== '') {
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            foreach (['assets/img/regions/' . $slug, 'uploads/regions/' . $slug] as $base) {
                $candidate = $base . '.' . $ext;
                if (is_file(APP_ROOT . '/' . $candidate)) {
                    return media_url($candidate);
                }
            }
        }
    }

    return hougarden_location_fallback_image((string) ($region['name'] ?? ''), $slug);
}

function hougarden_location_fallback_image(string $label, string $slug = ''): string
{
    $slug = $slug !== '' ? $slug : strtolower((string) preg_replace('/[^a-z0-9]+/', '-', $label));
    $slug = trim($slug, '-');
    $slugMap = [
        'Austin' => 'austin-metro',
        'Phoenix' => 'phoenix-metro',
        'Atlanta' => 'atlanta-metro',
        'Denver' => 'denver-metro',
        'Chicago' => 'chicago-metro',
    ];
    $slug = $slugMap[$label] ?? $slug;
    if ($slug !== '') {
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            $candidate = 'assets/img/regions/' . $slug . '.' . $ext;
            if (is_file(APP_ROOT . '/' . $candidate)) {
                return media_url($candidate);
            }
        }
    }

    if (is_file(APP_ROOT . '/assets/img/hero-home.jpg')) {
        return media_url('assets/img/hero-home.jpg');
    }

    return '';
}

/**
 * @return list<array{label: string, filter: array<string, string>, image: string}>
 */
function hougarden_location_markets(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $fallback = hougarden_location_markets_fallback();

    try {
        $pdo = db();
        $sql = schema_column_exists($pdo, 'regions', 'home_filter_key')
            ? 'SELECT * FROM regions
               WHERE is_active = 1 AND is_featured = 1
                 AND home_filter_key IS NOT NULL AND TRIM(home_filter_key) <> \'\'
               ORDER BY sort_order ASC, name ASC'
            : 'SELECT * FROM regions WHERE is_active = 1 AND is_featured = 1 ORDER BY sort_order ASC, name ASC';
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($rows === []) {
            $cache = $fallback;

            return $cache;
        }

        $out = [];
        foreach ($rows as $row) {
            $filter = hougarden_region_search_filter($row);
            if ($filter === []) {
                continue;
            }
            $out[] = [
                'label' => (string) ($row['name'] ?? ''),
                'filter' => $filter,
                'image' => hougarden_region_card_image($row),
                'region_id' => (int) ($row['id'] ?? 0),
            ];
        }

        $cache = $out !== [] ? $out : $fallback;

        return $cache;
    } catch (Throwable $e) {
        $cache = $fallback;

        return $cache;
    }
}

/**
 * @return list<array{label: string, filter: array<string, string>, image: string}>
 */
function hougarden_location_markets_fallback(): array
{
    return [
        [
            'label' => 'Austin',
            'filter' => ['location' => 'Austin'],
            'image' => hougarden_location_fallback_image('Austin'),
        ],
        [
            'label' => 'Phoenix',
            'filter' => ['location' => 'Phoenix'],
            'image' => hougarden_location_fallback_image('Phoenix'),
        ],
        [
            'label' => 'Atlanta',
            'filter' => ['location' => 'Atlanta'],
            'image' => hougarden_location_fallback_image('Atlanta'),
        ],
        [
            'label' => 'Denver',
            'filter' => ['location' => 'Denver'],
            'image' => hougarden_location_fallback_image('Denver'),
        ],
        [
            'label' => 'Chicago',
            'filter' => ['location' => 'Chicago'],
            'image' => hougarden_location_fallback_image('Chicago'),
        ],
    ];
}

/**
 * @return list<string>
 */
function hougarden_suggest_location_labels(): array
{
    $labels = [];
    foreach (hougarden_location_markets() as $row) {
        $labels[] = (string) $row['label'];
    }

    return $labels;
}
