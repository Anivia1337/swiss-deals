// Deals - Hell/Dunkel, Suche ueber alle [data-search]-Elemente (auch auf
// stats/ und in den Karten des eingebundenen Trackers) und die Laenderauswahl
// links (#ch / #ca / #de in der Adresse, Sichtbarkeit regelt stil.css).

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
    });
    // nur zaehlen, was im gewaehlten Land auch zu sehen ist
    document.querySelectorAll("[data-search]").forEach((el) => { if (el.offsetParent) treffer++; });
    const keine = $("keine-treffer");
    if (keine) keine.hidden = treffer > 0 || q === "";
  }
  suche.addEventListener("input", filtern);

  // ---- Laenderauswahl ----
  const laender = document.querySelectorAll(".laender a");
  if (!laender.length) {
    document.addEventListener("tracker-geladen", filtern);
    return;
  }
  const NAMEN = [...laender].map((a) => a.dataset.land);
  const gewaehlt = () => (NAMEN.includes(location.hash.slice(1)) ? location.hash.slice(1) : "ch");

  function zeigen() {
    const land = gewaehlt();
    document.body.dataset.land = land;
    laender.forEach((a) => {
      if (a.dataset.land === land) a.setAttribute("aria-current", "page"); else a.removeAttribute("aria-current");
      if (a.dataset.land !== "ch") {
        const n = document.querySelectorAll('#liste > li[data-land="' + a.dataset.land + '"]').length;
        a.querySelector(".n").textContent = n || "";
      }
    });
    const feld = $("eingabe");
    if (feld) feld.placeholder = land === "de" ? "Amazon.de product link" : "Best Buy SKU (e.g. 19837076) or product link";
    const leer = $("land-leer");
    if (leer) leer.hidden = land === "ch" || !!document.querySelector('#liste > li[data-land="' + land + '"]');
    filtern();
  }
  addEventListener("hashchange", zeigen);

  // Der Tracker (tracker/app.js) laedt seine Karten spaeter nach; ein neu
  // hinzugefuegtes Produkt aus einem anderen Land waehlt dieses Land aus.
  document.addEventListener("tracker-geladen", (ev) => {
    const land = ev.detail && ev.detail.land;
    if (land && land !== gewaehlt()) location.hash = land; else zeigen();
  });
  zeigen();
})();
