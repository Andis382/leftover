# Leftover

**A forty-second count of what did not sell, and a bake plan at four in the morning that gets a little less wrong every week.**

A small bakery decides at four in the morning how many of each of thirty things to bake, by feel. Whatever is left at closing goes in a bag out the back. Whatever sold out by ten is a queue of people nobody counted. Neither number is written down anywhere, so the same Monday over-bake and the same Saturday shortage repeat for years, and eight to fifteen per cent of production is treated as weather.

```
count at closing  →  baked, left, and when the shelf emptied
                  →  a plan before you get up
                  →  "6 fewer croissants, so 42. About 7 left over each time."
```

---

## The one idea

**A sell-out is not a good day.**

If the shelf emptied at ten in the morning, the number sold is not the number wanted — it is a floor, and everyone who came at eleven is invisible. No till in the world records a sale that did not happen, which is why every report a bakery can run makes a sell-out look like a triumph.

So this records it on purpose, with roughly what time it happened, and a day that emptied early counts as more demand than it served — proportionally, up to thirty per cent. That single correction is the difference between averaging your till and learning what people actually wanted.

Two safety rails matter as much as the arithmetic:

**Nothing is said until there is something to say.** Under two counted days of the same weekday, a line stays silent and says so. It never invents a number to look useful.

**No suggestion may move production by more than a fifth.** Three weeks of data is not entitled to tell somebody to halve their Saturday. It is a nudge, and a nudge that gets to be right slowly is worth more than an order that is spectacularly wrong once.

There is no machine learning in this and there should not be. A bakery has twenty to forty products and a few dozen observations each. Anything clever would be fitting noise.

---

## What it does

| | |
|---|---|
| **The closing count** | A ruled tally sheet on a phone: baked, left, and — if it went — roughly when. Skip anything you did not bake. Nothing is compulsory, and saving again corrects the day rather than duplicating it. |
| **The plan** | At an hour each shop chooses, tomorrow's numbers from the last few of the same weekday, in plain words: the change, then the total, then why. |
| **Waste in money** | "Thirty-one croissants, forty-six euro" is an argument. "Some waste" is not. Per day, per product, per month. |
| **Sold-out, counted** | Shown beside waste, at the same size, because it is the same size of mistake and the only one nothing else will ever tell you about. |
| **A skipped day** | Makes the sample smaller, never a zero. A day the shop was shut is recorded as shut and left out of every average. |
| **Languages** | Albanian and English, and the plan is always written in the shop's language whatever the server is doing. |
| **Delivery** | The plan waits on its page by default — no account anywhere. Optionally a Telegram bot pushes it. Every plan screen has a one-tap link that opens it in your own WhatsApp. |

---

## Running it

Needs PHP 8.2+ and Composer. No Node, no build step, no `npm install`: the CSS is hand-written.

```bash
git clone https://github.com/Andis382/leftover.git
cd leftover
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Open http://localhost:8000 and log in as `demo@leftover.test` / `password`. The seed is a bakery with ten weeks behind it, including a croissant line that is consistently over-baked, a sesame roll that keeps selling out on Saturdays, a week the shop was shut, a day nobody counted, and one product added too recently to say anything about.

**Database.** SQLite by default, which needs no setup but does need `pdo_sqlite`. For Postgres set `DB_CONNECTION=pgsql` and the usual credentials.

**The plan.** One cron line, hourly:

```
* * * * * cd /path/to/leftover && php artisan schedule:run >> /dev/null 2>&1
```

Each shop carries its own plan hour and its own timezone, and is served only when its own clock reaches it, so one line covers a bakery in Tirana and one in Hamburg. Or run it directly:

```bash
php artisan leftover:plan --now --dry-run      # show what it would say
php artisan leftover:plan --date=2026-09-22
```

**Tests.**

```bash
php artisan test
```

51 tests. If your PHP has no `pdo_sqlite`, point the suite at a real database: `DB_CONNECTION=pgsql DB_DATABASE=leftover_test php artisan test`.

---

## How the forecast works

```
the last 8 of the same weekday, counted days only
   ├─ sold        = baked − left
   ├─ sold out?   × up to 1.30, in proportion to how early it emptied
   ├─ weight      × 0.85 per week further back
   └─ target      = the weighted mean

   then: capped to ±20% of what you last baked
         rounded to the tray size
         silent below 2 counted days, and it says so
