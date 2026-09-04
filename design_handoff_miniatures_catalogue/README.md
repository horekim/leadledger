# Handoff: Miniatures Catalogue (Lead Ledger)

## Overview

A catalogue of classic Citadel ("Oldhammer") miniatures with a personal collection layer on top. Visitors browse ranges and sets; a signed-in collector ticks off the castings they own and flags the ones they are actively hunting for. A small admin area maintains the catalogue itself (ranges, sets, and the miniatures inside each set).

Three surfaces:

1. **Public catalogue** — sets index (grouped by genre → range) and a set page showing that set's miniatures as a photographic grid.
2. **Admin** — ranges & sets management, a per-set miniature table, and a miniature editor drawer.
3. **Auth** — combined sign-in / sign-up page.

## About the Design Files

The files in this bundle are **design references created in HTML** — prototypes showing intended look and behaviour. They are **not production code to copy directly**.

`Miniatures Catalogue.dc.html` is a single-file prototype written against a small in-house runtime (`support.js`): a declarative template plus a JavaScript logic class. It uses `{{ }}` template holes, `<sc-for>` / `<sc-if>` control flow, and inline styles throughout. None of that is meant to survive the port.

The task is to **recreate these designs in the target codebase's existing environment** (React, Vue, SwiftUI, native — whatever is in use) using its established patterns, component library, routing, and data layer. If no environment exists yet, choose an appropriate stack and implement the designs there. Treat the prototype as the visual and behavioural spec; treat the Modernist stylesheet (`_ds/modernist/styles.css`) as the source of truth for tokens.

## Fidelity

**High-fidelity.** Colours, typography, spacing, rules, and interaction states are final and should be reproduced closely. All values come from the Modernist design system's CSS variables — port those tokens rather than the literal hexes where possible.

Two things are deliberately fake in the prototype and must be built properly:

- **Owned / wanted state is session-only** (in-memory objects, seeded with a fake pattern on mount). Needs real persistence per user.
- **The miniature editor drawer does not save.** Its Code and Name inputs are read-only in practice (`onChange` is a no-op) and "Save miniature" just closes the drawer.

Also note the aggregate counts in the index header ("5,318 miniatures", "412 sets") are hard-coded placeholders; they must be derived from real data.

---

## Design Tokens

From `_ds/modernist/styles.css`. Use the variables, not the literals.

**Colour**

| Token | Value | Use |
| --- | --- | --- |
| `--color-bg` | `#f3f2f2` | Page ground |
| `--color-surface` | `#eae9e9` | Cards, admin rail, auth aside |
| `--color-text` | `#201e1d` | Ink |
| `--color-accent` | `#ec3013` | The single accent — set codes, primary buttons, progress bars, WANTED |
| `--color-accent-100` | `#fff2ef` | Hover tint on set cards, danger icon-button hover |
| `--color-accent-600` | `#dd2b0f` | Pressed accent |
| `--color-accent-700` | `#ae1800` | Accent text at paragraph size, link hover |
| `--color-divider` | `#201e1d` @ 40% | All rules |
| `--color-neutral-300` | `#d7d3d3` | Progress bar track |
| `--color-neutral-900` | `#2d2b2b` | Photo plate ground, modal/drawer scrim base |

Many one-off tints are `color-mix(in srgb, var(--color-text) N%, transparent)` — the recurring steps are **60%** (body copy, secondary text), **55%** (genre labels, empty-state body), **50%** (uppercase meta labels), **48%**, **45%**, **40%**, **35%**, **32%**, **28%** (breadcrumb slash), **8%** and **4%** (row hovers).

**Type** — Archivo throughout. `--font-heading` = Archivo at weight **800**; `--font-body` = Archivo (400/500).

Scale as used: page `h1` **44px/1** (auth 46px/1.02), `h2` default, range heading **24px**, set-group heading **22px**, set card title **20px/1.1**, stat figure **26px/1**, body copy **13.5px/1.55**, table/UI text **13px**, card caption name **11.5px/1.2**, meta labels **10–11px uppercase letter-spacing .08–.1em**, card code **10px uppercase .1em**, WANTED strip **9px .16em**.

