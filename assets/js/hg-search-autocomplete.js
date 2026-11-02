(function () {
  var inputs = document.querySelectorAll("[data-hg-location-input]");
  if (!inputs.length) return;

  var rootEl = document.querySelector("[data-hg-suggest-url]");
  var suggestUrl =
    (rootEl && rootEl.getAttribute("data-hg-suggest-url")) ||
    document.body.getAttribute("data-hg-suggest-url") ||
    "/api/search-suggest.php";

  inputs.forEach(function (input) {
    var wrap = input.closest("[data-hg-autocomplete]");
    if (!wrap) return;
    var list = wrap.querySelector("[data-hg-suggest-list]");
    if (!list) return;

    var timer = null;
    var lastQ = "";
    var activeIndex = -1;

    function closeList() {
      list.hidden = true;
      list.innerHTML = "";
      activeIndex = -1;
    }

    function render(data) {
      list.innerHTML = "";
      var locs = data.locations || [];
      var props = data.properties || [];
      if (locs.length === 0 && props.length === 0) {
        closeList();
        return;
      }

      if (locs.length) {
        var h = document.createElement("p");
        h.className = "hg-suggest__heading";
        h.textContent = "Locations";
        list.appendChild(h);
        locs.forEach(function (item) {
          var a = document.createElement("a");
          a.className = "hg-suggest__item";
          a.href = item.href;
          a.innerHTML =
            "<span class=\"hg-suggest__label\">" +
            escapeHtml(item.label) +
            "</span><span class=\"hg-suggest__meta\">" +
            (item.count ? item.count + " listings" : "View") +
            "</span>";
          list.appendChild(a);
        });
      }

      if (props.length) {
        var h2 = document.createElement("p");
        h2.className = "hg-suggest__heading";
        h2.textContent = "Properties";
        list.appendChild(h2);
        props.forEach(function (item) {
          var a = document.createElement("a");
          a.className = "hg-suggest__item hg-suggest__item--property";
          a.href = item.href;
          a.innerHTML =
            "<span class=\"hg-suggest__label\">" +
            escapeHtml(item.title || item.address) +
            "</span><span class=\"hg-suggest__meta\">" +
            escapeHtml(item.price || "") +
            "</span>";
          list.appendChild(a);
        });
      }

      list.hidden = false;
    }

    function escapeHtml(s) {
      return String(s)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/"/g, "&quot;");
    }

    function fetchSuggest(q) {
      if (q.length < 1) {
        closeList();
        return;
      }
      fetch(suggestUrl + "?q=" + encodeURIComponent(q), {
        headers: { Accept: "application/json" },
      })
        .then(function (r) {
          return r.json();
        })
        .then(render)
        .catch(function () {
          closeList();
        });
    }

    input.addEventListener("input", function () {
      var q = input.value.trim();
      if (q === lastQ) return;
      lastQ = q;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fetchSuggest(q);
      }, 180);
    });

    input.addEventListener("focus", function () {
      if (input.value.trim().length >= 1) fetchSuggest(input.value.trim());
    });

    document.addEventListener("click", function (e) {
      if (!wrap.contains(e.target)) closeList();
    });

    input.addEventListener("keydown", function (e) {
      var items = list.querySelectorAll(".hg-suggest__item");
      if (list.hidden || !items.length) return;
      if (e.key === "ArrowDown") {
        e.preventDefault();
        activeIndex = Math.min(activeIndex + 1, items.length - 1);
        items[activeIndex].focus();
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        activeIndex = Math.max(activeIndex - 1, 0);
        items[activeIndex].focus();
      } else if (e.key === "Escape") {
        closeList();
      }
    });
  });
})();
