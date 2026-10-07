// Deals - Hell/Dunkel und Suche ueber alle [data-search]-Elemente (auch auf
// stats/ und in den Karten des eingebundenen Trackers).
(function () {
  "use strict";

  const $ = (id) => document.getElementById(id);
  function merken(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  function holen(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }

  // ---- Hell/Dunkel ----
  const t = holen("deals-thema");
  if (t) document.documentElement.dataset.theme = t;
  $("thema").addEventListener("click", () => {
    const dunkel = matchMedia("(prefers-color-scheme: dark)").matches;
    const jetzt = document.documentElement.dataset.theme || (dunkel ? "dark" : "light");
    const neu = jetzt === "dark" ? "light" : "dark";
    document.documentElement.dataset.theme = neu;
    merken("deals-thema", neu);
  });

  // ---- Suche ----
  const suche = $("suche");
  function filtern() {
    const q = suche.value.trim().toLowerCase();
    let treffer = 0;
    document.querySelectorAll("[data-search]").forEach((el) => {
      el.hidden = q !== "" && !el.dataset.search.includes(q);
      if (!el.hidden) treffer++;
    });
    const keine = $("keine-treffer");
    if (keine) keine.hidden = treffer > 0 || q === "";
  }
  suche.addEventListener("input", filtern);

  // Der Tracker (tracker/app.js) laedt seine Karten spaeter nach
  document.addEventListener("tracker-geladen", filtern);
})();
