<?php
/**
 * HouGarden-style hero search — Ask | location | estimate.
 */

declare(strict_types=1);

$action = $searchAction ?? base_url('properties.php');
$location = (string) ($_GET['location'] ?? '');
$estimateLocation = (string) ($_GET['estimate_location'] ?? '');
$defaultTab = $searchDefaultTab ?? 'estimate';
$searchSimpleHome = !empty($searchSimpleHome);
?>
<?php if ($searchSimpleHome): ?>
<div class="hg-search hg-search--simple">
  <form method="get" action="<?= e($action) ?>" class="hg-search-bar hg-search-bar--pill">
    <?= hg_icon('search', 'hg-search-bar__icon') ?>
    <div class="hg-search-input-wrap" data-hg-autocomplete>
      <input type="search" name="location" id="hg-location" value="<?= e($location) ?>" placeholder="Enter an address, neighborhood, city, or ZIP" autocomplete="off" aria-label="Search" data-hg-location-input aria-autocomplete="list" aria-controls="hg-location-suggest"/>
      <div class="hg-suggest" id="hg-location-suggest" data-hg-suggest-list hidden role="listbox"></div>
    </div>
    <button type="submit" class="hg-search-submit hg-search-submit--icon" aria-label="Search">
      <?= hg_icon('search') ?>
    </button>
  </form>
</div>
<script src="<?= e(asset_url('assets/js/hg-search-autocomplete.js?v=2')) ?>" defer></script>
<?php return; endif; ?>
<div class="hg-search" data-hg-search data-hg-default-tab="<?= e($defaultTab) ?>">
  <div class="hg-search-tabs" role="tablist" aria-label="Search mode">
    <button type="button" class="hg-search-tab<?= $defaultTab === 'ask' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $defaultTab === 'ask' ? 'true' : 'false' ?>" data-hg-tab="ask" id="hg-tab-ask">
      <span class="hg-search-tab__mark" aria-hidden="true">h</span>
      <span>Ask <?= e(site_name_short()) ?></span>
    </button>
    <button type="button" class="hg-search-tab<?= $defaultTab === 'location' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $defaultTab === 'location' ? 'true' : 'false' ?>" data-hg-tab="location" id="hg-tab-location">
      Search by Location
    </button>
    <button type="button" class="hg-search-tab<?= $defaultTab === 'estimate' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $defaultTab === 'estimate' ? 'true' : 'false' ?>" data-hg-tab="estimate" id="hg-tab-estimate">
      Get an Estimate
    </button>
  </div>

  <div class="hg-search-panel" role="tabpanel" aria-labelledby="hg-tab-ask" data-hg-panel="ask"<?= $defaultTab !== 'ask' ? ' hidden' : '' ?>>
    <form method="get" action="<?= e(base_url('contact.php')) ?>" class="hg-search-bar">
      <?= hg_icon('chat', 'hg-search-bar__icon') ?>
      <input type="hidden" name="subject" value="Ask HouGarden"/>
      <input type="text" name="message" placeholder="Ask about suburbs, schools, or a property…" aria-label="Your question"/>
      <button type="submit" class="hg-search-submit">Ask</button>
    </form>
  </div>

  <div class="hg-search-panel" role="tabpanel" aria-labelledby="hg-tab-location" data-hg-panel="location"<?= $defaultTab !== 'location' ? ' hidden' : '' ?>>
    <form method="get" action="<?= e($action) ?>" class="hg-search-bar">
      <?= hg_icon('search', 'hg-search-bar__icon') ?>
      <div class="hg-search-input-wrap" data-hg-autocomplete>
        <input type="search" name="location" id="hg-location" value="<?= e($location) ?>" placeholder="Suburb, address or location…" autocomplete="off" aria-label="Location" data-hg-location-input aria-autocomplete="list" aria-controls="hg-location-suggest"/>
        <div class="hg-suggest" id="hg-location-suggest" data-hg-suggest-list hidden role="listbox"></div>
      </div>
      <a class="hg-search-filters" href="<?= e(base_url('properties.php')) ?>">
        <?= hg_icon('tune') ?>
        <span>Filters</span>
      </a>
      <button type="submit" class="hg-search-submit">Search</button>
    </form>
  </div>

  <div class="hg-search-panel" role="tabpanel" aria-labelledby="hg-tab-estimate" data-hg-panel="estimate"<?= $defaultTab !== 'estimate' ? ' hidden' : '' ?>>
    <form method="get" action="<?= e(base_url('contact.php')) ?>" class="hg-search-bar">
      <?= hg_icon('search', 'hg-search-bar__icon') ?>
      <input type="hidden" name="subject" value="Property estimate request"/>
      <input type="text" name="estimate_location" id="hg-estimate" value="<?= e($estimateLocation) ?>" placeholder="Type address to search…" aria-label="Address for estimate"/>
      <button type="submit" class="hg-search-submit">Search</button>
    </form>
  </div>
</div>
<script>
(function () {
  var root = document.querySelector("[data-hg-search]");
  if (!root) return;
  var tabs = root.querySelectorAll("[data-hg-tab]");
  var panels = root.querySelectorAll("[data-hg-panel]");
  function activate(id) {
    tabs.forEach(function (t) {
      var on = t.getAttribute("data-hg-tab") === id;
      t.classList.toggle("is-active", on);
      t.setAttribute("aria-selected", on ? "true" : "false");
    });
    panels.forEach(function (p) {
      var show = p.getAttribute("data-hg-panel") === id;
      if (show) p.removeAttribute("hidden");
      else p.setAttribute("hidden", "");
    });
  }
  tabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
      activate(tab.getAttribute("data-hg-tab"));
    });
  });
  var def = root.getAttribute("data-hg-default-tab");
  if (def) activate(def);
})();
</script>
<script src="<?= e(asset_url('assets/js/hg-search-autocomplete.js?v=2')) ?>" defer></script>
