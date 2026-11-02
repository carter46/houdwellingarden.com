<?php
/**
 * HouGarden AU homepage / search location markets (Admin → Regions).
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

    $slug = strtolower((string) ($region['slug'] ?? ''));
    $name = trim((string) ($region['name'] ?? ''));
    if ($slug === 'victoria' || strcasecmp($name, 'Victoria') === 0) {
        return ['market' => 'victoria'];
    }
    if ($name !== '') {
        return ['location' => $name];
    }

    return [];
}

function hougarden_region_card_image(array $region): string
{
    $slug = strtolower((string) preg_replace('/[^a-z0-9]+/', '-', (string) ($region['slug'] ?? '')));
    $slug = trim($slug, '-');
    if ($slug !== '') {
        foreach (['jpg', 'jpeg', 'webp', 'png'] as $ext) {
            $candidate = 'uploads/regions/' . $slug . '.' . $ext;
            if (is_file(APP_ROOT . '/' . $candidate)) {
                return media_url($candidate);
            }
        }
    }

    $path = trim((string) ($region['image_path'] ?? ''));
    if ($path !== '') {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $rel = ltrim(str_replace('\\', '/', $path), '/');
        if (is_file(APP_ROOT . '/' . $rel)) {
            return media_url($path);
        }
    }

    return hougarden_location_fallback_image((string) ($region['name'] ?? ''));
}

function hougarden_location_fallback_image(string $label): string
{
    $map = [
        'Bellevue Hill' => 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?auto=format&fit=crop&w=800&q=80',
        'Rose Bay' => 'https://images.unsplash.com/photo-1505142468610-359e7d316be0?auto=format&fit=crop&w=800&q=80',
        'South Yarra' => 'https://images.unsplash.com/photo-1514395462725-fb8496db94a8?auto=format&fit=crop&w=800&q=80',
        'Ocean Grove' => 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?auto=format&fit=crop&w=800&q=80',
        'Victoria' => 'https://images.unsplash.com/photo-1519864607624-a07bb8c2477e?auto=format&fit=crop&w=800&q=80',
    ];

    return $map[$label] ?? 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=800&q=80';
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
            'label' => 'Bellevue Hill',
            'filter' => ['location' => 'Bellevue Hill'],
            'image' => hougarden_location_fallback_image('Bellevue Hill'),
        ],
        [
            'label' => 'Rose Bay',
            'filter' => ['location' => 'Rose Bay'],
            'image' => hougarden_location_fallback_image('Rose Bay'),
        ],
        [
            'label' => 'South Yarra',
            'filter' => ['location' => 'South Yarra'],
            'image' => hougarden_location_fallback_image('South Yarra'),
        ],
        [
            'label' => 'Ocean Grove',
            'filter' => ['location' => 'Ocean Grove'],
            'image' => hougarden_location_fallback_image('Ocean Grove'),
        ],
        [
            'label' => 'Victoria',
            'filter' => ['market' => 'victoria'],
            'image' => hougarden_location_fallback_image('Victoria'),
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
