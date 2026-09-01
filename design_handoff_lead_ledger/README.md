# Handoff: Lead Ledger — Oldhammer miniatures archive

## Overview

A public catalogue of vintage Citadel/Games Workshop miniatures (1984–1992), plus an admin backend for maintaining it. Two halves, one signed-in identity:

- **Public** — browse the archive by **Range → Set → Miniature**. The front page lists every set grouped by range; opening a set shows its miniatures as a photo grid. A signed-in collector ticks any miniature as **owned** with a single checkmark. Owned photographs print at full strength; unowned ones sit back to a faint grey, so a set page reads as a map of the gaps.
- **Admin** — manage the catalogue structure: create/rename/delete ranges and sets, open a set, and add/edit/delete/reorder the miniatures inside it.

There is deliberately **no miniature detail page**. Miniatures exist only in the gallery and in the admin set view. A miniature has exactly: **code, name, photograph, parent set**. No sculptor, year, material, price, description, or collector counts — those were explicitly removed.

Data model:

```
Range   { id, name }                       — no code, no description
Set     { id, code, name, rangeId }        — no description, no image, no year
Miniature { id, code, name, photo, setId, sortIndex }
Ownership { userId, miniatureId }          — private per user
```

Ordering rules: **ranges sort alphabetically by name; sets sort by code (natural/numeric-aware) within their range.** Neither is user-reorderable. **Miniatures inside a set are manually ordered** by drag-and-drop in the admin table — persist a `sortIndex`.

## About the Design Files

The files in this bundle are **design references created in HTML** — prototypes showing intended look and behaviour, not production code to copy directly.

`Lead Ledger.dc.html` is a self-contained HTML prototype: an HTML template plus a JavaScript logic class, rendered by the bundled `support.js` runtime. **Do not port `support.js` or the `<x-dc>` / `<sc-for>` / `<sc-if>` template syntax into the target codebase.** Read the file as a spec: the markup shows structure and exact inline styles; the logic class (`renderVals()`) shows state, derived values and handlers.

The task is to **recreate these designs in the target codebase's existing environment** (React, Vue, Rails views, etc.) using its established patterns, routing and component library. If no environment exists yet, pick the most appropriate framework.

Reading order:
1. `_ds/modernist/readme.md` — the design system's rules.
2. `_ds/modernist/styles.css` — the actual tokens and component classes. Port this stylesheet more or less as-is; it is plain CSS with no build step.
3. `Lead Ledger.dc.html` — the screens. Template first, then `class Component`.

Open the HTML file directly in a browser to click through everything.

## Fidelity

**High-fidelity.** Colours, typography, spacing, rules and interaction states are final and come from the Modernist design system. Recreate faithfully. Every value is either a `var(--*)` token from `styles.css` or an explicit px value in an inline style — both are intentional.

Sample **content** is not final: range names, set codes, miniature names and the four photographs are placeholders. The 5,318 / 412 headline figures in the page head are invented to show the design at real scale and do **not** match the ~74 sample miniatures — replace with live counts.

## Design system: Modernist

Flat, architectural, set entirely in Archivo. Near-mono: red `#ec3013` on a light `#f3f2f2` ground. **Zero corner radius anywhere.** Strong 2px rules do the organising — no hairlines, no shadows, nothing floats. Labels sit flush left, including inside buttons. Photography prints in pure black and white.

Classes used from `styles.css`:

| Class | Use |
| --- | --- |
| `.btn` + `.btn-primary` / `.btn-secondary` / `.btn-ghost` / `.btn-block` | Buttons. Primary is a solid accent fill. |
| `.card` | The miniature card in the gallery grid |
| `.table` | Both admin tables (themed uppercase header, row rules) |
| `.input`, `.field > label` | Form fields |
| `.dialog-backdrop` + `.dialog` (+ `-title` / `-body` / `-actions`) | Both modals |

