# Lead Ledger

A public catalogue of vintage Citadel / Games Workshop miniatures (1984–1992),
with an admin backend for maintaining it. Built to the design handoff in
[`design_handoff_miniatures_catalogue/`](design_handoff_miniatures_catalogue/README.md).

Plain PHP 8 and MySQL over PDO — no framework, no build step, no Composer —
because that is what one.com shared hosting runs happily. Every table it touches
carries the **`ll_`** prefix, so it can share a database with anything else.

## Setting it up on one.com

1. **Upload** everything in this repository into the webspace folder your domain
   serves from (usually `/www` or `/www/<domain>`). **Check that `.htaccess` and
   `uploads/.htaccess` actually arrived** — they are dotfiles, and file managers
   and FTP clients hide them by default, so they are easy to leave behind.
   Without the first one every route 404s.
2. **Create or pick a database** in the one.com control panel, under
   *Web hosting → MySQL/Database*. Note the hostname, database name and user.
3. **Create `config.php`** beside `index.php` on the server, filling in the four
   connection values. It is gitignored and blocked from HTTP, so the password
   never enters this repository.

   ```php
   <?php
   return [
       'db_host' => 'localhost',      // on one.com, not the external hostname
       'db_name' => 'CHANGEME',       // name and user are usually the same string
       'db_user' => 'CHANGEME',
       'db_pass' => 'CHANGEME',

       'db_prefix'     => 'll_',
       'site_name'     => 'Lead Ledger',
       'contact_email' => 'you@example.com',  // the address Wanted and For trade ask people to write to
       'pretty_urls'   => true,       // false if .htaccess cannot be used
       'debug'         => false,
   ];
   ```
4. **Open `https://yourdomain/install.php`** and press *Create tables and write
   the sample catalogue*. (Press *Create empty tables only* if you would rather
   start from nothing.)
5. **Create the first account.** The first account made owns the catalogue and
   gets the admin screens; everyone after it is a collector.
6. **Delete `install.php`** from the server.

If you would rather create the tables by hand, paste [`schema.sql`](schema.sql)
into phpMyAdmin instead of step 4.

### If the URLs 404 (a WordPress site at the domain root, for instance)

Clean URLs need Apache to actually read this app's `.htaccess`. Two things stop
that: the file never got uploaded (FTP clients and file managers routinely hide
dotfiles), or a rewrite further up the tree claims the request first.

Either way there is a way through that needs no rewriting at all — add this to
`config.php`:

```php
'pretty_urls' => false,
```

Routes then address the front controller directly — `/leadledger/index.php/sign-in`
— which nothing upstream can intercept. Stylesheets, scripts and photographs
keep their own plain paths.

`db_host` is **`localhost`** when the site runs on one.com — the database is on
the same machine, so the external hostname the control panel shows is not the
one to use. The database name and the user name are usually the same string.

### Running it against one.com from your own machine

one.com keeps MySQL closed to the outside by default. Switch the database to
external access in the control panel first, and *then* use the external
hostname it shows.

## How it is laid out

| Path | What it is |
| --- | --- |
| `index.php` | Front controller — every route in one file |
| `src/` | `db` (PDO + the `ll_` prefix), `auth`, `repo` (all SQL), `upload`, `view`, `helpers` |
| `views/` | Plain PHP templates: `layout`, `public/`, `admin/`, `auth`. `public/_plate.php` is the miniature photograph and its controls, shared by the set grid, the wanted page and the trade list |
| `assets/ds/styles.css` | The Modernist design system, ported unchanged from the handoff |
| `assets/app.css` | The project layer on top of it |
| `assets/app.js` | Optimistic ticks, dialogs, the drawer, drag-reordering |
| `uploads/` | Miniature photographs (four samples committed, the rest ignored) |
| `schema.sql` · `install.php` | The `ll_` tables, and the one-time installer |
| `design_handoff_miniatures_catalogue/` | The current design bundle, kept for reference — it supersedes the earlier `design_handoff_lead_ledger/` |

`src/` and `views/` are blocked by `.htaccess`; nothing under `uploads/` is ever
executed.

### Updating a database that is already live

`install.php` only ever creates tables. When the schema changes afterwards,
upload `migrate.php`, open it while signed in as the catalogue owner, and it
reports what it will change before changing anything. It is idempotent, and
tells you when there is nothing left to do — delete it then.

