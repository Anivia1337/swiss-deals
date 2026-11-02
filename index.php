<?php
/**
 * Deals - Schweizer Tagesangebote (Scraper-Dateien in DEALS_DIR) plus der
 * Preis-Tracker fuer kanadische Shops (tracker/, eigenes Repository - wird
 * eingebunden, wenn vorhanden).
 *
 * Gestaltung wie /pdf (Fluent), englische Oberflaeche. Kein Inline-CSS und
 * kein Inline-JS - die CSP in .htaccess erlaubt beides nicht.
 */

ini_set('display_errors', '0');
require_once __DIR__ . '/includes/functions.php';

const FALLBACK_DAYS = 3;

// Laender des Preis-Trackers (tracker/, eigenes Repository): Kuerzel =>
// [Name, Beschreibung, Platzhalter im Eingabefeld]. Das Kuerzel muss zu LAND
// in tracker/app.js passen.
const LAENDER = [
    'ca' => ['Canada', 'Tracked prices from Best Buy Canada, Canada Computers and Amazon.ca, in CAD', 'Best Buy SKU (e.g. 19837076) or product link'],
    'us' => ['United States', 'Tracked prices from Amazon.com, in USD', 'Amazon.com product link'],
    'mx' => ['Mexico', 'Tracked prices from Amazon.com.mx, in MXN', 'Amazon.com.mx product link'],
    'de' => ['Germany', 'Tracked prices from Amazon.de, in EUR', 'Amazon.de product link'],
    'fr' => ['France', 'Tracked prices from Amazon.fr, in EUR', 'Amazon.fr product link'],
    'it' => ['Italy', 'Tracked prices from Amazon.it, in EUR', 'Amazon.it product link'],
    'nl' => ['Netherlands', 'Tracked prices from Amazon.nl, in EUR', 'Amazon.nl product link'],
    'be' => ['Belgium', 'Tracked prices from Amazon.com.be, in EUR', 'Amazon.com.be product link'],
    'se' => ['Sweden', 'Tracked prices from Amazon.se, in SEK', 'Amazon.se product link'],
];

// Neueste Tagesdatei mit mindestens einem Deal. Eine leere Datei (Scraper
// lief, fand aber nichts) zaehlt nicht - sonst stand die Seite leer da,
// obwohl gestern noch Deals vorlagen.
function load_deals(): array
{
    for ($i = 0; $i <= FALLBACK_DAYS; $i++) {
        $data = tag_laden(date('Y-m-d', strtotime("-{$i} days")));
        if (array_filter($data)) return [$data, $i === 0];
    }
    return [[], false];
}

[$raw, $isToday] = load_deals();
$deals = [];
foreach ($raw as $key => $d) {
    if (!empty($d) && is_array($d) && !in_array($key, AUSGEBLENDET, true)) {
        $deals[] = normalize_deal((string) $key, $d, 'assets/img', __DIR__ . '/assets/img');
    }
}
// verfuegbare zuerst, dann nach Verfuegbarkeit
usort($deals, fn($a, $b) => [$a['expired'], $b['availability']] <=> [$b['expired'], $a['availability']]);

$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES);
$tracker = is_readable(__DIR__ . '/tracker/teil.php');
$v = fn($f) => @filemtime(__DIR__ . '/' . $f) ?: 1;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Deals</title>
<meta name="description" content="Today's Swiss daily deals (Digitec, Galaxus, daydeal, Blick, 20 Minuten) and tracked prices from Canadian shops and Amazon in nine countries.">
<meta name="color-scheme" content="light dark">
<meta property="og:type" content="website">
<meta property="og:title" content="Deals">
<meta property="og:description" content="Today's Swiss daily deals and tracked prices from Canadian shops and Amazon in nine countries.">
<meta property="og:url" content="https://anivia.ch/deals/">
<link rel="stylesheet" href="/deals/stil.css?v=<?= $v('stil.css') ?>">
<script src="/deals/app.js?v=<?= $v('app.js') ?>" defer></script>
<script src="/deals/tracker/app.js?v=<?= $v('tracker/app.js') ?>" defer></script>
</head>
<body>

<svg class="sinnbilder" aria-hidden="true" focusable="false">
  <symbol id="s-marke" viewBox="0 0 24 24"><path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/></symbol>
  <symbol id="s-thema" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 3v18a9 9 0 0 0 0-18z" fill="currentColor" stroke="none"/></symbol>
  <symbol id="s-suche" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></symbol>
  <symbol id="s-verlauf" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 6-6"/></symbol>
  <symbol id="s-uebersicht" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></symbol>
</svg>

