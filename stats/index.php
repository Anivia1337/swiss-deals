<?php
/**
 * Deals - Verlauf aller Schweizer Tagesdeals aus DEALS_DIR, neueste zuerst.
 * Zeitraum ueber ?days= (Standard 30, 0 = alle), Suche und Sortierung im
 * Browser (../app.js, stats.js). Gestaltung aus ../stil.css.
 */

ini_set('display_errors', '0');
require_once __DIR__ . '/../includes/functions.php';

$days   = isset($_GET['days']) ? max(0, (int) $_GET['days']) : 30;

$tage = [];   // Tag => rohe Deals
$files = glob(DEALS_DIR . '/deals-*.json') ?: [];
rsort($files);
$available = count($files);
foreach ($days ? array_slice($files, 0, $days) : $files as $path) {
    if (preg_match('/deals-(\d{4}-\d{2}-\d{2})\.json$/', $path, $m)) {
        $tage[$m[1]] = tag_laden($m[1]);
    }
}

$rows = [];
foreach ($tage as $tag => $data) {
    foreach ($data as $key => $d) {
        if (empty($d) || !is_array($d) || in_array($key, AUSGEBLENDET, true)) continue;
        $r = normalize_deal((string) $key, $d, 'assets/img', __DIR__ . '/../assets/img');
        $r['day'] = $tag;
        $rows[] = $r;
    }
}

$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES);
$v = fn($f) => @filemtime(__DIR__ . '/../' . $f) ?: 1;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Deal history</title>
<meta name="color-scheme" content="light dark">
<link rel="stylesheet" href="/deals/stil.css?v=<?= $v('stil.css') ?>">
<script src="/deals/app.js?v=<?= $v('app.js') ?>" defer></script>
<script src="/deals/stats/stats.js?v=<?= @filemtime(__DIR__ . '/stats.js') ?: 1 ?>" defer></script>
</head>
<body>

<svg class="sinnbilder" aria-hidden="true" focusable="false">
  <symbol id="s-zurueck" viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></symbol>
  <symbol id="s-thema" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 3v18a9 9 0 0 0 0-18z" fill="currentColor" stroke="none"/></symbol>
  <symbol id="s-suche" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></symbol>
</svg>

<header class="kopf">
  <div class="marke">
    <a class="zurueck" href="/deals/" title="Back to deals"><svg class="sym"><use href="#s-zurueck"/></svg><span class="nurlesbar">Back to deals</span></a>
    <h1>Deal history</h1>
  </div>
  <label class="suche">
    <svg class="sym"><use href="#s-suche"/></svg>
    <span class="nurlesbar">Search</span>
    <input type="search" id="suche" placeholder="Search deals and shops" autocomplete="off">
  </label>
  <div class="kopf-rechts">
    <button type="button" class="rundknopf" id="thema" title="Light or dark appearance"><svg class="sym"><use href="#s-thema"/></svg><span class="nurlesbar">Switch appearance</span></button>
  </div>
</header>

<main class="haupt">
  <div class="bereich-kopf">
    <h2>Switzerland <span class="zahl"><?= count($rows) ?></span></h2>
    <p>Every Swiss daily deal seen so far, newest first.</p>
  </div>

  <nav class="zeitraum" aria-label="Period">
    <?php foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days', 0 => "All ($available days)"] as $n => $label): ?>
      <a href="?days=<?= $n ?>"<?= $days === $n ? ' aria-current="page"' : '' ?>><?= $label ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if (!$rows): ?>
    <p class="leer">No history yet.</p>
  <?php else: ?>
    <div class="tabelle-rahmen">
      <table class="tabelle" id="tabelle">
        <thead>
          <tr>
            <th data-typ="text"><button type="button">Day</button></th>
            <th data-typ="text"><button type="button">Shop</button></th>
            <th data-typ="text"><button type="button">Deal</button></th>
            <th data-typ="zahl" class="z"><button type="button">Price</button></th>
            <th data-typ="zahl" class="z breit"><button type="button">Before</button></th>
            <th data-typ="zahl" class="z"><button type="button">Discount</button></th>
            <th data-typ="zahl" class="breit"><button type="button">Left</button></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr data-search="<?= $h(mb_strtolower("{$r['title']} {$r['subtitle']} {$r['shop']}")) ?>">
            <td class="z" data-wert="<?= $h($r['day']) ?>"><?= $h($r['day']) ?></td>
            <td data-wert="<?= $h($r['shop']) ?>"><span class="shop"><?php if ($r['logo']): ?><img src="/deals/<?= $h($r['logo']) ?>" alt=""><?php endif; ?><?= $h($r['shop']) ?></span></td>
            <td data-wert="<?= $h($r['title']) ?>"><?= $h($r['title']) ?><?php if ($r['subtitle']): ?> <span class="leise">&middot; <?= $h($r['subtitle']) ?></span><?php endif; ?></td>
            <td class="z" data-wert="<?= $r['new_price'] ?? '' ?>"><?= $r['new_price'] !== null ? 'CHF ' . $h($r['new_price_fmt']) : '&ndash;' ?></td>
            <td class="z breit leise" data-wert="<?= $r['old_price'] ?? '' ?>"><?= $r['old_price'] !== null ? 'CHF ' . $h($r['old_price_fmt']) : '&ndash;' ?></td>
            <td class="z" data-wert="<?= $r['discount'] ?? '' ?>"><?= $r['discount'] !== null ? '&minus;' . $r['discount'] . '%' : '&ndash;' ?></td>
            <td class="breit" data-wert="<?= (int) $r['availability'] ?>"><progress class="vorrat" max="100" value="<?= (int) $r['availability'] ?>" title="<?= (int) $r['availability'] ?>%"></progress></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="leer" id="keine-treffer" hidden>No deals match your search.</p>
  <?php endif; ?>
</main>

</body>
</html>
