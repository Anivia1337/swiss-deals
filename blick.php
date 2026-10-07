<?php
/**
 * Ergaenzung zum alldeals-Scraper: liest Tages- und Wochendeal direkt von
 * box.blick.ch/deals und legt sie im Format des Scrapers in
 * data/blick-YYYY-MM-DD.json ab. tag_laden() (includes/functions.php) fuellt
 * damit Eintraege, die in der Scraper-Datei fehlen oder leer sind.
 *
 * Grund: seit Oktober 2026 liefert der Scraper nur noch den Wochendeal.
 *
 *   php blick.php    (stuendlich per Cron)
 */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/includes/functions.php';

$c = curl_init('https://box.blick.ch/deals');
curl_setopt_array($c, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_ENCODING       => '',
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36',
    CURLOPT_HTTPHEADER     => ['Accept-Language: de-CH,de;q=0.9'],
]);
$html = (string) curl_exec($c);
if (curl_getinfo($c, CURLINFO_HTTP_CODE) !== 200 || $html === '') {
    fwrite(STDERR, "blick.php: box.blick.ch antwortet nicht\n");
    exit(1);
}

// Abschnitt <div id="tagesdeal"> bzw. "wochendeal" bis zum naechsten Abschnitt
function abschnitt(string $html, string $id): ?array
{
    if (!preg_match('#id="' . $id . '"(.*?)(?:id="(?:tagesdeal|wochendeal)"|</main>)#s', $html, $m)) return null;
    $b = $m[1];
    $feld = fn(string $muster) => preg_match($muster, $b, $t) ? trim(html_entity_decode(strip_tags($t[1]))) : null;

    $name = $feld('#class="deal__name">(.*?)</span>#s');
    if (!$name) return null;
    $bild = $feld('#data-dealproductimage.*?data-src="([^"]+)"#s');
    return [
        'url'          => $feld('#class="deal__link[^"]*" href="([^"]+)"#') ?? 'https://box.blick.ch/deals',
        'title'        => $name,
        'subtitle'     => '',
        'new_price'    => $feld('#data-dealprice>(.*?)</span>#s'),
        'old_price'    => $feld('#data-dealregularprice>(.*?)</span>#s'),
        'availability' => ($feld('#class="dealstripe__amount">(.*?)</span>#s') ?? '0%'),
        'image'        => $bild ? (str_starts_with($bild, 'http') ? $bild : 'https://box.blick.ch' . $bild) : '',
        'timestamp'    => (int) (microtime(true) * 1000),
    ];
}

$deals = array_filter([
    'blick'        => abschnitt($html, 'tagesdeal'),
    'blick_weekly' => abschnitt($html, 'wochendeal'),
]);
if (!$deals) {
    fwrite(STDERR, "blick.php: keine Deals gefunden - Seitenaufbau geaendert?\n");
    exit(1);
}

if (!is_dir(ERGAENZUNG_DIR)) mkdir(ERGAENZUNG_DIR, 0770, true);
file_put_contents(ERGAENZUNG_DIR . '/blick-' . date('Y-m-d') . '.json',
    json_encode($deals, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
echo implode(', ', array_map(fn($k, $d) => "$k: {$d['title']} {$d['new_price']}", array_keys($deals), $deals)), "\n";
