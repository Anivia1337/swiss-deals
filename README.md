# Swiss Deals

Shows today's Swiss daily deals — Digitec, Galaxus, daydeal (all categories),
Blick and 20 Minuten — on one page, with discount, remaining stock and the
previous price, plus a searchable history of every deal seen so far.
Plain PHP, no framework, no database, English UI, light/dark theme.

Based on [herzkadani/alldeals](https://github.com/herzkadani/alldeals): the
page reads the same daily JSON files its scraper writes. The front end here is
a rewrite (new layout, robust price parsing for Swiss notations like
`1'399.–`, calculated discounts, fallback to the last day with data).

## How it works

- A scraper (not part of this repository — see alldeals) writes one file per
  day to `DEALS_DIR` (`/deals/deals-YYYY-MM-DD.json` by default, set in
  `includes/functions.php`).
- `index.php` shows the newest file that actually contains deals, up to three
  days back. `?demo=1` uses `dev-fixtures.json` when there is no data.
- `stats/` lists every deal of the last 7/30/90 days or all days, sortable and
  searchable.
- Shops can be hidden with `AUSGEBLENDET` in `includes/functions.php`
  (MediaMarkt is hidden because its data isn't reliable).
- If a `tracker/` folder with
  [Anivia1337/price-tracker](https://github.com/Anivia1337/price-tracker)
  exists, its section (tracked prices from Canadian shops) is shown below the
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
| `assets/img/` | Shop logos |
| `brandcolors.json`, `dev-fixtures.json` | Shop colours and demo data from alldeals |

## License

The original alldeals project has no license; ask its author before reusing
the parts that come from it (data format, demo data, logos).
