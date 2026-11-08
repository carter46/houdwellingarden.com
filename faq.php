<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/hougarden-shell.php';

$pageTitle = 'FAQ — ' . site_name();
hougarden_use_public_shell('about');
require __DIR__ . '/includes/header-hougarden.php';

$faqGroups = faq_groups_public();
?>
<div class="hg-page-head">
  <p class="hg-page-head__eyebrow">Help</p>
  <h1 class="hg-page-head__title">Frequently asked questions</h1>
  <p class="hg-page-head__lead">Answers about listings, inspections, and enquiries on <?= e(site_name()) ?>.</p>
</div>

<div class="hg-faq-wrap">
  <?php foreach ($faqGroups as $group): ?>
    <?php
      $groupTitle = trim((string) ($group['title'] ?? ''));
      $items = $group['items'] ?? [];
      if ($groupTitle === '' || !is_array($items) || $items === []) {
          continue;
      }
    ?>
    <section class="hg-faq-group">
      <h2 class="hg-faq-group__title"><?= e($groupTitle) ?></h2>
      <?php foreach ($items as $faq): ?>
        <?php
          $q = trim((string) ($faq['q'] ?? ''));
          $a = trim((string) ($faq['a'] ?? ''));
          if ($q === '' || $a === '') {
              continue;
          }
        ?>
        <details class="hg-faq-item">
          <summary>
            <span><?= e($q) ?></span>
            <span class="hg-faq-item__icon" aria-hidden="true">
              <?= hg_icon('add', '', ['style' => 'width:18px;height:18px']) ?>
            </span>
          </summary>
          <div class="hg-faq-item__body"><?= nl2br(e($a)) ?></div>
        </details>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>

  <div class="hg-faq-footer">
    <p class="hg-prop-muted">Still have questions?</p>
    <a href="<?= e(base_url('contact.php')) ?>" class="hg-btn">Contact us</a>
  </div>
</div>
<?php require __DIR__ . '/includes/footer-hougarden.php';