<header class="kopf">
  <div class="marke">
    <span class="marke-zeichen"><svg class="sym"><use href="#s-marke"/></svg></span>
    <h1>Deals</h1>
  </div>
  <label class="suche">
    <svg class="sym"><use href="#s-suche"/></svg>
    <span class="nurlesbar">Search</span>
    <input type="search" id="suche" placeholder="Search deals and shops" autocomplete="off">
  </label>
  <div class="kopf-rechts">
    <a class="rundknopf" href="/start" title="Overview"><svg class="sym"><use href="#s-uebersicht"/></svg><span class="nurlesbar">Overview</span></a>
    <a class="rundknopf" href="/deals/stats/" title="History of Swiss deals"><svg class="sym"><use href="#s-verlauf"/></svg><span class="nurlesbar">History</span></a>
    <button type="button" class="rundknopf" id="thema" title="Light or dark appearance"><svg class="sym"><use href="#s-thema"/></svg><span class="nurlesbar">Switch appearance</span></button>
  </div>
</header>

<div class="rahmen">
<nav class="laender" aria-label="Country">
  <a href="#ch" data-land="ch"><span class="kuerzel">CH</span>Switzerland<span class="n"><?= count($deals) ?: '' ?></span></a>
<?php if ($tracker) foreach (LAENDER as $code => [$name]): ?>
  <a href="#<?= $code ?>" data-land="<?= $code ?>"><span class="kuerzel"><?= strtoupper($code) ?></span><?= $name ?><span class="n"></span></a>
<?php endforeach; ?>
</nav>

<main class="haupt">

  <section class="bereich land-ch" aria-labelledby="t-ch">
    <div class="bereich-kopf">
      <h2 id="t-ch">Switzerland <span class="zahl"><?= count($deals) ?></span></h2>
      <p>Today's daily deals from Digitec, Galaxus, daydeal, Blick and 20 Minuten.</p>
    </div>

    <?php if (!$isToday && $deals): ?>
      <p class="hinweisband">No fresh data for today yet &mdash; showing the latest available deals.</p>
    <?php endif; ?>

    <?php if (!$deals): ?>
      <p class="leer">No Swiss deals available right now. Check back later.</p>
    <?php else: ?>
      <ul class="raster">
        <?php foreach ($deals as $d): ?>
        <li class="karte deal<?= $d['expired'] ? ' aus' : '' ?>" data-search="<?= $h(mb_strtolower("{$d['title']} {$d['subtitle']} {$d['shop']} {$d['shop_sub']}")) ?>">
          <a class="bild" href="<?= $h($d['url']) ?>" target="_blank" rel="noopener">
            <?php if ($d['image']): ?><img src="<?= $h($d['image']) ?>" alt="" loading="lazy" referrerpolicy="no-referrer"><?php endif; ?>
            <?php if ($d['discount']): ?><span class="rabatt">&minus;<?= $d['discount'] ?>%</span><?php endif; ?>
            <?php if ($d['expired']): ?><span class="ausverkauft">Sold out</span><?php endif; ?>
          </a>
          <div class="info">
            <span class="shop">
              <?php if ($d['logo']): ?><img src="/deals/<?= $h($d['logo']) ?>" alt=""><?php endif; ?>
              <?= $h($d['shop']) ?><?php if ($d['shop_sub']): ?> <span class="shop-sub">&middot; <?= $h($d['shop_sub']) ?></span><?php endif; ?>
            </span>
            <a class="titel" href="<?= $h($d['url']) ?>" target="_blank" rel="noopener"><?= $h($d['title']) ?></a>
            <?php if ($d['subtitle']): ?><span class="unter"><?= $h($d['subtitle']) ?></span><?php endif; ?>
          </div>
          <progress class="vorrat" max="100" value="<?= (int) $d['availability'] ?>" title="<?= (int) $d['availability'] ?>% available"></progress>
          <b class="jetzt"><?= $d['new_price'] !== null ? 'CHF ' . $h($d['new_price_fmt']) : '&ndash;' ?>
            <?php if ($d['old_price'] !== null): ?><s class="regulaer">CHF <?= $h($d['old_price_fmt']) ?></s><?php endif; ?></b>
          <span class="stand"><?= (int) $d['availability'] ?>% left &middot; <?= $h($d['updated']) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <?php if ($tracker): ?>
  <?php foreach (LAENDER as $code => [$name, $text, $platzhalter]): ?>
  <div class="bereich-kopf land-kopf" data-land="<?= $code ?>" data-platzhalter="<?= $h($platzhalter) ?>">
    <h2><?= $name ?></h2>
    <p><?= $text ?> &mdash; checked every hour.</p>
  </div>
  <?php endforeach; ?>
  <?php include __DIR__ . '/tracker/teil.php'; ?>
  <p class="leer" id="land-leer" hidden>Nothing tracked here yet. Add a product above.</p>
  <?php endif; ?>

  <p class="leer" id="keine-treffer" hidden>No deals match your search.</p>
</main>
</div>


</body>
</html>