**Spacing** — `--space-1` 4px, `--space-2` 8px, `--space-3` 12px, `--space-4` 16px, `--space-6` 24px, `--space-8` 32px.

**Radius** — `--radius-md: 0px`. **Nothing is rounded anywhere.** This is deliberate.

**Shadows** — only `--shadow-lg` (`0 12px 32px #2d2b2b @22%`) is used, on the admin drawer.

**Rules** — major divisions are `2px solid var(--color-divider)`. Never soften these to hairlines. The one 1px rule is the range-rail row separator (`--color-text` @ 8%) and the segmented-control internal dividers.

**Icons** — Material Symbols Outlined (20px, weight 400) for interface glyphs; the owned tick and the wanted crosshair are inline SVG (see below). The design system nominally specifies Lucide; the prototype uses Material Symbols. Either is fine — pick whatever the codebase already ships and keep it consistent.

**Photography** — every miniature photo prints in pure black and white on a near-black plate: `background-color: var(--color-neutral-900)`, `background-size: cover`, `filter: grayscale(1) contrast(1.08)` when owned. Never tint or colourise imagery.

---

## Screens / Views

### Global header

Sticky, `z-index: 30`, `--color-bg`, `border-bottom: 2px solid --color-divider`, height **53px** (padding `12px 26px`) — several sticky offsets depend on that 53px, so keep it or make it a variable.

- Left: brand lockup, `<a>` back to the catalogue. "LEAD LEDGER" in heading font, **20px**, weight 800, `letter-spacing -.02em`, uppercase. Beside it a subtitle at **10px** uppercase `.08em`, text @45%.
- Right, signed in: ghost "Sign out" + secondary button that navigates between public and admin (label and a leading `arrow_back` glyph both change by context).
- Right, signed out: secondary "Sign in".

At ≤900px the header drops to `10px 16px`, gap 12px, and the brand subtitle hides.

> **Copy fix pending:** the subtitle currently reads "Oldhammer archive", which is left over from an earlier archive-framing brief; the page blurb says "My collection of Oldhammer miniatures". Pick one voice before shipping.

### 1. Sets index (public, default)

**Purpose:** see every range and set at a glance and how complete each one is.

**Layout:** single column, `padding: 22px 26px 60px` (≤900px: `20px 16px 72px`), no side rail on this view.

Page head — flex row, baseline-ends, gap 30px:
- Kicker: **10px** uppercase `.08em` in `--color-accent`.
- `h1` **44px/1**, margin `0 0 5px`.
- Blurb: **13.5px/1.55**, text @60%, `max-width: 58ch`, `text-wrap: pretty`.
- Stat row (right, `flex: none`, gap 24px, right-aligned): three figures at **26px/1** over **9.5px** uppercase labels @50% — "Miniatures", "Sets", and "You own" (this third figure in `--color-accent`).

Then **genre sections** (`margin-bottom: 52px`). Each opens with a label: `border-top: 2px solid --color-divider`, `padding-top: 9px`, `margin-bottom: 26px`, text **11px** uppercase `.1em` at `--color-text` @ **55%** (a muted ink — deliberately quiet, *not* accent red; genres are scaffolding, not controls).

Inside a genre, one `<section>` per range (`margin-bottom: 44px`):
- Range head: flex baseline row, `padding-bottom: 9px`, `border-bottom: 2px` , `margin-bottom: 18px`. `h3` **24px**; meta "N sets · N miniatures" at **11px** @48%; right-aligned "N / N OWNED" at **11px** uppercase `.1em` @50%.
- Set grid: `grid-template-columns: repeat(auto-fill, minmax(268px, 1fr))`, `gap: 22px` (single column ≤900px).

**Set card** — `--color-surface`, no radius, `padding: 15px 16px`, flex column, gap 13px, whole card clickable, hover `--color-accent-100`:
- Top row: set code (heading font, **14px**, `.08em`, accent, `min-width: 44px`), set name (heading font **20px/1.1**), and a ✓ badge shown only when the set is complete.
- Bottom: a meta row (**10.5px** uppercase `.1em` @50%) with "N miniatures" left and "N owned" right, then a **4px** progress bar — track `--color-neutral-300`, fill `--color-accent` at owned/total %.

