# Lead Ledger

A public catalogue of vintage Citadel / Games Workshop miniatures (1984–1992),
with an admin backend for maintaining it. Built to the design handoff in
[`design_handoff_lead_ledger/`](design_handoff_lead_ledger/README.md).

Plain PHP 8 and MySQL over PDO — no framework, no build step, no Composer —
because that is what one.com shared hosting runs happily. Every table it touches
carries the **`ll_`** prefix, so it can share a database with anything else.

## Setting it up on one.com

1. **Upload** everything in this repository into the webspace folder your domain
   serves from (usually `/www` or `/www/<domain>`).
2. **Create or pick a database** in the one.com control panel, under
   *Web hosting → MySQL/Database*. Note the hostname, database name and user.
3. **Copy `config.example.php` to `config.php`** on the server and fill in the
   four connection values. `config.php` is gitignored and blocked from HTTP —
   the password never enters this repository.
4. **Open `https://yourdomain/install.php`** and press *Create tables and write
   the sample catalogue*. (Press *Create empty tables only* if you would rather
   start from nothing.)
5. **Create the first account.** The first account made owns the catalogue and
   gets the admin screens; everyone after it is a collector.
6. **Delete `install.php`** from the server.

If you would rather create the tables by hand, paste [`schema.sql`](schema.sql)
into phpMyAdmin instead of step 4.

### Running it against one.com from your own machine

one.com keeps MySQL closed to the outside by default. Switch the database to
external access in the control panel first, then use the external hostname in
`config.php`.

## How it is laid out

| Path | What it is |
| --- | --- |
| `index.php` | Front controller — every route in one file |
| `src/` | `db` (PDO + the `ll_` prefix), `auth`, `repo` (all SQL), `upload`, `view`, `helpers` |
| `views/` | Plain PHP templates: `layout`, `public/`, `admin/`, `auth` |
| `assets/ds/styles.css` | The Modernist design system, ported unchanged from the handoff |
| `assets/app.css` | The project layer on top of it |
| `assets/app.js` | Optimistic ticks, dialogs, the drawer, drag-reordering |
| `uploads/` | Miniature photographs (four samples committed, the rest ignored) |
| `schema.sql` · `install.php` | The `ll_` tables, and the one-time installer |
| `design_handoff_lead_ledger/` | The original design bundle, kept for reference |

`src/` and `views/` are blocked by `.htaccess`; nothing under `uploads/` is ever
executed.

## Routes

| Route | |
| --- | --- |
| `GET /` | The sets index — every set, grouped by range |
| `GET /{range-slug}/{set-code}` | A set's photo grid |
| `GET POST /sign-in` | Sign in / create account (`?mode=up`) |
| `POST /sign-out` | |
| `GET /admin` | Ranges & sets |
| `GET /admin/sets/{id}` | A set's miniatures |
| `POST /api/collection/{miniature}` | Toggle owned — idempotent, also accepts `PUT` / `DELETE` |
| `POST /api/collection/sets/{set}` | Tick or clear a whole set in one request |
| `POST /admin/sets/{set}/reorder` | Persist a drag-reorder |

Everything works without JavaScript — the ticks, filters, density switcher and
admin forms are all real links and form posts. JavaScript upgrades them to
optimistic toggles, modal dialogs and drag-and-drop.

## The handoff's known gaps, and what happened to them

| Gap | Now |
| --- | --- |
| Drawer Code/Name fields did not save | Real form, posts to `admin/miniature/save` |
| Photo dropzone was prototype-only | Real upload to `uploads/`, validated by image bytes, old file removed on replace |
| Owned ticks did not persist | `ll_ownership`, private per user, idempotent both ways |
| Headline counts were invented | Derived in one aggregate query — no N+1 |
| No Esc-to-close | Esc closes the topmost dialog or the drawer |
| No search | Still deliberately absent, as specified |

## Two judgment calls worth knowing about

- **The header's Admin button only appears for the catalogue owner.** The design
  shows it for anyone signed in, but a collector has nothing to manage.
- **"Tick whole set" sits in the set page's filter bar.** The handoff puts it in
  "a set band header", which this layout does not have.

## Requirements

PHP 8.0+ with PDO MySQL and GD-free image inspection (`getimagesize`), MySQL 5.7+
or MariaDB 10.2+ (the schema uses `InnoDB` foreign keys for the delete cascades),
and `mod_rewrite`.