## Routes

| Route | |
| --- | --- |
| `GET /` | The sets index — every set, grouped by range |
| `GET /{range-slug}/{set-code}` | A set's photo grid |
| `GET /wanted` | The want ad — every miniature on the hunt, grouped by genre. Public, and meant to be pasted into forum posts |
| `GET /for-trade` | The trade list — every owned spare, same layout and same audience as the want ad |
| `GET POST /sign-in` | Sign in / create account (`?mode=up`) |
| `POST /sign-out` | |
| `GET /admin` | All ranges, each with its sets |
| `GET /admin/ranges/{id}` | One range's sets |
| `GET /admin/sets/{id}` | A set's miniatures |
| `POST /api/collection/{miniature}` | Toggle owned, admin only — idempotent, also accepts `PUT` / `DELETE`. Owning clears any wanted row; releasing clears any trade row |
| `POST /api/wanted/{miniature}` | Toggle wanted, admin only — same shape |
| `POST /api/trade/{miniature}` | Toggle for-trade, admin only — same shape. Only meaningful while owned, so releasing ownership clears it |
| `POST /admin/sets/{set}/reorder` | Persist a drag-reorder |

Everything works without JavaScript — the ticks, filters, density switcher and
admin forms are all real links and form posts. JavaScript upgrades them to
optimistic toggles, modal dialogs and drag-and-drop.

## Where this departs from the handoff

**Ownership is the archive's, not each visitor's.** The handoff makes ownership
private per user — every collector keeps their own ledger. Here there is one
collection: the ticks are the catalogue owner's, everyone sees them, and only an
admin can change them. `ll_ownership` therefore has no user column, the public
set page renders the tick as a read-only badge on owned cards rather than a
control on every card, and sign-up is open only until the first account exists,
because a second account would have nothing to do.

**Two sets in the same range may carry the same code.** The handoff treats a
set's code as its identity within a range; here it is a label. Because the
public URL `/{range}/{set}` still has to address one set, each set also carries
a `slug` derived from its code and uniquified per range — `c01`, then `c01-2`.
The slug is what the URL uses and what the database keeps unique; the code is
free to repeat. Renaming a set's code re-slugs it, so its public URL changes,
which is the same behaviour ranges already have.

The handoff treats a miniature's code and name as given. In this build **the
photograph is the required field** and the code and name are both optional,
stored as `NULL` when absent. A card with neither shows the design's flush-left
`No info` line; the admin table says `No code` / `No name`. The drawer and the
server both refuse to save a miniature without a photograph.

## The handoff's known gaps, and what happened to them

| Gap | Now |
| --- | --- |
| Drawer Code/Name fields did not save | Real form, posts to `admin/miniature/save` |
| Photo dropzone was prototype-only | Real upload to `uploads/`, validated by image bytes, old file removed on replace |
| Owned ticks did not persist | `ll_ownership`, one row per miniature, idempotent both ways |
| Headline counts were invented | Derived in one aggregate query — no N+1 |
| No Esc-to-close | Esc closes the topmost dialog or the drawer |
| No search | Still deliberately absent, as specified |

## A judgment call worth knowing about

- **The header's Admin link only appears for the catalogue owner.** The design
  shows it for anyone signed in, but a collector has nothing to manage.
- **The wanted page's crosshairs are the admin's, like every other tick.** The
  page is public to read; changing what is on the hunt needs the owner account,
  which is what the handoff asks for on port.
- **The header's height is measured, not assumed.** The design fixes it at 53px
  and offsets several sticky rails from that, but the signed-out header carries
  a Sign in button and stands 61px, and below 900px the nav wraps to a second
  row. `--header-h` starts at 53px in CSS and `app.js` overwrites it with the
  real height on load and on resize, so no rail sits low with the page sliding
  through the gap. It was 8px out before this.

Ticking is per miniature only. The handoff describes a "tick whole set" bulk
action; it was built and then removed on request, so there is no bulk endpoint.

## Requirements

PHP 8.0+ with PDO MySQL and GD-free image inspection (`getimagesize`), MySQL 5.7+
or MariaDB 10.2+ (the schema uses `InnoDB` foreign keys for the delete cascades),
and `mod_rewrite`.