```

Everything is a row you can look at. `app/Services/Forecaster.php` is 150 lines and every rule it promises is pinned in `tests/Unit/ForecasterTest.php`, because a suggestion that is ten per cent too low every Tuesday looks exactly like a suggestion that is right.

---

## How it talks

A tool that asks a tired person to do something every night gets one chance to sound like a colleague rather than a supervisor. `tests/Unit/PlanToneTest.php` holds the shipped wording — in every language — to three rules:

- **It states the change and then the total.** "Five fewer, so forty-five." Nobody converts a percentage at four in the morning.
- **It never gives an order.** No *must*, no *should*, no *optimal*. The baker knows things the notebook does not: a funeral, a school trip, rain since six.
- **It never scolds.** Waste is a fact, not a failure. "You wasted seven again" is how an app gets deleted in week three.

Nothing is ever sent about a day somebody skipped. The only nag in the product is one line on the front page saying yesterday was never counted, and a button next to it saying the shop was shut.

---

## Layout

```
app/
  Services/
    Forecaster.php         the arithmetic, and nothing else
    PlanBuilder.php        history → lines → the message, in the shop's language
    SheetRecorder.php      saving a count, twice if need be
    WasteReport.php        what the bin took, in money
    Notify/                page (default) · telegram · log
  Support/Observation.php  one counted day, as four facts
  Support/Suggestion.php   the answer, and the working behind it
  Models/                  Product · DaySheet · Count · Plan · PlanLine
resources/views/           Blade, mobile first
resources/icons.php        the Phosphor glyphs used, as path data
design-system/leftover/    tokens, type scale, the rules and why
public/css/app.css         hand written, no build step
lang/{sq,en}/              the interface, and every word the plan says
tests/                     51: the forecast, the count, the plan, tone, tenancy
```

---

## What it looks like, and why

A tally sheet. Not as a theme — because that is what it replaces. A bakery that counts anything counts it on a ruled sheet by the till, and the sheet works: ruled rows, a margin, a name on the left, a number on the right, fillable with one hand while holding a tray. What paper cannot do is become an average, and that is the only thing the software adds.

So: paper ground, ink-brown type, Playfair Display for figures meant to be read from across a kitchen, Karla for everything you type into, a red margin rule that fills in as you go down the list, and almost no motion at all. The whole system, and the reasoning behind each token, is in [`design-system/leftover/MASTER.md`](design-system/leftover/MASTER.md).

Three rules are enforced by `tests/Unit/InterfaceDisciplineTest.php` rather than remembered: no emoji as icons, no status that means something only by colour, and a 48px tap-target floor with visible focus and honoured reduced motion.

---

## Honest notes

- **This category exists and works.** Delicious Data has 250+ bakeries and cites returns down 22%; foodforecast, Meteolytix and Prognosix all do bakery forecasting properly. Every one of them assumes a POS export or an ERP and is priced per branch. The claim here is narrow: the one-shop bakery has no POS export, no back office and nobody entering anything into a computer at the end of the day, and nothing asks it for the one number the till can never know.
- **It demands a nightly habit.** That is the real risk, not the arithmetic. The count has to stay under a minute and has to survive being skipped, which is why nothing is required and why a missed day costs a smaller sample rather than a wrong one. Whether a tired baker actually does it every night is a question only real shops can answer.
- **Weather, holidays and the school calendar are not in it.** They matter, and a weekday average quietly absorbs some of them. Adding them before the basic habit is proven would be solving the interesting problem instead of the real one.
- **Waste in money is only as good as the prices you enter.** Cost is the truer number and most small bakeries do not know it per item; the app uses price when cost is missing and says which it used.
- **No POS integration, no stock, no invoicing.** Deliberately. Those are what make the competition cost what it costs.

## Roadmap

- A count that works with no signal, with a real outbox
- Weather as an input, once there is enough history to tell whether it earns its place
- Holidays and school terms per country
- A weekly summary worth reading on a Sunday
- Multi-branch, only if a shop with two branches asks for it

## Licence

MIT. See [LICENSE](LICENSE).