### 2. Set page (public)

**Purpose:** work through one set, ticking what you own and flagging what you're hunting.

**Layout:** two columns — `240px` rail + main (`display: grid`). The rail collapses entirely ≤900px.

**Range rail** — sticky at `top: 53px`, `height: calc(100vh - 53px)`, scrollable, `border-right: 2px`. A "RANGES" label, then per range a disclosure button (caret ›, rotated 90° when open, heading font 15px) with a set count; expanded ranges list their sets as rows `padding: 6px 20px 6px 43px`, **13px**, showing accent set code (`min-width: 38px`), name, and an `own/total` ratio at 10px `.06em` opacity .6. The active set is highlighted.

**Main:** breadcrumb ("← All sets / Range name", 11px uppercase, link in accent, slash @28%), `h1` **44px**, "N OF N OWNED" at 11px uppercase `.1em` @50%, blurb.

**Filter bar** — sticky `top: 53px`, `z-index: 20`, `border-bottom: 2px`, `padding: 12px 0`, `margin-bottom: 26px`, on `--color-bg`; static (unsticky) ≤900px. Contents:
- A segmented control (1px divider border, internal 1px dividers): **All / Owned / Missing / Wanted**. Each option is 32px tall, `padding: 0 14px`, heading font 13px; the selected one fills `--color-accent` with `--color-bg` text.
- Result count, 11px uppercase `.08em` @50%, pushed right with `margin-left: auto`.
- Density control: three 32px square buttons — Contact sheet ▦ / Compact ▤ / Comfortable ▢ — same selected treatment.

**Set group header** (shown when several sets are listed): accent code, `h3` 22px, meta, right-aligned owned label, and a ghost **"Tick whole set"** button that owns/un-owns every miniature in the set at once.

**Miniature grid** — `repeat(auto-fill, minmax(Xpx, 1fr))` where X and the gap follow density: contact sheet **94px / 12px**, compact **128px / 18px**, comfortable **178px / 24px**. ≤900px it becomes `minmax(108px, 1fr)` with a 14px gap.

#### Miniature card — the important component

A `card` with `position: relative`, `padding: var(--space-2)`, `gap: var(--space-2)`. Background is `--color-surface` when owned, and `color-mix(--color-surface 55%, --color-bg)` when not — so unowned cards sit back.

The **plate**: `aspect-ratio: 3/4`, `overflow: hidden`, ground `--color-neutral-900`, cursor pointer, clicking anywhere on it toggles owned.

- Photo layer: cover-positioned background image, `transition: opacity .18s, filter .18s`.
  - Owned: `opacity: 1; filter: grayscale(1) contrast(1.08)`.
  - Not owned: `opacity: .3; filter: grayscale(1) contrast(.9)` — present but recessed, so the grid reads as a map of the gaps.
- **Owned toggle** (top-right, `top/right: var(--space-2)`, 34×34, `z-index: 3`, `border: 2px`, `display: grid; place-items: center`):
  - Owned: accent border + accent fill, `--color-bg` tick, tick `opacity: 1`.
  - Not owned: border `--color-bg` @60%, fill `--color-neutral-900` @45%, tick `opacity: 0` (an empty box over the photo).
  - Tick icon: 22px inline SVG, `stroke-width: 3`, `stroke-linecap: square`, path `m5 12.5 4.5 4.5L19 7`.
- **Wanted toggle** (the "actively looking for this" control) — a second 34×34 square directly below the owned box at `top: calc(var(--space-2) + 40px)`, same right edge, same border treatment:
  - **Only rendered when the miniature is not owned** (`display: none` when owned) — owning something ends the hunt.
  - Wanted: accent border + accent fill, `--color-bg` glyph. Not wanted: border `--color-bg` @60%, fill `--color-neutral-900` @45%, glyph `--color-bg` @70% (visible but quiet — unlike the tick, the crosshair always shows so the affordance is discoverable).
  - Icon: 18px crosshair, `stroke-width: 2.4`, `stroke-linecap: square` — `<circle cx=12 cy=12 r=7>` plus four ticks `M12 1v3 M12 20v3 M1 12h3 M20 12h3`.
  - `title` is "I am looking for this" / "Stop looking for this".
