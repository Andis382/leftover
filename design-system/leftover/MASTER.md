# Leftover — Design System (Master)

Source of truth for every screen. A file in `pages/` overrides this one where
they disagree; otherwise this one wins.

Built with the **UI/UX Pro Max** skill and then reconciled by hand. The skill's
own contract says to verify the returned category and top result before using
it, to retry once when the result is off-topic, and never to persist unverified
output — so each row below records the search it came from, and where a
generated suggestion was rejected, why.

```bash
python3 .claude/skills/ui-ux-pro-max/scripts/search.py \
  "bakery shop daily counting log warm paper" --design-system \
  --variance 5 --motion 3 --density 5 -p "Leftover"
python3 .claude/skills/ui-ux-pro-max/scripts/search.py "bakery food artisan warm" --domain color
python3 .claude/skills/ui-ux-pro-max/scripts/search.py "editorial print paper warm serif craft" --domain style
python3 .claude/skills/ui-ux-pro-max/scripts/search.py "warm humanist serif editorial food craft" --domain typography
```

---

## 1. Product

| | |
|---|---|
| **Type** | A small-business daily log. One shop, one owner, one phone. |
| **User** | A baker, at twenty past eight at night, after eleven hours standing. And the same baker at four in the morning, reading. |
| **The job** | Forty seconds of counting; a plan before the ovens go on. |
| **Stack** | Laravel 12 + Blade, hand-written CSS, no Node, no build step (`--stack laravel`). |

The single most important design fact: **this app asks a tired person to do
something every night.** It gets one chance to feel like a notebook and not like
a manager. Everything below follows from that.

## 2. Style — E-Ink / Paper, warmed by the bakery palette

`--domain style` → `e-ink-paper` (active): "Paper-like, matte, high contrast,
texture, reading, calm, slow tech", **Best For** "minimal journals". Its design
variables are `--paper-bg: #FDFBF7`, `--ink-color`, `--pencil-grey`, and
`--transition: none`.

Read plainly: **the interface is a tally sheet**, and not as a theme. A bakery
that counts anything counts it on a ruled sheet by the till. That sheet works —
ruled rows, a margin, a name on the left, a number on the right, fillable with
one hand while holding a tray. What it cannot do is become an average, and that
is the only thing the software adds. So the screen keeps paper's shape and
paper's stillness, and a red margin rule fills in as you go down the list.

