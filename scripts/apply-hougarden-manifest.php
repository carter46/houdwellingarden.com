<?php
/**
 * Re-label existing properties in place from database/hougarden_au_manifest.json.
 * Does not delete rows — updates the first N properties by id.
 *
 * Usage: php scripts/apply-hougarden-manifest.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$manifestPath = dirname(__DIR__) . '/database/hougarden_au_manifest.json';
if (!is_readable($manifestPath)) {
    fwrite(STDERR, "Missing manifest: {$manifestPath}\n");
    exit(1);
}

$data = json_decode((string) file_get_contents($manifestPath), true);
$listings = $data['listings'] ?? [];
if (!is_array($listings) || $listings === []) {
    fwrite(STDERR, "No listings in manifest.\n");
    exit(1);
}

$pdo = db();
$ids = $pdo->query('SELECT id, slug FROM properties ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
if ($ids === []) {
    fwrite(STDERR, "No properties in database.\n");
    exit(1);
}

$typeStmt = $pdo->prepare('SELECT id FROM property_types WHERE slug = ? LIMIT 1');
$typeStmt->execute(['single_family']);
$typeId = (int) ($typeStmt->fetchColumn() ?: 0);
$condoStmt = $pdo->prepare('SELECT id FROM property_types WHERE slug = ? LIMIT 1');
$condoStmt->execute(['condo']);
$condoTypeId = (int) ($condoStmt->fetchColumn() ?: $typeId);

$uploadRoot = dirname(__DIR__) . '/uploads/properties';

function hg_download_image(string $url, string $destPath): bool
{
    if ($url === '') {
        return false;
    }
    $dir = dirname($destPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 25,
            'header' => "User-Agent: HoudwellLocalImport/1.0\r\nAccept: image/*\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $bin = @file_get_contents($url, false, $ctx);
    if ($bin === false || strlen($bin) < 500) {
        return false;
    }
    $ext = 'jpg';
    if (str_contains($url, '.webp')) {
        $ext = 'webp';
    }
    if (!str_ends_with($destPath, '.jpg') && !str_ends_with($destPath, '.webp')) {
        $destPath .= '.' . $ext;
    }
    return file_put_contents($destPath, $bin) !== false;
}

function hg_slug_from_address(string $address, string $city): string
{
    $base = strtolower($address . ' ' . $city);
    $base = preg_replace('/[^a-z0-9]+/', '-', $base) ?? 'property';
    $base = trim($base, '-');
    return substr($base, 0, 80) ?: 'property';
}

$updateWithDesc = $pdo->prepare(
    'UPDATE properties SET
      slug = ?, reference_code = ?, title = ?, description = ?,
      property_type_id = ?, status = ?, price = ?, price_on_request = 0, currency = ?,
      address_line = ?, city = ?, region = ?, state = ?, postal_code = ?, country = ?,
      bedrooms = ?, bathrooms = ?, sqft = ?, badge = ?, is_featured = ?,
      listed_at = CURDATE(), source_name = ?, source_url = ?, source_reference = ?
     WHERE id = ?'
);

$updateNoDesc = $pdo->prepare(
    'UPDATE properties SET
      slug = ?, reference_code = ?, title = ?,
      property_type_id = ?, status = ?, price = ?, price_on_request = 0, currency = ?,
      address_line = ?, city = ?, region = ?, state = ?, postal_code = ?, country = ?,
      bedrooms = ?, bathrooms = ?, sqft = ?, badge = ?, is_featured = ?,
      listed_at = CURDATE(), source_name = ?, source_url = ?, source_reference = ?
     WHERE id = ?'
);

$delImg = $pdo->prepare('DELETE FROM property_images WHERE property_id = ?');
$insImg = $pdo->prepare(
    'INSERT INTO property_images (property_id, path, alt_text, sort_order, is_cover) VALUES (?, ?, ?, ?, ?)'
);

$applied = 0;
$imagesOk = 0;
$imagesFail = 0;

foreach ($listings as $i => $row) {
    if (!isset($ids[$i])) {
        break;
    }
    $propertyId = (int) $ids[$i]['id'];
    $oldSlug = (string) $ids[$i]['slug'];

    $city = (string) ($row['city'] ?? '');
    $address = (string) ($row['address_line'] ?? '');
    $newSlug = hg_slug_from_address($address, $city);
    if ($newSlug === 'property') {
        $newSlug = $oldSlug;
    }
    // Avoid slug collision: append id
    $newSlug = $newSlug . '-' . $propertyId;

    $beds = $row['bedrooms'] ?? null;
    $isApartment = is_numeric($beds) && (float) $beds <= 2.5 && str_contains(strtolower($address), '/');
    $propTypeId = $isApartment ? $condoTypeId : $typeId;

    $desc = trim((string) ($row['description'] ?? ''));
    if ($desc === '') {
        $desc = null;
    }

    $reference = 'HG-AU-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);

    $common = [
        $newSlug,
        $reference,
        (string) ($row['title'] ?? $address),
        $propTypeId,
        'available',
        $row['price'] ?? null,
        'AUD',
        $address,
        $city,
        (string) ($row['region'] ?? $city),
        (string) ($row['state'] ?? ''),
        (string) ($row['postal_code'] ?? ''),
        'Australia',
        $row['bedrooms'] ?? null,
        $row['bathrooms'] ?? null,
        $row['sqft'] ?? null,
        $row['badge'] ?? null,
        !empty($row['is_featured']) ? 1 : 0,
        'hougarden_au',
        (string) ($row['source_url'] ?? ''),
        $reference,
        $propertyId,
    ];

    if ($desc !== null) {
        $updateWithDesc->execute(array_merge(
            [$common[0], $common[1], $common[2], $desc],
            array_slice($common, 3)
        ));
    } else {
        $updateNoDesc->execute($common);
    }

    $folder = $uploadRoot . '/hg-' . $propertyId;
    $relPath = 'uploads/properties/hg-' . $propertyId . '/cover.jpg';
    $absPath = dirname(__DIR__) . '/' . $relPath;

    $galleryUrls = $row['gallery_urls'] ?? [];
    if (!is_array($galleryUrls) || $galleryUrls === []) {
        $galleryUrls = array_filter([(string) ($row['image_url'] ?? '')]);
    }

    $delImg->execute([$propertyId]);
    $downloadedAny = false;
    $sort = 0;
    foreach ($galleryUrls as $gi => $imageUrl) {
        $imageUrl = (string) $imageUrl;
        if ($imageUrl === '') {
            continue;
        }
        if ($sort === 0) {
            $rel = $relPath;
            $abs = $absPath;
        } else {
            $rel = 'uploads/properties/hg-' . $propertyId . '/gallery-' . ($sort + 1) . '.jpg';
            $abs = dirname(__DIR__) . '/' . $rel;
        }
        if (hg_download_image($imageUrl, $abs)) {
            $downloadedAny = true;
            $insImg->execute([
                $propertyId,
                $rel,
                (string) ($row['title'] ?? 'Property photo') . ' — ' . ($sort + 1),
                $sort,
                $sort === 0 ? 1 : 0,
            ]);
            $sort++;
        }
    }

    if ($downloadedAny) {
        $imagesOk++;
    } else {
        $existing = $pdo->prepare('SELECT path FROM property_images WHERE property_id = ? ORDER BY is_cover DESC LIMIT 1');
        $existing->execute([$propertyId]);
        if (!$existing->fetchColumn()) {
            $imagesFail++;
        } else {
            $imagesFail++;
        }
    }

    $applied++;
}

// Remaining properties: mark as Australia backup listings with same country/currency
$remaining = array_slice($ids, count($listings));
foreach ($remaining as $j => $r) {
    $pid = (int) $r['id'];
    $pdo->prepare(
        "UPDATE properties SET country = 'Australia', currency = 'AUD', status = 'available',
         source_name = COALESCE(source_name, 'hougarden_au_extra')
         WHERE id = ?"
    )->execute([$pid]);
}

echo "Updated {$applied} properties from manifest.\n";
echo "Images downloaded: {$imagesOk}, kept/failed: {$imagesFail}\n";
echo "Extra properties left in DB: " . count($remaining) . " (unchanged titles, AUD country set)\n";
