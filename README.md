# Swiss Deals

Shows today's Swiss daily deals — Digitec, Galaxus, daydeal (all categories),
Blick and 20 Minuten — on one page, with discount, remaining stock and the
previous price, plus a searchable history of every deal seen so far.
Plain PHP, no framework, no database, English UI, light/dark theme.

The deal data comes from the scraper of
[herzkadani/alldeals](https://github.com/herzkadani/alldeals), which writes one
JSON file per day. This repository is only the front end that reads those
files — new code, with robust price parsing for Swiss notations like
`1'399.–`, calculated discounts and a fallback to the last day with data.

## How it works

- A scraper (not part of this repository — see alldeals) writes one file per
  day to `DEALS_DIR` (`/deals/deals-YYYY-MM-DD.json` by default, set in
  `includes/functions.php`).
- `index.php` shows the newest file that actually contains deals, up to three
  days back.
- `stats/` lists every deal of the last 7/30/90 days or all days, sortable and
  searchable.
- Shops can be hidden with `AUSGEBLENDET` in `includes/functions.php`
  (MediaMarkt is hidden because its data isn't reliable).
- If a `tracker/` folder with
  [Anivia1337/price-tracker](https://github.com/Anivia1337/price-tracker)
  exists, its section (tracked prices from Canadian shops and Amazon.de) is shown below the
  Swiss deals.

## Setup

Requires PHP 8.1+. Put the folder on a PHP web server and point `DEALS_DIR` at
the scraper's output. On Apache, `.htaccess` sets a strict Content Security
Policy (no inline CSS/JS, no external fonts) and hides the repository files.

## Files

| File | Purpose |
|---|---|
| `index.php` | Today's deals (+ the price tracker, if present) |
| `stats/` | Deal history |
| `includes/functions.php` | Loading, price parsing, shop names |
| `app.js` | Light/dark toggle, search |
| `stil.css` | Styles |
| `assets/img/` | Shop logos (trademarks of the respective shops) |

## License

MIT for the code in this repository. The scraper is not included — see
alldeals for it.