> **Rejected.** The `--design-system` run returned *Vibrant & Block-based* ("Best
> For: Startups, creative agencies, gaming, youth-focused") with the *Funnel
> (3-Step Conversion)* landing pattern, and the *Indie/Craft* pairing of Amatic
> SC with Cabin. All three are real rows and all three are wrong here: a bouncy
> block layout and a handwritten display face are a cartoon of a bakery, aimed at
> someone browsing, not at someone counting.

**Deliberately unlike InstallBook.** The other product in this pair is a Swiss
register — square, navy, engineered. Two products by the same hand should not
look like the same product, and a bakery is not an equipment log.

## 3. Colour tokens

`--domain color` → the **Bakery/Cafe** palette, "Warm brown + cream white":
primary `#92400E`, background `#FEF3C7`, foreground `#78350F`, border `#FDE68A`.

| Role | Light | Dark | Notes |
|---|---|---|---|
| `--ground` | `#f7edd6` | `#15110c` | the palette's `#FEF3C7`, one step down in chroma — at full-page scale it fought the text |
| `--sheet` | `#fdfbf7` | `#1e1810` | e-ink's `--paper-bg`, verbatim |
| `--ink` | `#3a2a18` | `#f2e9db` | e-ink's ink black warmed toward the bakery brown; `#78350F` is the accent, not the body |
| `--ink-soft` / `--ink-faint` | `#6b5237` / `#7e6748` | `#c4b49b` / `#a99578` | both clear 4.5:1 on `--sheet` |
| `--rule` / `--rule-strong` | `#e4d6b4` / `#c9b48a` | `#3a3022` / `#554832` | the palette's `#FDE68A` darkened until a hairline is actually visible |
| `--margin-rule` | `#c0524a` | `#8c4a44` | the red line down the left of an exercise book |
| `--brand` | `#92400e` | `#e9a86a` | verbatim |
| `--level` | `#3f6212` on `#ecf2df` | `#a3cc6a` on `#1f2812` | about right |
| `--over` | `#9a3412` on `#f9e7dc` | `#f0a183` on `#2e1a12` | left on the shelf |
| `--short` | `#8a6100` on `#faefd2` | `#e8bd63` on `#2d2410` | ran out |

**Three states, not two.** Most products have "good" and "bad". This one needs a
third, because **selling out is its own kind of expensive** and nothing else in
the shop will ever tell you so. It gets its own token, its own tint and its own
place on the dashboard beside waste, at the same size.

Every pair in both themes was checked against 4.5:1. Dark is a separate palette,
not an inversion — warm, because the app is genuinely read at four in the
morning and this is the same paper under a kitchen bulb.

## 4. Typography — Restaurant Menu

`--domain typography` → **Restaurant Menu**: Playfair Display SC headings, Karla
body, "Best For: Restaurants, cafes, food blogs, culinary". Extended by one
step: **Playfair Display** (roman) for the figures.

- **Playfair Display SC** — page and section headings. Small caps is the
  lettering of a menu board, the one piece of typography every bakery owns.
- **Playfair Display** — every number meant to be read from across a kitchen:
  the waste total, the counters, the move on a plan line. A figure set like a
  price on a board gets looked at; the same figure in the UI face does not.
- **Karla** — everything anybody types into, and all body text.

Scale: 11 · 13 · 15 · 16 · 18 · 22 · 28 · 38 · 48. Body never below 16px.
Every number carries `font-variant-numeric: tabular-nums`, because every number
here is being compared with another number.

## 5. Spacing, grid, radius

4px rhythm (`--s-1` 4 → `--s-8` 64, `--density 5`). `--radius: 2px` — paper has
a cut edge, not a bevel, but not a hard machine corner either. `--tap: 48px`,
enforced by test. Number boxes are 56px tall and centred: they are typed into
with a thumb, in a hurry.

## 6. Icons — Phosphor, regular weight

The ~97 glyphs actually used are copied from `@phosphor-icons/core` (MIT) into
`resources/icons.php` as raw path data, rendered by `<x-icon name="…" />`, so
there is no icon package, no icon font and no build step. Regenerate with
`scripts/build-icons.py`.

Emoji are banned and the ban is tested: they are drawn by whatever font the
phone carries, differ between Android and iOS, and cannot take a colour from a
token.

## 7. Motion — `--motion 3`, and less than that

The style row says it outright: *"No smooth transitions, distinct page turns,
sharp"*. Colours change over about a tenth of a second and nothing moves.
Paper does not ease, and neither does anything at eight at night.
`prefers-reduced-motion: reduce` removes even that, and it is tested.

## 8. Rules this interface keeps

- **Nothing on the counting screen is required.** Two rows filled in and
  twenty-eight blank is a real record; the blanks are absent from the history,
  never zero.
- **Every field has a visible label**; the error sits under its own field, tied
  with `aria-describedby` and `aria-invalid`, *and* a failed form gets a
  focusable summary at the top that links to each bad field.
- **Colour is the third signal.** Every status tag carries an icon and a word.
- **48px targets, 8px apart. Visible focus, never removed. Skip link.**
- **A count announced as a sentence**, never as a bare number, for a screen
  reader.
- **Mobile first**, `min-height: 100dvh`, no horizontal scroll at 375px,
  `env(safe-area-inset-bottom)` under the tab bar.
- **Five bottom destinations**, each with an icon *and* a word.

## 9. Anti-patterns

- Emoji as icons *(tested against)*
- A raw hex in a screen *(tested against)*
- Colour as the only signal
- A status tag without an icon *(tested against)*
- Scolding, selling, or giving orders in the plan's wording *(tested against, in
  every shipped language)*
- Playful display faces on a screen used by somebody exhausted
- Small text — nothing below 11px, and 11px only for uppercase labels