- **WANTED strip** — when wanted, a full-width bar pinned to the bottom of the plate (`left/right/bottom: 0`, `z-index: 3`): `--color-accent` fill, `--color-bg` text, heading font weight 800, **9px**, `letter-spacing: .16em`, `padding: 3px 6px`, label flush left. This is the state's at-a-glance signal in a dense grid.
- Two alternative owned-marks exist behind a prop (see Props below): a rotated "OWNED" **stamp** (3px accent border, `rotate(-12deg)`, accent text 15px `.14em`) and a **corner fold** (30px accent triangle, top-right). Default is the checkbox.

Caption below the plate: code at **10px** uppercase `.1em` (accent-700 when owned, text @40% when not), name in heading font **11.5px/1.2** (full ink when owned, text @52% when not). Both are individually toggleable via props; a "No info" label at 10px @32% covers the caption-off case.

**Empty state** — `padding: 56px 0` between two 2px rules; heading font 20px title + 13px body @55%. Copy varies: filtered-to-nothing ("Nothing matches this filter" / "Switch back to All to see the whole set.") vs. an unpopulated set ("No miniatures in this set yet" / "This set is catalogued but its miniatures have not been added.").

### 3. Admin — Ranges & sets

`display: grid; grid-template-columns: 200px minmax(0,1fr)`; min-height `calc(100vh - 53px)`.

**Rail:** `--color-surface`, `border-right: 2px`, sticky under the header, vertical nav links. ≤900px it becomes a horizontally scrolling row with a 2px bottom border instead.

**Main:** `padding: 22px 26px 60px`. Section head is a flex row over a 2px rule (`padding-bottom: 14px`, `margin-bottom: 18px`): kicker "MANAGE" (10px uppercase @50%), `h2` "Ranges & sets", and a primary **"New range"** button with a leading 16px `add` glyph.

Per range: `h3` 22px + uppercase meta @50%, then right-aligned secondary **"Edit range"** (`edit`) and primary **"Add set"** (`add`) at 12px. Below, a `.table` with columns Code (110px) / Set / Miniatures (130px); rows are clickable and hover-tinted (`--color-text` @4%). If a range has no sets: a 22px-padded block under a 2px rule — "No sets in this range yet" / "Add a set to start listing miniatures under {range}."

### 4. Admin — Set detail

Breadcrumb ("← Ranges & sets / Range name"), `h2` "{CODE} {Set name}", count line at 11px uppercase @50%, and secondary **"Edit set"** + primary **"Add miniature"**.

Table columns: drag handle (28px, `⠿`, `cursor: grab`), thumbnail (46px — a 30×38 plate with the photo), Code (110px, heading font `.08em`), Name (500 weight), and a right-aligned danger icon-button (`delete`). Rows are **drag-to-reorder** (HTML5 drag events; the dragged row and the drop target are visually marked) and clicking a row opens the drawer.

Empty state: `padding: 34px 0` between 2px rules — "No miniatures in this set yet" / "Add the first casting and it appears in the public catalogue straight away."

### 5. Admin — Miniature drawer

Right-hand sheet over a scrim (`--color-neutral-900` @42%, `z-index: 60`); clicking the scrim closes. Panel **480px** (`max-width: 92vw`), full height, scrollable, `--color-bg`, `border-left: 2px`, `--shadow-lg`, `padding: 22px 24px 40px`. Full-width ≤900px with no left border.

- Head: accent kicker, `h3` name, ghost `×` at 18px pushed right.
- Photo drop zone: a plate that accepts **drag-and-drop or click-to-pick** (hidden `<input type="file" accept="image/*">`). Empty state centres an `add_photo_alternate` glyph at 26px over a 12px hint, in `--color-bg` @62%; the zone highlights while a file is dragged over. With a photo: filename at 11.5px @55% plus `swap_horiz` (replace) and danger `delete` icon-buttons.
- Fields: a `118px 1fr` grid — Code and Name — then right-aligned secondary "Cancel" and primary "Save miniature".

