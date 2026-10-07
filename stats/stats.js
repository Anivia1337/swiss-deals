// Verlauf - Sortieren per Klick auf den Spaltenkopf. Suche und Hell/Dunkel
// kommen aus ../app.js.
(function () {
  "use strict";

  const tabelle = document.getElementById("tabelle");
  if (!tabelle) return;
  const tbody = tabelle.tBodies[0];
  const koepfe = [...tabelle.tHead.rows[0].cells];

  koepfe.forEach((th, spalte) => {
    th.querySelector("button").addEventListener("click", () => {
      const auf = th.getAttribute("aria-sort") === "descending";
      koepfe.forEach((k) => k.removeAttribute("aria-sort"));
      th.setAttribute("aria-sort", auf ? "ascending" : "descending");

      const zahl = th.dataset.typ === "zahl", r = auf ? 1 : -1;
      const wert = (tr) => tr.cells[spalte].dataset.wert;
      const zeilen = [...tbody.rows].sort((a, b) => {
        if (!zahl) return wert(a).localeCompare(wert(b)) * r;
        const x = parseFloat(wert(a)), y = parseFloat(wert(b));
        if (isNaN(x) || isNaN(y)) return isNaN(x) - isNaN(y);   // leere immer ans Ende
        return (x - y) * r;
      });
      tbody.append(...zeilen);
    });
  });
  koepfe[0].setAttribute("aria-sort", "descending");
})();
