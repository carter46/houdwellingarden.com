<?php
/**
 * Contact Us — form posts to inquiries + admin email notify.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/hougarden-shell.php';

$pageTitle = 'Contact — ' . site_name();
hougarden_use_public_shell('ask');

$propertySlug = trim((string) ($_GET['property'] ?? ''));
$linkedProperty = null;
if ($propertySlug !== '') {
    try {
        $linkedProperty = property_find_public_by_slug($propertySlug);
    } catch (Throwable $e) {
        $linkedProperty = null;
        app_log('contact', 'linked property lookup: ' . $e->getMessage());
    }
}

$errors = [];
$success = false;
$askSubject = trim((string) ($_GET['subject'] ?? ''));
$form = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'interest' => $linkedProperty ? 'Request Details' : 'General Inquiry',
    'message' => $askSubject !== '' ? $askSubject . "\n\n" : '',
];

$phone = site_phone();
$emailContact = site_email();
$offices = [];
try {
    $offices = offices_list_public();
} catch (Throwable $e) {
    $offices = [];
    app_log('contact', 'offices list: ' . $e->getMessage());
}

if (is_post()) {
    if (!csrf_verify()) {
        $errors[] = 'Invalid security token. Please try again.';
    } elseif (honeypot_tripped()) {
        $success = true;
    } else {
        $max = (int) app_config('security.inquiry_max_per_hour', 5);
        $limit = rate_limit_hit('inquiry', $max, 3600);
        if (!$limit['allowed']) {
            $errors[] = 'Too many messages sent. Please try again later.';
        } else {
            $form = array_merge($form, [
                'first_name' => (string) ($_POST['first_name'] ?? ''),
                'last_name' => (string) ($_POST['last_name'] ?? ''),
                'email' => (string) ($_POST['email'] ?? ''),
                'phone' => (string) ($_POST['phone'] ?? ''),
                'interest' => (string) ($_POST['interest'] ?? ''),
                'message' => (string) ($_POST['message'] ?? ''),
            ]);
            $type = $linkedProperty ? 'property_inquiry' : 'contact';
            $payload = $form;
            $payload['property_id'] = $linkedProperty ? (int) $linkedProperty['id'] : null;
            $validated = inquiry_validate($payload, $type);
            $errors = $validated['errors'];
            if ($validated['ok']) {
                try {
                    $id = inquiry_create($validated['data']);
                    $inquiry = inquiry_find($id) ?? array_merge($validated['data'], ['id' => $id]);
                    inquiry_notify_admin($inquiry);
                    inquiry_ack_client($inquiry);
                    $success = true;
                    $form = [
                        'first_name' => '',
                        'last_name' => '',
                        'email' => '',
                        'phone' => '',
                        'interest' => 'General Inquiry',
                        'message' => '',
                    ];
                } catch (Throwable $e) {
                    app_log('contact', 'inquiry: ' . $e->getMessage());
                    $errors[] = 'Could not send your message. Please try again shortly.';
                }
            }
        }
    }
}

require __DIR__ . '/includes/header-hougarden.php';
?>
<div class="hg-page-head">
  <p class="hg-page-head__eyebrow">Contact</p>
  <h1 class="hg-page-head__title">Connect with our team</h1>
  <p class="hg-page-head__lead">Reach <?= e(site_name()) ?>. Messages are stored securely and emailed to our team. Phone and email below are managed in Admin → Settings (same as the site footer).</p>
</div>

<div class="hg-content-layout hg-content-layout--contact">
  <div class="hg-content-sidebar">
    <div class="hg-panel" style="margin-bottom:1.25rem;">
      <h2 class="hg-content-block__title">Direct contact</h2>
      <ul class="hg-contact-list">
        <?php if ($phone !== ''): ?>
          <li>
            <span class="hg-contact-list__label">Phone</span>
            <a href="tel:<?= e(preg_replace('/\D+/', '', $phone) ?: $phone) ?>"><?= e($phone) ?></a>
          </li>
        <?php endif; ?>
        <?php if ($emailContact !== ''): ?>
          <li>
            <span class="hg-contact-list__label">Email</span>
            <a href="mailto:<?= e($emailContact) ?>"><?= e($emailContact) ?></a>
          </li>
        <?php endif; ?>
        <?php if ($phone === '' && $emailContact === ''): ?>
          <li><span class="hg-prop-muted">Add public phone and email in Admin → Settings.</span></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="hg-panel">
      <h2 class="hg-content-block__title">Offices</h2>
      <?php if ($offices === []): ?>
        <p class="hg-prop-muted">Office locations appear here when added in the database.</p>
      <?php else: ?>
        <div class="hg-office-list">
          <?php foreach ($offices as $office): ?>
            <div class="hg-office-item">
              <p class="hg-office-item__name"><?= e((string) $office['name']) ?></p>
              <p class="hg-office-item__addr">
                <?php if (!empty($office['address_line'])): ?>
                  <?= e((string) $office['address_line']) ?><br/>
                <?php endif; ?>
                <?= e(trim(($office['city'] ?? '') . (($office['region'] ?? '') !== '' ? ' · ' . $office['region'] : ''))) ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="hg-content-main">
    <div class="hg-panel">
      <h2 class="hg-content-block__title">Send a message</h2>

      <?php if ($linkedProperty): ?>
        <p class="hg-contact-list__label">Regarding</p>
        <p style="margin:0 0 1rem;">
          <a href="<?= e(base_url('property.php?slug=' . rawurlencode((string) $linkedProperty['slug']))) ?>"><?= e((string) $linkedProperty['title']) ?></a>
        </p>
      <?php endif; ?>

      <?php
        $inquiryForm = $form;
        $inquiryErrors = $errors;
        $inquirySuccess = $success;
        $inquiryContext = 'contact';
        $idPrefix = 'contact';
        $submitLabel = 'Send message';
        $formHidden = null;
        $inquiryFormAction = '';
        $inquiryIntro = 'Fill in the form and a team member will respond shortly.';
        require __DIR__ . '/includes/partials/inquiry-form-hougarden.php';
      ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer-hougarden.php';