**Currently non-functional:** field edits don't persist and Save just closes. The upload is preview-only (object URL held in memory). Both need real wiring.

### 6. Auth (sign in / sign up)

Two equal columns, `min-height: calc(100vh - 53px)`; stacks ≤900px.

Left (`padding: 64px 56px`, `max-width: 560px`, vertically centred): accent kicker, `h1` **46px/1.02**, blurb 14px/1.55 @62% `max-width: 42ch`, a two-option segmented tab (Sign in / Sign up) in a 1px divider box, then the form at `max-width: 400px`, gap 16px:
- Sign up only: "Collector name" (placeholder "How you appear on the archive").
- Email (`you@example.com`), Password (`••••••••`); sign-up adds "Eight characters or more." at 11.5px @50%.
- Sign in only: a "Keep me signed in" checkbox (15px, `accent-color: --color-accent`) and a "Forgotten password" link.
- Primary block button, **label flush left**, then a switch line ("New here? / Create an account").

Right (`--color-surface`, `border-left: 2px`, same padding): "WHAT AN ACCOUNT GIVES YOU" label, then three points, each `padding: 18px 0` under a 2px top rule — heading font 18px title over 13.5px/1.5 body @60% `max-width: 40ch`.

### 7. Dialogs

Two modals, both `.dialog-backdrop` + `.dialog` from the design system:

- **Range / set editor** (`z-index: 70`) — title, optional Code field, Name field, optional Genre `<select>`, an optional ghost **Delete** row (with a 16px `delete` glyph, `padding-inline: 0`), then Cancel + primary save.
- **Delete confirmation** (`z-index: 75`, sits above the editor) — title, body, Cancel + primary destructive action.

Neither closes on **Esc** yet — add that, plus focus trapping and initial focus, when porting.

---

## Interactions & Behaviour

**Navigation** is state-driven, not routed. The prototype switches on a `screen` value (`browse` / `admin` / `auth`) plus a `setFilter` (the open set) and `adminSetId`. **Give each of these a real URL when porting**: e.g. `/`, `/sets/:setCode`, `/admin/ranges`, `/admin/sets/:setCode`, `/signin`. Every navigation scrolls the window to the top.

**Owning a miniature** — click the plate or the tick box. If not signed in, the app scrolls to top and switches to the sign-in screen instead (the intent is not remembered — worth improving: resume the tick after auth). "Tick whole set" owns all miniatures in the set, or clears them all if every one is already owned.

**Wanting a miniature** — click the crosshair. Same auth gate. Wanted is only meaningful while unowned: the flag is ignored (and its control hidden) once the miniature is owned. Decide on port whether ticking *owned* should also clear a stored `wanted` row, or leave it dormant so un-ticking restores the hunt; the prototype leaves the record and just masks it.

**Filters** are mutually exclusive: All / Owned / Missing / Wanted, applied over the current set's miniatures. Wanted = not owned AND flagged.

**Density** changes grid column width and gap only; it persists in state (and is also exposed as a prop default).

**Drag-to-reorder** in the admin miniature table uses native HTML5 drag events, with the drag source and hovered drop target styled; drop commits the new order.

**Transitions** — the only animation is the miniature photo's `opacity`/`filter` at **.18s**. Hovers are instant. Keep it flat and quick.

**Focus** — the design system provides `:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 2px; }`. Don't lose it: the plate is clickable and both toggles are real `<button>`s, so they must be keyboard-reachable and announce their state (`aria-pressed` on both toggles; the plate click should not create a duplicate tab stop).

**Responsive** — a single breakpoint at **900px**. Both rails collapse (public rail hides, admin rail becomes a scrolling row), grids go to one column (miniature grid to `minmax(108px,1fr)`), the drawer goes full-width, headings step down (h1 44→32px, h2 →26px), and the filter bar unsticks. Header padding tightens and the brand subtitle hides.