Project-level CSS on top of the system (defined in the prototype's `<helmet>`; port these):

```css
/* photographs print in pure black and white, flat on the grid */
.plate    { position: relative; overflow: hidden; background: var(--color-neutral-900); }
.panel    { background: var(--color-surface); border-radius: var(--radius-md); }
.panel-hover:hover { background: var(--color-accent-100); }
.rule-b   { border-bottom: 2px solid var(--color-divider); }
.rule-tb  { border-top: 2px solid var(--color-divider); border-bottom: 2px solid var(--color-divider); }
.row-hit:hover { background: color-mix(in srgb, var(--color-text) 4%, transparent); }
.tbl-scroll { overflow-x: auto; }

/* Material Symbols Outlined, loaded from Google Fonts */
.msym { font-family: "Material Symbols Outlined"; font-size: 20px; line-height: 1; font-weight: 400; }
.icon-btn { display: grid; place-items: center; width: 30px; height: 30px; padding: 0;
            border: 0; background: transparent; cursor: pointer;
            color: color-mix(in srgb, var(--color-text) 60%, transparent); }
.icon-btn:hover { background: color-mix(in srgb, var(--color-text) 8%, transparent); color: var(--color-text); }
.icon-btn.danger:hover { background: var(--color-accent-100); color: var(--color-accent-700); }
```

> **Icon note.** The prototype uses **Material Symbols Outlined** (`add`, `edit`, `delete`, `arrow_back`, `swap_horiz`, `add_photo_alternate`), chosen by the client, overriding the design system's stated Lucide default. Keep Material Symbols.

---

## Screens

### 1. App header — every screen

Sticky, `top: 0`, `z-index: 30`, `background: var(--color-bg)`, `border-bottom: 2px solid var(--color-divider)`. Inner: `display:flex; align-items:center; gap:26px; padding:12px 26px`. **Measured height 53px** — every other sticky offset and `calc(100vh - 53px)` in the design depends on it.

- **Wordmark** (left, `margin-right:auto`, links to the sets index): `LEAD LEDGER` in `var(--font-heading)` 800 / 20px / `letter-spacing:-.02em` / uppercase, then a sub-label `Oldhammer archive` at 10px / `.08em` / uppercase / 45% text. Baseline aligned, `gap:10px`.
- **Signed in:** `.btn-ghost` "Sign out" + `.btn-secondary` that toggles context — reads **"Admin"** on public screens, **"← Public site"** (Material `arrow_back`, 16px) on admin screens. Both `white-space:nowrap; flex:none`.
- **Signed out:** a single `.btn-secondary` "Sign in", hidden while already on the auth page.
- Buttons all run at the system's default `.btn` size (14px). Don't re-override.

### 2. Public — sets index (the front page)

`max-width:1320px; margin:0 auto; padding:26px 30px 90px`. No side rail on this page.

**Page head** (`flex`, `align-items:flex-end`, `justify-content:space-between`, `gap:30px`, no bottom rule here):
- Kicker "The whole archive" — 10px / `.08em` / uppercase / `var(--color-accent)`.
- `<h1>` 44px, `line-height:1`.
- Blurb `<p>` 13.5px / 1.55 / 60% text / `max-width:58ch` / `text-wrap:pretty`.
- Right: three right-aligned stats, `gap:24px` — **Miniatures / Sets / You own**. Number `var(--font-heading)` 26px `line-height:1`; label 9.5px / `.08em` / uppercase / 50% text. "You own" is `var(--color-accent)`.

**Filter bar** — present but empty on this page (the All/Owned/Missing group, count and density switcher are set-page only). It contributes the `.rule-b` divider beneath the head.

**One `<section>` per range**, `margin-bottom:44px`:
- Range header, `display:flex; align-items:baseline; gap:12px; padding-bottom:9px; margin-bottom:18px`, `.rule-b`. Contents: `<h3>` 24px range name (no code badge); meta "3 sets · 1,840 miniatures" at 11px / 48% text; owned ratio pushed right (`margin-left:auto`), 11px / `.1em` / uppercase / 50% text.
- **Set cards**: `grid-template-columns: repeat(auto-fill, minmax(268px, 1fr)); gap:22px`. Each card is `.panel.panel-hover`, `padding:15px 16px`, `display:flex; flex-direction:column; gap:13px`, whole card clickable:
  - Top row: set code (`var(--font-heading)` 14px `.08em` accent, `min-width:44px`), set name (`var(--font-heading)` 20px, flexes), and — only when the set is fully owned — an 18×18 accent square with a white `✓`.
  - Bottom (`margin-top:auto`): a label row `"7 miniatures"` / `"3 owned"` at 10.5px `.1em` uppercase 50% text, then a 4px progress bar — track `var(--color-neutral-300)`, fill `var(--color-accent)` at the owned percentage.

### 3. Public — set page

Two columns: `display:grid; grid-template-columns: 266px minmax(0,1fr)`.

**Left rail** (266px) — only exists on set pages, never on the index. `border-right:2px solid var(--color-divider)`, `position:sticky; top:53px; height:calc(100vh - 53px); overflow:auto; padding:20px 0 40px`.
- A "Ranges" label (10px `.08em` uppercase 50% text) at `padding:0 20px 14px`.
- Range rows, separated by `border-top: 1px solid color-mix(in srgb, var(--color-text) 8%, transparent)`: a full-width button, `grid-template-columns:14px 1fr auto; gap:9px; padding:10px 20px`, `var(--font-heading)` 15px. Left cell is a `›` caret rotating 0°→90° on expand (`transition: transform .12s`, 45% text); right cell is the range's total count, `var(--font-body)` 11px / 45%.
- Set rows (when expanded): `display:flex; gap:9px; padding:6px 20px 6px 43px`, 13px. Code in `var(--font-heading)` `.06em` accent `min-width:38px`; name flexes; owned ratio `4/8` at 10px, 60% opacity. **Selected set:** `background: var(--color-accent-100); color: var(--color-accent-800); box-shadow: inset 2px 0 0 var(--color-accent)`.

**Main column**, `padding:26px 30px 90px`.

Page head, same flex shell but **with** `padding-bottom:14px; border-bottom:2px solid var(--color-divider)`, and no stat row:
- Breadcrumb line: `← All sets` (accent link) `/` `{Range name} · {SET CODE}` (55% text) — 11px `.08em` uppercase, `gap:10px`.
- `<h1>` 44px set name.
- Owned line `"2 of 7 owned"` — 11px `.1em` uppercase 50% text.

**Filter bar** — sticky `top:53px`, `z-index:20`, `padding:12px 0`, `background-color: var(--color-bg)`, `.rule-b`, `display:flex; flex-wrap:wrap; gap:10px`:
- **All / Owned / Missing** segmented group — one 1px-divider box, each button `height:32px; padding:0 14px`, `var(--font-heading)` 13px, `border-left` between. Active = accent fill, `var(--color-bg)` text. Mutually exclusive.
- Result count `"Showing 8 of 8"` — 11px `.08em` uppercase 50% text, `margin-left:auto`.
- **Density switcher** — three 32×32 buttons, glyphs `▦ ▤ ▢` = Contact sheet / Compact / Comfortable, active = accent fill.

**Miniature grid** — `repeat(auto-fill, minmax(<col>, 1fr))`:

| Density | col | gap | caption |
| --- | --- | --- | --- |
| Contact sheet | 94px | 12px | hidden |
| Compact (default) | 128px | 18px | shown |
| Comfortable | 178px | 24px | shown |

**Miniature card** — `.card.mini-card`, `padding: var(--space-2); gap: var(--space-2)`, `position:relative`. Background is `var(--color-surface)` when owned, `color-mix(in srgb, var(--color-surface) 55%, var(--color-bg))` when not.
- **Plate**: `.plate`, `aspect-ratio:3/4`, `display:grid; place-items:center`, clickable (clicking the plate toggles owned). Inside, the photograph is painted as a background layer at `grid-area:1/1`, `background-size:cover`, over `var(--color-neutral-900)`:
  - Owned: `opacity:1; filter: grayscale(1) contrast(1.08)`
  - Not owned: `opacity:.3; filter: grayscale(1) contrast(.9)`
  - `transition: opacity .18s, filter .18s`
- **Tick control** — a 34×34 button at `top/right: var(--space-2)`, `z-index:3`, **2px border**:

  | State | Border | Background | Check |
  | --- | --- | --- | --- |
  | Not owned | `color-mix(in srgb, var(--color-bg) 60%, transparent)` | `color-mix(in srgb, var(--color-neutral-900) 45%, transparent)` | 22px, `stroke-width:3`, `stroke-linecap:square`, `opacity:0` |
  | Owned | `var(--color-accent)` | `var(--color-accent)` | same, `var(--color-bg)`, `opacity:1` |

- **Caption**, flush left (never centred):
  - Code — 10px `.1em` uppercase. Owned: `var(--color-accent-700)`. Not owned: 40% text.
  - Name — `var(--font-heading)` 11.5px `line-height:1.2`. Owned: `var(--color-text)`. Not owned: 52% text.
  - Which parts show is driven by the `cardCaption` setting (Code + name / Name only / Code only / None). With **None**, the card shows a faint flush-left `No info` line (10px `.1em` uppercase, 32% text) in place of the caption.

**Empty state** — `padding:56px 0`, 2px rules top and bottom. Title `var(--font-heading)` 20px, body 13px / 55% text. Two variants: "No miniatures in this set yet" (the set is catalogued but unpopulated) and "Nothing matches this filter" (Owned/Missing filter is on).

**No pagination.** The whole set renders at once — sets are small by nature.

### 4. Owned marking

The default is the **Checkbox** described above. Two alternates exist in the prototype as switchable variants, worth keeping in the backlog:
- **Stamped plate** — owned plates get a full `inset:0` overlay: the word `OWNED` in `var(--font-heading)` 15px `letter-spacing:.14em`, `var(--color-accent)`, inside a `3px solid var(--color-accent)` box, `transform: rotate(-12deg)`, `z-index:3`.
- **Corner fold** — a 30px accent triangle in the plate's top-right.

Behaviour:
- Toggling is optimistic and instant — no confirmation, no toast.
- The tick button and the plate both toggle; the button must `stopPropagation`.
- **Signed out, a tick navigates to the auth page** (sign-in tab) instead of toggling.
- "Tick whole set" (in a set band header) toggles every miniature in the set: if all are already owned it clears them, otherwise it sets them all. Should be **one bulk request**, not N.

### 5. Admin — ranges & sets (the admin landing page)

Full two-column below the shared header: `grid-template-columns: 200px minmax(0,1fr); min-height: calc(100vh - 53px)`.

**Sidebar** — `background: var(--color-surface)`, `border-right:2px solid var(--color-divider)`, `padding:18px 0`, `position:sticky; top:53px; height:calc(100vh - 53px)`. No brand block (the shared header carries it), no back link (the header button does). One item: **Ranges & sets** — `display:block; padding:8px 18px`, `var(--font-heading)` 14px. Active: `background: var(--color-accent-100); color: var(--color-accent-800); box-shadow: inset 2px 0 0 var(--color-accent)`.

**Main** — `padding:22px 26px 60px`.
- Section head, `.rule-b`, `padding-bottom:14px`: kicker "Manage" (10px `.08em` uppercase 50%) + `<h2>` "Ranges & sets"; right, a `.btn-primary` **"New range"** with the Material `add` glyph at 16px.
- One `<section>` per range, `margin-bottom:34px`:
  - Range head, `display:flex; align-items:baseline; gap:12px`: `<h3>` 22px name, meta `"3 sets"` (11px `.08em` uppercase 50%), then pushed right a `.btn-secondary` **"Edit range"** (`edit` glyph) and a `.btn-primary` **"Add set"** (`add` glyph), both 12px.
  - Sets `.table` inside a `.tbl-scroll` wrapper. Columns: **Code** (110px, `var(--font-heading)` `.08em`), **Set** (flex, `font-weight:500`), **Miniatures** (130px, 13px 60% text). The whole row is the click target → opens that set. No trailing "Open" button.
  - **Empty range**: instead of the table, `padding:22px 0 26px; border-top:2px solid var(--color-divider)` with `var(--font-heading)` 15px "No sets in this range yet" and a 13px / 55% line "Add a set to start listing miniatures under {range}."

### 6. Admin — set page

Same shell. Section head, `.rule-b`:
- Breadcrumb line: `← Ranges & sets` (accent link) `/` `{Range name}` (55% text) — 11px `.08em` uppercase.
- `<h2>` `{CODE} {Set name}`.
- Count line `"7 miniatures"` — 11px `.1em` uppercase 50% text.
- Right: `.btn-secondary` **"Edit set"** (`edit`) and `.btn-primary` **"Add miniature"** (`add`).

**Miniatures `.table`** in a `.tbl-scroll` wrapper. The whole row opens the edit drawer; rows are `draggable` for manual reordering.

| # | Column | Width | Content |
| --- | --- | --- | --- |
| 1 | drag handle | 28px | `⠿`, `cursor:grab`, 35% text |
| 2 | thumb | 46px | 30×38 `.plate` with the photograph |
| 3 | Code | 110px | `var(--font-heading)`, `letter-spacing:.08em` |
| 4 | Name | flex | `font-weight:500` |
| 5 | — | 60px | `.icon-btn.danger` `delete` (stops propagation) |

Drop indicator: `box-shadow: inset 0 2px 0 var(--color-accent)` on the row being hovered over.

**Empty set**: `padding:34px 0`, 2px rules top and bottom, `var(--font-heading)` 17px "No miniatures in this set yet" + a 13px / 55% line. No button — the header's "Add miniature" covers it.

### 7. Admin — miniature drawer

`position:fixed; inset:0; z-index:60; display:flex; justify-content:flex-end`, backdrop `color-mix(in srgb, var(--color-neutral-900) 42%, transparent)`; the area left of the panel closes it on click.

Panel: `width:480px; max-width:92vw; height:100%; overflow:auto`, `background: var(--color-bg)`, `border-left:2px solid var(--color-divider)`, `box-shadow: var(--shadow-lg)`, `padding:22px 24px 40px`.

- Header: kicker "Edit miniature" / "New miniature" (10px `.08em` uppercase accent), `<h3>` name, and a `.btn-ghost` `×` pushed right.
- **Photo dropzone**: a `.plate` at `aspect-ratio:4/3`. Accepts drag-and-drop **and** click-to-choose (hidden `<input type="file" accept="image/*">`). Empty: centred Material `add_photo_alternate` at 26px over "Drop a photograph, or click to choose" (12px, 62% of bg). Active drag: `outline: 2px solid var(--color-accent); outline-offset:-2px`. With an image: full-bleed `background-size:cover`, `filter: grayscale(1) contrast(1.08)`, and below it a row with the filename (11.5px, 55% text) plus `.icon-btn` `swap_horiz` (replace) and `.icon-btn.danger` `delete` (remove).
- Fields: a `118px 1fr` row — **Code**, **Name**.
- Actions, right-aligned, `gap:9px`: `.btn-secondary` "Cancel", `.btn-primary` "Save miniature".

### 8. Range / set editor dialog

A `.dialog` at `z-index:70`, used for four cases: **Edit range**, **New range**, **Edit set**, **New set in {range}** — the title says which.
- `.dialog-title`
- **Code** field — shown for sets only; **ranges have no code** (derive an internal id from the name server-side).
- **Name** field
- For *edit* cases only, a quiet `.btn-ghost` **Delete** (12px, `delete` glyph, `padding-inline:0`) — this hands off to the confirm dialog, it does not delete directly.
- `.dialog-actions`: "Cancel" + a primary whose label is "Save" / "Create set" / "Create range".

### 9. Delete confirmation dialog

A `.dialog` at `z-index:75`, one component covering miniature / set / range. Title `Delete {name}?`; body states the blast radius and that it cannot be undone:
- **Miniature** — "This removes {CODE} from the set for every collector."
- **Set** — "The set and its N miniatures are removed from the catalogue, along with every collector's record of them."
- **Range** — "This removes N sets and M miniatures from the catalogue, along with every collector's record of them."

Actions: "Cancel" + a primary labelled "Delete miniature" / "Delete set" / "Delete range". Deleting a range cascades to its sets and their miniatures; deleting a set cascades to its miniatures.

### 10. Sign in / create account page

A full page (not a modal), `display:grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); min-height: calc(100vh - 53px)`.

**Left — the form**, `padding:64px 56px; max-width:560px`, vertically centred:
- Kicker "Account" / "Join the archive" (10px `.08em` uppercase accent).
- `<h1>` 46px "Sign in" / "Create an account".
- Blurb 14px / 1.55 / 62% text / `max-width:42ch`.
- **Sign in / Create account** segmented tabs — one 1px-divider box, `height:34px; padding:0 16px`, `var(--font-heading)` 13px, active = accent fill. `width:max-content`.
- Fields, `gap:16px`, `max-width:400px`: **Collector name** (sign-up only), **Email**, **Password** (with an "Eight characters or more." hint on sign-up).
- Sign-in only: a "Keep me signed in" checkbox (`accent-color: var(--color-accent)`) and a "Forgotten password" link, spread apart.
- `.btn-primary.btn-block` "Sign in" / "Create account", `justify-content:flex-start`.
- A switch line: "No account yet? **Create one**" / "Already have one? **Sign in**".

**Right — the value panel**, `border-left:2px solid var(--color-divider)`, `background: var(--color-surface)`, `padding:64px 56px`, vertically centred. A 10px uppercase label "What an account gives you", then three items each `padding:18px 0; border-top:2px solid var(--color-divider)` — `var(--font-heading)` 18px title over 13.5px / 1.5 / 60% body, `max-width:40ch`.

Reached from the header "Sign in" button and from any owned-tick while signed out.

---

## Responsive

Desktop-first with one breakpoint at **900px**. Below it:

- Header padding drops to `10px 16px`; the `Oldhammer archive` sub-label hides.
- Public set page: the shell becomes single-column and **the range rail is hidden entirely** — the breadcrumb is the navigation.
- Page padding `20px 16px 72px`; `h1` → 32px; the page head stacks (`flex-direction:column; align-items:flex-start`); the stat row goes flush left.
- The filter bar stops being sticky.
- Set cards go single column; the miniature grid drops to `minmax(108px, 1fr)` at `gap:14px`.
- Admin: shell becomes single-column, the sidebar becomes a horizontal scrolling nav strip with a bottom rule instead of a right one; section heads stack; `h2` → 26px.
- Both admin tables scroll horizontally inside `.tbl-scroll`.
- The miniature drawer goes full-width with no left border.
- Auth page stacks; the value panel moves below the form with a top rule instead of a left one.

Exact rules are in the prototype's `<helmet>` `@media (max-width: 900px)` block — port them.

---

## Interactions & Behaviour

- **Routes** — `/` (sets index), `/{range}/{set}` (set page), `/sign-in`, `/admin` (ranges & sets), `/admin/sets/{set}`. Every navigation scrolls to top.
- **Rail accordion** — multiple ranges can be open at once; two are open by default.
- **Set selection from the rail** — clicking the active set again clears back to the index.
- **Owned filter** — All / Owned / Missing, mutually exclusive, set-page scoped.
- **Density** — persists across navigation (session-level is fine).
- **Drag reorder** — miniatures only. Ranges and sets are alphabetical and must not be draggable.
- **Hover** — table rows tint 4%; set cards go `var(--color-accent-100)`; icon buttons tint 8% (danger variant tints accent).
- **Focus** — `:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 2px; }` comes from the stylesheet. Never fall back to the browser default ring.
- **Dialogs** — close on Cancel, on the primary action, and (drawer only) on a backdrop click. **Esc-to-close is not wired in the prototype — add it.**

## State

| Key | Type | Notes |
| --- | --- | --- |
| `screen` | `'browse' \| 'auth' \| 'admin'` | routes in a real app |
| `setFilter` | `string \| null` | selected set code; `null` = sets index |
| `expanded` | `{ [rangeCode]: boolean }` | rail accordion |
| `ownedOnly` / `missingOnly` | `boolean` | mutually exclusive |
| `density` | `'Contact sheet' \| 'Compact' \| 'Comfortable'` | |
| `owned` | `{ [miniCode]: 1 }` | **server-persisted, per user** |
| `photoOverrides` | `{ [miniCode]: { url, name } }` | drawer uploads — see caveat below |
| `adminSetId` | `string \| null` | which admin view |
| `editor` | `object \| null` | range/set editor dialog payload |
| `confirm` | `{ kind, key } \| null` | delete confirmation |
| `drawerId` | `string \| 'NEW' \| null` | miniature drawer |
| `drag` / `dragOver` | reorder state | |
| `signedIn` / `authMode` | auth | |

**Server work implied:**
- `GET /api/ranges` — ranges with their sets, each set carrying `count` and, for a signed-in user, `ownedCount`. The index needs this in one call; don't N+1 it.
- `GET /api/sets/{code}` — the set with its ordered miniatures and per-miniature owned flags.
- `PUT` / `DELETE /api/collection/{miniatureCode}` — toggle owned; **idempotent**.
- `POST /api/collection/sets/{code}` — bulk tick/untick a whole set.
- Admin CRUD for ranges, sets and miniatures; a reorder endpoint taking the set's miniature ids in order; image upload.
- Owned state is private per user; nothing in the UI exposes who owns what.

## Design Tokens

All in `_ds/modernist/styles.css` — use the variables, never the literals.

**Colour**
```
--color-bg       #f3f2f2    --color-text     #201e1d
--color-surface  (system)   --color-accent   #ec3013
--color-divider  (system, always rendered at 2px)
neutral 100→900  ·  accent 100→900   (OKLCH ramps, matched lightness steps)
```
Mono scheme — there is no second accent; `--color-accent-2-*` is a stand-in for the same role. Accent-on-ground is ~3:1: fine for chrome, icons and large text, **not** for body copy — use `--color-accent-700` for paragraph-size accent text.

Recurring `color-mix(in srgb, var(--color-text) N%, transparent)` tints in this design: **62%** auth blurb · **60%** secondary table cells and body copy · **55%** empty-state body, breadcrumb tail, filenames · **52%** unowned card name · **50%** kickers and counts · **48%/45%** metas · **40%** unowned card code · **35%** drag handles · **32%** "No info" · **28%** breadcrumb slash · **8%** rail inner hairline · **4%** row hover.

**Type** — `--font-heading` / `--font-body` both Archivo. Heading weight via `var(--font-heading-weight)`. Explicit overrides in this design (44px/46px `h1`, 24px/22px `h3`, 11.5px card names) are intentional.

**Radius** — every relevant component is overridden to `0`. **Nothing is rounded.** The one `var(--radius-md)` use is `.panel`, which resolves to 0 anyway.

**Shadow** — only `--shadow-lg`, on the admin drawer.

**Spacing** — `var(--space-*)`; the miniature card uses `var(--space-2)` for its padding and gap. Page gutters are explicit px (26/30/22px desktop, 16px mobile) — keep them.

## Assets

- **Photography** — four sample studio shots in `uploads/`, shot on black, cycled across the ~74 sample miniatures. They are painted as CSS backgrounds (not `<img>`) so the owned/unowned filter and opacity transition apply cleanly. Real photography should keep the same treatment: `grayscale(1)` always, `contrast(1.08)` + full opacity when owned, `contrast(.9)` + `opacity:.3` when not. Target ratios: **3:4** (grid card), **4:3** (drawer dropzone), **30×38** (admin thumb).
- **Icons** — Material Symbols Outlined, loaded from Google Fonts: `@20,400,0,0`. Self-host in production.
- **Fonts** — Archivo, imported by `styles.css`. Self-host in production.
- **Content** — range names, set codes and miniature names are real historical Citadel references used as sample data. Replace with the live archive.

## Known gaps — pick these up

1. **The miniature drawer's Code and Name fields do not save.** They render the current values but their `onChange` is a no-op, and "Save miniature" just closes the drawer. Wire them.
2. **The photo dropzone is prototype-only.** It reads the file to a data URL and holds it in component state under the miniature's code — nothing uploads, and it is lost on reload. Replace with a real upload.
3. **Owned ticks do not persist.** They live in component state; a reload restores a seeded demo set. Needs the collection endpoints above.
4. **Headline counts are fake.** `5,318` miniatures / `412` sets in the page head, and the per-range totals in the rail (1,840 / 726 …), are hard-coded and contradict the sample data. Derive them.
5. **No Esc-to-close** on the drawer or either dialog.
6. **No search.** Removed deliberately at this catalogue size; if the real archive is in the thousands it will need to come back — the filter bar is where it lived.

## Files

| File | What it is |
| --- | --- |
| `Lead Ledger.dc.html` | The prototype — template (all screens) then `class Component` with state, derived values and handlers. Read both halves. |
| `_ds/modernist/styles.css` | The Modernist design system stylesheet — tokens + component classes. Port this. |
| `_ds/modernist/readme.md` | The design system's written rules (do's, don'ts, component table). |
| `uploads/*.jpg` | The four sample miniature photographs. |
| `support.js` | The prototype runtime only. **Do not port.** Included so the HTML opens and runs in a browser. |
