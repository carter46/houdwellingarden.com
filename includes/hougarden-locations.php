<?php
/**
 * HouGarden AU homepage / search location markets.
 *
 * @return list<array{label: string, filter: array<string, string>, image: string}>
 */
declare(strict_types=1);

function hougarden_location_markets(): array
{
    return [
        [
            'label' => 'Bellevue Hill',
            'filter' => ['location' => 'Bellevue Hill'],
            'image' => 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?auto=format&fit=crop&w=800&q=80',
        ],
        [
            'label' => 'Rose Bay',
            'filter' => ['location' => 'Rose Bay'],
            'image' => 'https://images.unsplash.com/photo-1505142468610-359e7d316be0?auto=format&fit=crop&w=800&q=80',
        ],
        [
            'label' => 'South Yarra',
            'filter' => ['location' => 'South Yarra'],
            'image' => 'https://images.unsplash.com/photo-1514395462725-fb8496db94a8?auto=format&fit=crop&w=800&q=80',
        ],
        [
            'label' => 'Ocean Grove',
            'filter' => ['location' => 'Ocean Grove'],
            'image' => 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?auto=format&fit=crop&w=800&q=80',
        ],
        [
            'label' => 'Victoria',
            'filter' => ['market' => 'victoria'],
            'image' => 'https://images.unsplash.com/photo-1519864607624-a07bb8c2477e?auto=format&fit=crop&w=800&q=80',
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