---

## State Management

Prototype state, and what it should become:

| State | Prototype | Port as |
| --- | --- | --- |
| `owned: {code: 1}` | in-memory, seeded fake on mount | per-user persisted collection rows |
| `wanted: {code: 1}` | in-memory | per-user wishlist rows |
| `signedIn` | boolean | real session |
| `screen`, `setFilter`, `adminSetId` | view switches | routes |
| `expanded: {rangeCode: true}` | rail disclosure | local UI state (may persist) |
| `ownedOnly` / `missingOnly` / `wantedOnly` | filter flags | one enum + query param |
| `density` | grid density | local preference, persisted |
| `photoOverrides: {code: objectURL}` | in-memory upload preview | real asset upload |
| `editor`, `confirm`, `drawerId` | open dialog / drawer | modal state |
| `drag`, `dragOver` | reorder | reorder + persisted `sortOrder` |
| `query`, `page` | present but unused | search + pagination were cut from this design; don't build UI for them unless asked |

**Data shape** implied by the prototype:

- **Range** — `code`, `name`, `genre` (Fantasy / Sci-fi / Specialist games), plus derived set and miniature counts.
- **Set** — `code` (e.g. `C11`), `name`, `rangeCode`, ordered miniatures.
- **Miniature** — `code`, `name`, `setCode`, `rangeCode`, `photo`, `sortOrder`.
- **Per-user** — `owned` and `wanted` joins on miniature code.

Ranges sort alphabetically; sets sort by code with numeric-aware comparison (`localeCompare(…, {numeric: true})`) so `C1` precedes `C11`. Genre sections follow a fixed genre order, and genres with no ranges are omitted. Counts shown per set, per range, and in the header are all derived — don't store them.

## Props (prototype tweak controls)

The prototype exposes these to allow variants; they are **not** required in production, but they document decisions:

- `gridDensity` — Contact sheet / Compact / Comfortable (default Compact).
- `ownedMark` — Checkbox (default) / Stamped plate / Corner fold.
- Caption toggles for code and name.

Ship the defaults: Compact + Checkbox + both caption lines.

## Assets

- Miniature photographs in the prototype are placeholder JPEGs supplied by the user, in `uploads/` (`Extech.jpg`, `Female_Warrior_Jayne.jpg`, `Hero.jpg`, `Scum-1.jpg`). They stand in for real collection photography and are **not** production assets — expect user-uploaded images, rendered through the grayscale plate treatment.
- Interface icons: Material Symbols Outlined (loaded from Google Fonts) — `add`, `edit`, `delete`, `arrow_back`, `swap_horiz`, `add_photo_alternate`. Swap for the codebase's icon set.
- The owned tick and the wanted crosshair are inline SVG; exact paths are given in the miniature-card section above.
- Fonts: **Archivo** (heading 800, body 400/500) via the design system stylesheet.

## Files

- `Miniatures Catalogue.dc.html` — the full prototype (all screens). Template first, logic class after it.
- `support.js` — the prototype runtime. Reference only; do not port.
- `_ds/modernist/styles.css` — the Modernist design system tokens and component classes (`.btn`, `.card`, `.table`, `.dialog`, `.field`, `.input`, `.tag`, `.hr`, `.grayscale`). **This is the file to mine for values.**
- `_ds/modernist/readme.md` — the design system's own guidance (flush-left labels, 2px rules, zero radius, black-and-white photography, accent used sparingly).
- `uploads/` — placeholder photography.

## Known gaps to close on port

1. Owned + wanted persistence (currently session-only, seeded with fake data).
2. Drawer field editing and save (inputs are inert).
3. Real photo upload and storage (currently in-memory object URLs).
4. Replace the hard-coded header totals ("5,318", "412") with derived counts.
5. Esc-to-close, focus trap, and initial focus on both dialogs and the drawer.
6. Real routes and deep links for every screen.
7. Resume the intended tick after a sign-in interruption.
8. Reconcile the header brand subtitle ("Oldhammer archive") with the collection framing.
