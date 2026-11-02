<?php
declare(strict_types=1);
?>
</main>
</div>
<script>
(function () {
  var body = document.body;
  var navToggle = document.querySelector('.admin-nav-toggle');
  var navBackdrop = document.querySelector('.admin-nav-backdrop');
  var sidebar = document.getElementById('admin-sidebar');

  function setNavOpen(open) {
    if (!navToggle) return;
    body.classList.toggle('admin-nav-open', open);
    navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    navToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    if (navBackdrop) {
      navBackdrop.hidden = !open;
    }
  }

  if (navToggle) {
    navToggle.addEventListener('click', function () {
      setNavOpen(!body.classList.contains('admin-nav-open'));
    });
  }
  if (navBackdrop) {
    navBackdrop.addEventListener('click', function () {
      setNavOpen(false);
    });
  }
  if (sidebar) {
    sidebar.querySelectorAll('.admin-sidebar__link').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.matchMedia('(max-width: 899px)').matches) {
          setNavOpen(false);
        }
      });
    });
  }

  document.querySelectorAll('.admin-property-card__head').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var panelId = btn.getAttribute('aria-controls');
      var panel = panelId ? document.getElementById(panelId) : null;
      if (!panel) return;
      var open = panel.hidden;
      panel.hidden = !open;
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.classList.toggle('is-open', open);
    });
  });

  function closeMenus(exceptPanel) {
    document.querySelectorAll('.admin-menu__panel').forEach(function (panel) {
      if (panel !== exceptPanel) {
        panel.hidden = true;
        var menu = panel.closest('.admin-menu');
        if (menu) menu.classList.remove('is-open');
      }
    });
  }
  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('.admin-menu__toggle');
    if (toggle) {
      e.preventDefault();
      e.stopPropagation();
      var menu = toggle.closest('.admin-menu');
      var panel = menu ? menu.querySelector('.admin-menu__panel') : null;
      if (!panel) return;
      var willOpen = panel.hidden;
      closeMenus();
      if (willOpen) {
        panel.hidden = false;
        menu.classList.add('is-open');
      }
      return;
    }
    if (!e.target.closest('.admin-menu')) closeMenus();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeMenus();
      document.querySelectorAll('.admin-modal.is-open').forEach(function (m) {
        m.classList.remove('is-open');
      });
    }
  });
  document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-modal-open');
      var modal = id ? document.getElementById(id) : null;
      if (modal) modal.classList.add('is-open');
    });
  });
  document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var modal = btn.closest('.admin-modal');
      if (modal) modal.classList.remove('is-open');
    });
  });
})();
</script>
</body>
</html>
