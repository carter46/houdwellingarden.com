<?php
/**
 * HouGarden-style footer for homepage layout.
 */

declare(strict_types=1);

require_once __DIR__ . '/hougarden-locations.php';

$phone = site_phone();
$email = site_email();
$footerLocations = hougarden_location_markets();
?>
    </main>
    <footer class="hg-footer">
      <div class="hg-footer__grid">
        <div class="hg-footer__col">
          <p class="hg-footer__heading">Homes for sale</p>
          <ul class="hg-footer__links">
            <?php foreach ($footerLocations as $loc): ?>
              <?php
                $label = (string) ($loc['label'] ?? '');
                $filter = $loc['filter'] ?? [];
                if ($label === '' || !is_array($filter)) {
                    continue;
                }
              ?>
              <li><a href="<?= e(base_url('properties.php?' . http_build_query($filter))) ?>"><?= e($label) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="hg-footer__col">
          <p class="hg-footer__heading">Homes for rent</p>
          <ul class="hg-footer__links">
            <li><a href="<?= e(base_url('properties.php')) ?>">Browse rentals</a></li>
            <li><a href="<?= e(base_url('contact.php')) ?>">Enquire</a></li>
          </ul>
        </div>
        <div class="hg-footer__col">
          <p class="hg-footer__heading">About <?= e(site_name_short()) ?></p>
          <ul class="hg-footer__links">
            <li><a href="<?= e(base_url('about.php')) ?>">About us</a></li>
            <li><a href="<?= e(base_url('contact.php')) ?>">Contact</a></li>
            <li><a href="<?= e(base_url('faq.php')) ?>">FAQ</a></li>
          </ul>
        </div>
        <div class="hg-footer__col">
          <p class="hg-footer__heading">Contact</p>
          <ul class="hg-footer__links">
            <?php if ($phone !== ''): ?>
              <li><a href="tel:<?= e(preg_replace('/\D+/', '', $phone) ?: $phone) ?>"><?= e($phone) ?></a></li>
            <?php endif; ?>
            <?php if ($email !== ''): ?>
              <li><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
            <?php endif; ?>
            <?php if ($phone === '' && $email === ''): ?>
              <li><span class="hg-footer__muted">Set phone &amp; email in Admin → Settings</span></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
      <div class="hg-footer__bottom">
        <span>&copy; <?= e(date('Y')) ?> <?= e(site_name()) ?></span>
        <span class="hg-footer__legal">Terms &amp; Conditions · Privacy</span>
      </div>
    </footer>
  </div>
</div>
<?php require __DIR__ . '/partials/smartsupp.php'; ?>
</body>
</html>
