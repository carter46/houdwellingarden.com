<?php
/**
 * Shared enquiry form (Contact page + property detail sidebar).
 *
 * @var array{first_name:string,last_name:string,email:string,phone:string,interest:string,message:string} $inquiryForm
 * @var list<string> $inquiryErrors
 * @var bool $inquirySuccess
 * @var string $inquiryContext 'property'|'contact'
 * @var string $idPrefix
 * @var string $submitLabel
 * @var string|null $formHidden
 * @var string|null $inquiryIntro
 */

declare(strict_types=1);

$idPrefix = $idPrefix ?? 'inq';
$submitLabel = $submitLabel ?? 'Send message';
$inquiryContext = $inquiryContext ?? 'contact';
$formHidden = $formHidden ?? null;
$inquiryIntro = $inquiryIntro ?? null;
$options = inquiry_interest_options($inquiryContext);
$defaultInterest = $inquiryContext === 'property' ? 'Buy Now' : 'General Inquiry';
$selectedInterest = (string) ($inquiryForm['interest'] ?? '');
if ($selectedInterest === '') {
    $selectedInterest = $defaultInterest;
}
?>
<?php if ($inquirySuccess): ?>
  <div class="hg-success-panel">
    <?= hg_icon('check_circle') ?>
    <h3 class="hg-content-block__title">Inquiry received</h3>
    <p class="hg-prop-muted">Thank you — we will be in touch shortly.</p>
  </div>
<?php else: ?>
  <?php if ($inquiryIntro !== null && $inquiryIntro !== ''): ?>
    <p class="hg-prop-muted hg-inquiry-intro"><?= e($inquiryIntro) ?></p>
  <?php endif; ?>
  <?php foreach ($inquiryErrors as $err): ?>
    <p class="hg-prop-error"><?= e($err) ?></p>
  <?php endforeach; ?>
  <form method="post" action="<?= e($inquiryFormAction ?? '') ?>" class="hg-form-grid">
    <?= csrf_field() ?>
    <?= honeypot_field() ?>
    <?php if ($formHidden !== null && $formHidden !== ''): ?>
      <input type="hidden" name="form" value="<?= e($formHidden) ?>">
    <?php endif; ?>
    <div class="hg-form-grid hg-form-grid--2">
      <div class="hg-field">
        <label for="<?= e($idPrefix) ?>_first">First name *</label>
        <input id="<?= e($idPrefix) ?>_first" name="first_name" required type="text" value="<?= e((string) $inquiryForm['first_name']) ?>"/>
      </div>
      <div class="hg-field">
        <label for="<?= e($idPrefix) ?>_last">Last name *</label>
        <input id="<?= e($idPrefix) ?>_last" name="last_name" required type="text" value="<?= e((string) $inquiryForm['last_name']) ?>"/>
      </div>
    </div>
    <div class="hg-form-grid hg-form-grid--2">
      <div class="hg-field">
        <label for="<?= e($idPrefix) ?>_email">Email *</label>
        <input id="<?= e($idPrefix) ?>_email" name="email" required type="email" value="<?= e((string) $inquiryForm['email']) ?>"/>
      </div>
      <div class="hg-field">
        <label for="<?= e($idPrefix) ?>_phone">Phone</label>
        <input id="<?= e($idPrefix) ?>_phone" name="phone" type="tel" value="<?= e((string) $inquiryForm['phone']) ?>"/>
      </div>
    </div>
    <div class="hg-field">
      <label for="<?= e($idPrefix) ?>_interest"><?= $inquiryContext === 'property' ? 'Interest' : 'Area of interest' ?></label>
      <select id="<?= e($idPrefix) ?>_interest" name="interest">
        <?php foreach ($options as $opt): ?>
          <option value="<?= e($opt) ?>" <?= $selectedInterest === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="hg-field">
      <label for="<?= e($idPrefix) ?>_message">Message *</label>
      <textarea id="<?= e($idPrefix) ?>_message" name="message" required rows="6"><?= e((string) $inquiryForm['message']) ?></textarea>
    </div>
    <button type="submit" class="hg-btn"><?= e($submitLabel) ?></button>
    <?php if (smartsupp_chat_enabled()): ?>
      <p class="hg-prop-muted hg-inquiry-chat-note"><?= hg_icon('chat', 'hg-icon--sm') ?> Prefer live help? Use the chat bubble on this page — it works on every public page.</p>
    <?php endif; ?>
  </form>
<?php endif; ?>
