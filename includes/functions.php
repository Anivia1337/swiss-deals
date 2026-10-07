<?php
/**
 * Gemeinsame Helfer für AllDeals (anivia.ch edition).
 * Wird sowohl von der Hauptseite (index.php) als auch von der Stats-Seite
 * (stats/index.php) eingebunden, damit Preis-Parsing & Co. nicht doppelt
 * gepflegt werden müssen.
 */

date_default_timezone_set('Europe/Zurich');

// Fallback, falls die mbstring-Extension auf dem Server nicht aktiv ist
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($s) { return strtolower($s); }
}
if (!function_exists('mb_substr')) {
    function mb_substr($s, $start, $len = null) { return substr($s, $start, $len); }
}

const DEALS_DIR = '/deals'; // Ordner mit deals-YYYY-MM-DD.json

// Shops, die der Scraper zwar liefert, die aber nicht angezeigt werden.
// MediaMarkt: Daten stimmen nicht zuverlaessig (2026-10-04 ausgeblendet).
const AUSGEBLENDET = ['mediamarkt'];

// Wandelt Preis-Strings wie "1'399.–", "49.90", "83.–", 369 in eine Zahl um.
function parse_price($raw): ?float
{
    if ($raw === null || $raw === '') return null;
    $s = trim((string) $raw);
    $s = str_replace(["'", ' ', 'CHF', 'chf'], '', $s);
    // Schweizer Notation "49.–" / "73.-" (Rappen weggelassen) -> "49.00" / "73.00"
    $s = preg_replace('/\.[\-–]$/u', '.00', $s);
    $s = rtrim($s, '-–');
    $s = str_replace(',', '.', $s);
    if ($s === '' || !is_numeric($s)) return null;
    return (float) $s;
}

function format_chf(?float $amount): string
{
    if ($amount === null) return '–';
    return number_format($amount, $amount == floor($amount) ? 0 : 2, '.', "'");
}

function parse_percent($raw): int
{
    if (is_numeric($raw)) return (int) $raw;
    if (preg_match('/(\d+)/', (string) $raw, $m)) return (int) $m[1];
    return 0;
}

// Korrigierte relative Zeitangabe (Original lieferte immer "vor X Minuten",
// egal ob tatsächlich Stunden oder Tage vergangen waren)
function time_elapsed_string($timestamp_ms): string
{
    $seconds = max(0, time() - (int) round($timestamp_ms / 1000));

    if ($seconds < 60) return 'just now';
    $minutes = (int) floor($seconds / 60);
    if ($minutes < 60) return $minutes . ' min ago';
    $hours = (int) floor($minutes / 60);
    if ($hours < 24) return $hours . ' h ago';
    $days = (int) floor($hours / 24);
    return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
}

// Menschenlesbarer Shop-Name aus dem JSON-Key ableiten
function nice_shop_name(string $key): array
{
    $map = [
        'digitec'                      => ['Digitec', 'Daily Deal'],
        'galaxus'                      => ['Galaxus', 'Daily Deal'],
        'mediamarkt'                   => ['MediaMarkt', 'Deal'],
        'blick'                        => ['Blick', 'Deal of the day'],
        'blick_weekly'                 => ['Blick', 'Deal of the week'],
        'zmin'                         => ['20 Minuten', 'Offer of the day'],
        'zmin_weekly'                  => ['20 Minuten', 'Weekly offer'],
        'daydeal_daily'                => ['daydeal', 'Daily deal'],
        'daydeal_weekly'               => ['daydeal', 'Weekly deal'],
        'daydeal_tagesdeal'            => ['daydeal', 'Daily deal'],
        'daydeal_it_multimedia'        => ['daydeal', 'IT & Multimedia'],
        'daydeal_haushalt_wohnen'      => ['daydeal', 'Home & Living'],
        'daydeal_supermarkt_drogerie'  => ['daydeal', 'Groceries & Drugstore'],
        'daydeal_familie_baby'         => ['daydeal', 'Family & Baby'],
        'daydeal_baumarkt_hobby'       => ['daydeal', 'DIY & Hobby'],
        'daydeal_sport_freizeit'       => ['daydeal', 'Sport & Leisure'],
    ];
    if (isset($map[$key])) return $map[$key];
    $pretty = ucwords(str_replace('_', ' ', $key));
    return [$pretty, ''];
}

// Pfad relativ zum aufrufenden Skript (index.php bzw. stats/index.php),
// deshalb wird die Logo-Basis als Parameter übergeben.
function logo_for(string $key, string $imgDir): string
{
    $path = $imgDir . '/' . $key . '.jpg';
    return is_readable($path) ? $path : '';
}

// Baut aus rohen JSON-Deal-Daten ein normalisiertes, gerechnetes Deal-Array.
function normalize_deal(string $key, array $d, array $brandcolors, string $imgWebDir, string $imgDiskDir): array
{
    $newPrice = parse_price($d['new_price'] ?? null);
    $oldPrice = parse_price($d['old_price'] ?? null);
    $availability = parse_percent($d['availability'] ?? 0);
    [$shopName, $shopSubline] = nice_shop_name($key);

    $discount = null;
    if ($newPrice !== null && $oldPrice !== null && $oldPrice > 0 && $oldPrice > $newPrice) {
        $discount = (int) round((1 - $newPrice / $oldPrice) * 100);
    }

    $logoPath = logo_for($key, $imgDiskDir);

    return [
        'key'          => $key,
        'shop'         => $shopName,
        'shop_sub'     => $shopSubline,
        'title'        => $d['title'] ?? '',
        'subtitle'     => $d['subtitle'] ?? '',
        'image'        => $d['image'] ?? '',
        'url'          => $d['url'] ?? '#',
        'new_price'    => $newPrice,
        'old_price'    => $oldPrice,
        'new_price_fmt'=> format_chf($newPrice),
        'old_price_fmt'=> format_chf($oldPrice),
        'discount'     => $discount,
        'availability' => $availability,
        'expired'      => $availability <= 0,
        'timestamp'    => $d['timestamp'] ?? 0,
        'updated'      => isset($d['timestamp']) ? time_elapsed_string($d['timestamp']) : '',
        'color'        => $brandcolors[$key] ?? '#C4692A',
        'logo'         => $logoPath ? $imgWebDir . '/' . $key . '.jpg' : '',
    ];
}

function load_brandcolors(string $path): array
{
    if (is_readable($path)) {
        $data = json_decode(file_get_contents($path), true);
        if (is_array($data)) return $data;
    }
    return [];
}
