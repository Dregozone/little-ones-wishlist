# Little Ones Wishlist

A private, no-signup baby gift list. Friends and family open one link, see everything the
parents still need, and tap **"I will get this"** on the gifts they want to buy. Nobody
doubles up, nobody has to make an account, and the parents never find out who is buying what.

Built with Laravel 13, Livewire 4 and Flux UI.

---

## Table of contents

- [Why this exists](#why-this-exists)
- [Features](#features)
- [How guest identity works](#how-guest-identity-works)
- [Tech stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Setup](#setup)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Guest guide](#guest-guide)
  - [Admin guide](#admin-guide)
- [Make it yours](#make-it-yours)
- [Testing and code quality](#testing-and-code-quality)
- [Project structure](#project-structure)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [Security](#security)
- [License](#license)

---

## Why this exists

Shop-hosted baby registries want an account from everyone who touches them, tie you to one
retailer, and cheerfully email the parents a running commentary on who bought what. This is a
small self-hosted alternative:

- **Guests never register.** No email, no password, no name. One link is the whole onboarding.
- **Claims are anonymous.** The parents' admin screen shows *that* an item is claimed. It never
  shows, or stores anywhere it could show, *who* claimed it.
- **Any shop.** Items are just a name, a shop, a price and an optional link, so the list can
  span a dozen retailers and a hand-me-down from an aunt.
- **One claim per item.** A database-level unique constraint, not a hopeful `if` statement, so
  two people tapping at the same moment cannot both end up buying the mobile.

## Features

**For guests**

- A friendly name gate ("Who is this baby list for?") that keeps out crawlers and passers-by
  who stumble on the URL, while tolerating typos, accents, case and joining words.
- Items grouped into **Still available**, **You are getting**, and **Spoken for**.
- Live search plus a price-range filter, both reflected in the URL so a filtered view is
  shareable and survives a refresh.
- A progress bar for the whole list — "17 of 40 gifts spoken for" — that ignores your filters.
- Claim and un-claim, with a confirmation step before letting go of something.
- Optional cookie consent, explained in plain English. Decline it and the list still works;
  you just will not be recognised next time.
- A **personal recovery link** you can bookmark or message to yourself to bring your picks
  back on any device.
- Light and dark themes, defaulting to dark, with the choice remembered per device.
- Mobile-first layout, 44px minimum touch targets, screen-reader announcements for live totals.

**For the parents (admin)**

- Passwordless-capable sign-in (password or passkey). **Public registration is switched off** —
  accounts are created from the command line only.
- A single management table with a pinned quick-add row: name, shop, price, and you are done.
- Click-to-edit cells for name, shop and price, saved in place.
- A per-item drawer for the long fields: product link, image link and description, with a live
  preview of how guests will see the card.
- Hide an item from guests without deleting it.
- Free up a claim, for when someone tells you in person that they have changed their mind.
- Delete an item, behind a confirmation.
- Claim **counts** only — never claimant identities.

## How guest identity works

Guests never log in, but losing somebody's commitments would be worse than mildly annoying, so
identity is layered:

1. **A server-set, encrypted, HttpOnly cookie** (`wl_guest`) is the durable handle. Safari's
   tracking prevention caps script-written cookies and `localStorage` at seven days but leaves
   server-set cookies alone.
2. **A `localStorage` mirror** of the same token, replayed through the recovery route if the
   cookie is ever cleared.
3. **A salted IP hash**, used only to *offer* a match behind a "was this you?" prompt. Silently
   adopting an IP match would hand one guest another guest's claims on shared household wifi.

Only the SHA-256 digest of a token is stored (`guest_tokens.token_hash`); the plaintext lives on
the guest's device. Recognising a guest **mints an additional token** rather than replacing the
existing one, so picking the list up on a second device never locks the first one out. IP
addresses are only ever stored salted-hashed with the app key.

The `guests` table has no name, no email and no raw IP. That is deliberate and is the product's
core promise — see [`.ai/rules/pages.md`](.ai/rules/pages.md).

## Tech stack

| Layer | Choice |
| --- | --- |
| Backend | PHP 8.4, Laravel 13 |
| Frontend | Livewire 4 (single-file components), Alpine.js, Flux UI 2, Tailwind CSS 4 |
| Auth | Laravel Fortify (password reset + passkeys; registration disabled) |
| Database | SQLite by default; any Laravel-supported driver works |
| Build | Vite 8 |
| Tests | Pest 5 |
| Static analysis | Larastan / PHPStan |
| Formatting | Laravel Pint |
| Monitoring | Laravel Nightwatch (optional) |

---

## Prerequisites

- **PHP 8.4** (8.3 is the declared minimum; CI runs 8.4) with the usual Laravel extensions,
  plus `pdo_sqlite` and `sqlite3` if you use the default database
- **Composer 2**
- **Node.js 22+** and npm
- **Git**

Nothing else. No Docker, no Redis, no mail server needed for local development. If you use
[Laravel Herd](https://herd.laravel.com/) or Valet, the site is served for you and you can skip
`php artisan serve`.

## Setup

```bash
git clone https://github.com/Dregozone/little-ones-wishlist.git
cd little-ones-wishlist

# Installs PHP and JS dependencies, copies .env, generates a key,
# runs migrations and builds the front-end assets.
composer setup
```

`composer setup` does not create the SQLite file, so if `database/database.sqlite` does not
exist yet:

```bash
touch database/database.sqlite   # Windows PowerShell: New-Item database/database.sqlite
php artisan migrate
```

Seed a realistic sample list (20 items, six of them already claimed) so there is something to
look at:

```bash
php artisan db:seed
```

Create the account you will manage the list with — this is the **only** way to get an account,
as public registration is disabled:

```bash
php artisan make:admin you@example.com 'a-long-strong-password' --name="Your Name"
```

Then start everything (server, queue worker and Vite together):

```bash
composer dev
```

Visit **http://localhost:8000**. You will be asked who the list is for — out of the box the
accepted answers are `Emma`, `Anders` or `Learmonth`. Change those in
[Make it yours](#make-it-yours).

Sign in at **/login** and manage the list at **/admin/items**.

### Useful commands

| Command | What it does |
| --- | --- |
| `composer dev` | Serve, queue worker and Vite concurrently |
| `composer setup` | Full first-time install |
| `composer test` | Config clear, Pint check, PHPStan, then the test suite |
| `composer lint` | Fix formatting with Pint |
| `composer types:check` | PHPStan / Larastan |
| `php artisan test --compact` | Tests only |
| `php artisan make:admin <email> <password>` | Create an admin (`--force` resets an existing password) |
| `php artisan db:seed` | Sample wishlist data |
| `php artisan pail` | Tail application logs |

## Configuration

Everything lives in `.env`. The defaults in `.env.example` are aimed at local development.

| Variable | Default | Notes |
| --- | --- | --- |
| `APP_NAME` | `Laravel` | Shown in the browser tab and mail; set it to your list's name |
| `APP_URL` | `http://localhost:8000` | Must match how you actually reach the app; recovery links are built from it |
| `APP_ENV` / `APP_DEBUG` | `local` / `true` | Set to `production` / `false` when deploying |
| `DB_CONNECTION` | `sqlite` | Switch to `mysql`/`pgsql` and fill in `DB_*` if you prefer |
| `SESSION_DRIVER` | `database` | Sessions back the "already answered the gate" check |
| `QUEUE_CONNECTION` | `database` | `composer dev` runs a worker for you |
| `MAIL_MAILER` | `log` | Only used for password-reset emails. Point at real SMTP if you want them to arrive |
| `NIGHTWATCH_TOKEN` | *(empty)* | Optional. Leave blank to skip Laravel Nightwatch |
| `LOG_CHANNEL` | `nightwatch` | Set to `stack` if you are not using Nightwatch |

Cookies and their lifetimes are defined in code, not env:

| Cookie | Set by | Purpose | Lifetime |
| --- | --- | --- | --- |
| `wl_verified` | `EnsureGuestHasVerified` | Remembers the name gate was passed | 1 year |
| `wl_consent` | `ManageCookieConsent` | Remembers the yes/no on the identity cookie | 1 year |
| `wl_guest` | `ResolveGuest` | The device's identity token — only set after consent | 1 year |

---

## Usage

### Guest guide

Guests need nothing but the URL. Send them the root address of the site.

**1. Get past the name gate**

1. Open the link. You land on **"Who is this baby list for?"**.
2. Type a parent's first name or the family surname and press **Show me the list**.
3. Case, accents, punctuation, "and"/"plus", and single-character typos are all forgiven.
   A wrong name is not, and ten wrong tries in a minute earns a short cool-down.
4. Once you are through, this browser is remembered for a year — you will not be asked again.

**2. Decide about the cookie**

1. A banner appears at the bottom of the list explaining the one cookie that remembers your picks.
2. **Accept** to be recognised on your next visit, or **decline** and carry on.
3. Declining does not weaken your claims — an item you claim is still locked to you for this
   session; you just will not be recognised when you come back later.

**3. Find a gift**

1. Type in the **search box** to filter by item name, shop or description.
2. Drag the **price slider** to set a budget range; it applies when you let go.
3. The search and price range go into the URL, so you can bookmark or share a filtered view.
4. Press **Show everything** to clear all filters.
5. Items are in three groups: **Still available**, **You are getting**, and **Spoken for**
   (collapsed by default — those are already handled).

**4. Claim a gift**

1. On an available item, press **"I will get this"**.
2. The card moves into **You are getting** and the progress bar ticks up.
3. If someone beat you to it by a second, you will be told and the item moves to
   **Spoken for** — nothing is double-booked.
4. Follow the **product link** on the card to go and actually buy it. Claiming does not buy
   anything; it only reserves the item on this list.

**5. Change your mind**

1. On an item in **You are getting**, press **Change my mind**.
2. Confirm with **Put it back**.
3. The item returns to **Still available** for anyone, including you, to pick up again.

**6. Keep your picks across devices**

1. Once you have claimed something and accepted the cookie, a green **"Keep your link"** card
   appears with a personal URL.
2. Copy it and message it to yourself, or bookmark it.
3. Opening that link on any phone or computer restores your picks there, in addition to the
   device you started on. Both keep working.
4. Press **Got it** to dismiss the card.

**7. If the list forgets you**

- If you return and your picks are not highlighted, look for **"Have you been here before?"**.
  That appears when someone on your internet connection has claimed items before. Press
  **"Yes, that was me"** to take them back, or **"No, that was someone else"** to dismiss it.
- Otherwise, open your saved personal link.

**8. Switch theme**

Use the sun/moon toggle at the top right of the list. The choice is remembered on that device.

### Admin guide

Admin pages live behind `/login` and require an account created with `php artisan make:admin`.

**1. Create an account**

```bash
php artisan make:admin emma@example.com 'a-long-strong-password' --name="Emma"
```

- `--name` is optional; it defaults to a tidied-up version of the email address.
- The password must meet the same policy as the web forms.
- Adding `--force` to an existing email resets that account's password instead of failing.

**2. Sign in**

1. Go to **/login**.
2. Enter email and password, or use a passkey if you have registered one.
3. Forgotten the password? **/forgot-password** emails a reset link — this needs real mail
   settings in `.env`, otherwise the link is written to `storage/logs/laravel.log`.

**3. Open the manager**

Go to **/admin/items**. The header shows a running total: how many items, how many claimed, how
many hidden. There is a link to view the public list as guests see it.

**4. Add an item**

1. In the pinned **Add an item** row, fill in **Item**, **Shop** and **Price (£)**.
2. Press **Add**, or hit Enter from any of the three fields.
3. The new row appears at the top of the table and flashes briefly.
4. Only those three fields are required. Link, image and description come next.

**5. Add a link, image and description**

1. Open the **⋮** menu on the row and choose **Link, image and description**.
2. A drawer opens under the row with:
   - **Product link** — where the item can be bought (http/https)
   - **Image link** — a direct URL to a product photo (http/https)
   - **Description** — up to 500 characters
3. The panel on the right previews exactly how the card will look to guests, updating as you
   type the image URL.
4. Press **Save**, or **Close** to abandon the changes.

**6. Edit name, shop or price**

1. Click the cell in the table.
2. Type the new value.
3. Press Enter to save or Escape to cancel. The row flashes to confirm.
4. Prices are entered in pounds and pence (`24.00`) and stored as pennies.

**7. Hide or show an item**

1. Open the **⋮** menu and choose **Hide from guests** (or **Show to guests**).
2. Hidden rows are dimmed and badged **Hidden**. They vanish from the public list and from its
   totals, but nothing is lost, and a hidden item cannot be claimed.

**8. Free up a claim**

1. Claimed rows are badged **Claimed**. The **⋮** menu gains **Free up the claim**.
2. Choose it and confirm.
3. The item goes straight back to available for any guest to pick up.
4. Use this when someone tells you in person that they have changed their mind. You will never
   be told which guest had claimed it — that is by design.

**9. Delete an item**

1. Open the **⋮** menu and choose **Delete**.
2. Confirm in the dialog.
3. The item and any claim against it are removed for everyone. This cannot be undone — prefer
   **Hide from guests** if you are unsure.

**10. Search the list**

Use the search box in the admin header. It matches item name, shop and description, and covers
hidden items too. The term is kept in the URL.

**11. Manage your account**

- **/settings/profile** — change your display name and email address.
- **/settings/security** — change your password, and register or remove passkeys for
  passwordless sign-in.
- **/settings/appearance** — light or dark theme.

---

## Make it yours

This started as one family's list, so a handful of personal details are baked in. Change these
and it is your list.

**1. The accepted answers to the name gate**

`app/Actions/VerifyParentNames.php`:

```php
private const ACCEPTED = ['emma', 'anders', 'learmonth'];
```

Lowercase, no accents, no punctuation — the answer is normalised before comparison. Names of
four characters or more also accept a single-character typo, so keep entries distinct enough
that one cannot be mistyped into another.

Then update `tests/Feature/Wishlist/VerificationGateTest.php`, which asserts against these names.

**2. The family name in the page copy**

Search for `Baby Learmonth` and replace it in:

- `resources/views/partials/wishlist-header.blade.php`
- `resources/views/partials/admin-header.blade.php`
- `resources/views/pages/wishlist/⚡verify.blade.php`

**3. The parents' names in the cookie banner**

`resources/views/pages/wishlist/⚡consent.blade.php` names "Emma and Anders" in the anonymity
promise. Change it to your names — but keep the promise itself, since the whole design rests on it.

**4. Currency and locale**

`app/Models/WishlistItem.php` formats prices as sterling:

```php
Number::currency($this->price, 'GBP', 'en_GB');
```

The stored column is `price_pennies`, an integer of minor units, which works for any two-decimal
currency. The admin's price label (`Price (£)`) is in `resources/views/pages/admin/⚡items.blade.php`.

**5. Sample data**

`database/seeders/WishlistItemSeeder.php` holds twenty UK-shop baby items. Replace or empty it —
it is only there so the layout can be judged against realistic content lengths.

**6. Colours and theme**

`resources/css/app.css` defines the palette as CSS custom properties: warm `oat` as the paper
ground, `sage` as the lead colour, terracotta as a sparing accent, plus the three status colours.
Change the hex values there and the whole app follows. The dark-mode default and its
`theme-color` values are in `resources/views/partials/appearance.blade.php`.

**7. Headline and strapline**

`resources/views/partials/wishlist-header.blade.php` — "The Wish List" and "Everything we need
before the baby arrives…". There is nothing baby-specific in the data model, so this works just
as well for a wedding, a housewarming or a big birthday.

---

## Testing and code quality

```bash
composer test          # Pint check + PHPStan + Pest — the same gate CI runs
php artisan test --compact
php artisan test --compact --filter=ClaimItemTest
composer lint          # Fix formatting
composer types:check   # PHPStan only
```

Tests run against an in-memory SQLite database (`phpunit.xml`) and refresh it per test, so
nothing touches your development data. Feature tests cover the name gate, claiming and
releasing, guest identity and recovery, the admin table, and — importantly — that no guest
identifier ever leaks into rendered output.

## Project structure

```
app/
  Actions/            Single-purpose invokable classes: ClaimWishlistItem,
                      ReleaseWishlistItem, ResolveGuest, VerifyParentNames,
                      ManageCookieConsent
  Console/Commands/   MakeAdminCommand
  Http/Middleware/    EnsureGuestHasVerified — the name gate
  Models/             WishlistItem, WishlistClaim, Guest, GuestToken, User
resources/views/
  pages/              Livewire single-file pages (⚡-prefixed)
    wishlist/         index, verify, consent
    admin/            items
    settings/         profile, security, appearance
  components/         Blade components (wishlist cards, sections, admin cells)
  layouts/            wishlist, admin, app, auth
  partials/           headers, appearance bootstrap
routes/web.php        Public list, gate, recovery link, admin
tests/Feature/        Pest feature tests
.ai/rules/            Committed project rules for humans and AI agents
```

Pages are Livewire 4 single-file components: the class and its template live in one
`⚡name.blade.php` file, and routes reference them as `pages::wishlist.index`.

## Deployment

This is an ordinary Laravel application — anything that runs Laravel runs it. Whatever you use:

1. **Set the environment.**

   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.example
   ```

   Generate a key with `php artisan key:generate` if you have not already. **Do not change
   `APP_KEY` after go-live**: it salts the stored IP hashes and encrypts the guest identity
   cookies, so rotating it makes every guest anonymous to the app again and they lose their picks.

2. **Pick a database.** SQLite is genuinely fine for a list a few dozen people read — just make
   sure the file lives on persistent storage and is backed up. For MySQL or Postgres, set the
   `DB_*` variables and run `php artisan migrate --force`.

3. **Build and optimise.**

   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan migrate --force
   php artisan optimize          # config, routes, views, events
   ```

4. **Run a queue worker** if you keep `QUEUE_CONNECTION=database` (password-reset mail goes
   through it), supervised by systemd, Supervisor or your host's process manager.

5. **Configure mail** if you want password-reset emails to arrive. Everything else works without it.

6. **Serve over HTTPS.** Passkeys require a secure context, and the identity cookies are only
   worth having over TLS.

7. **Create your admin account** on the server with `php artisan make:admin`.

8. **Optional: Laravel Nightwatch.** Set `NIGHTWATCH_TOKEN` and keep `LOG_CHANNEL=nightwatch`.
   Without a token, set `LOG_CHANNEL=stack`.

Re-run `php artisan optimize` (or `optimize:clear`) after each deploy.

## Contributing

Contributions are welcome — bug reports, accessibility fixes, and features that keep the "no
signup, no surveillance" character of the app.

### Before you start

- For anything more than a small fix, **open an issue first** so we can agree the approach
  before you spend time on it.
- Read [`.ai/rules/index.md`](.ai/rules/index.md) and the rule files it points at. They record
  settled decisions and non-obvious traps — for example, why components must read the session
  via the `session()` helper rather than `$request->session()`.
- Read [`CLAUDE.md`](CLAUDE.md) / [`AGENTS.md`](AGENTS.md) for the coding conventions. They are
  written for AI agents but they are simply this project's house style.

### Making a pull request

1. **Fork** the repository and clone your fork.
2. **Branch** from `master` with a descriptive name:
   ```bash
   git checkout -b feat/price-filter-presets
   ```
3. **Set up** with `composer setup`, then `php artisan migrate` and `php artisan db:seed`.
4. **Make your change**, following the conventions below.
5. **Add or update tests.** Every change needs a test — a new one or an updated existing one.
   ```bash
   php artisan test --compact --filter=YourTest
   ```
6. **Run the full gate** before pushing:
   ```bash
   composer test
   ```
   This is exactly what CI runs: Pint in check mode, PHPStan, then Pest. If Pint complains,
   `composer lint` fixes it.
7. **Commit** using [Conventional Commits](https://www.conventionalcommits.org/) with a scope,
   matching the existing history:
   ```
   feat(wishlist): add price preset chips to the filter bar
   fix(admin): keep the details drawer open when validation fails
   chore(deps): bump vite to 8.1
   ```
8. **Push and open a PR** against `master`. In the description, say what changed, why, and how
   you verified it. Screenshots or a short clip are very welcome for UI changes.
9. **Respond to review.** Push follow-up commits rather than force-pushing over the discussion.

Note: the CI workflow runs on every pull request and on pushes to `master`, so your change is
gated either way.

### Coding conventions

**PHP**

- Curly braces on every control structure, even single-line bodies.
- Explicit return types and parameter type hints everywhere.
- Constructor property promotion; no empty `__construct()`.
- PHPDoc blocks over inline comments, with array-shape types where they help. Reserve inline
  comments for genuinely tricky logic — and explain *why*, not *what*.
- TitleCase enum keys. Descriptive names (`isRegisteredForDiscounts`, not `discount()`).
- Use `php artisan make:*` to create files so they land in the right shape.

**Business logic**

- Non-trivial operations belong in `app/Actions` as single-purpose invokable classes, not in the
  Livewire component. Follow `ClaimWishlistItem` as the model.
- Authorise and validate in the action as you would in an HTTP request — never trust what the
  interface passed in. `ReleaseWishlistItem` re-checks ownership for exactly this reason.

**Livewire and Blade**

- Pages are single-file Livewire components in `resources/views/pages`, prefixed with `⚡`.
- Keep state server-side. Use Alpine for client-side polish, not for business logic.
- Reuse the existing Blade components before writing a new one.
- Use Flux components (`<flux:*>`) for UI, and named routes with `route()` for links.

**Accessibility and UI** — this app is used by grandparents on phones:

- Minimum 44px touch targets (`min-h-11`).
- Every interactive control needs an accessible name; live regions for anything that changes.
- Support both themes; do not hardcode a colour that only works in one.
- Test at 320px width.

**The non-negotiable one**

> Nothing may surface `guest_id`, a device token or its hash, or anything else linking a claim to
> a person — not on the public list, and not in the admin, which shows counts only.

There are tests asserting this in `WishlistPageTest` and `ManageItemsTest`. Keep them passing.

### Dependencies

Do not add, remove or upgrade a dependency as part of an unrelated PR. Propose it in an issue
first.

## Security

Please **do not open a public issue for a security vulnerability.** Report it privately through
[GitHub's security advisories](https://github.com/Dregozone/little-ones-wishlist/security/advisories/new)
and you will get a response as soon as possible.

Worth knowing about the threat model:

- The name gate is a **nuisance filter, not a security boundary.** It keeps out crawlers and
  passers-by. Anyone told a parent's first name can get in, and that is intended — the list is
  private, not secret. Do not put anything genuinely sensitive on it.
- Guest recovery links are bearer tokens. Anyone holding one can adopt that guest's claims, which
  is precisely what makes them work across devices. They are worth roughly what a claim on a baby
  wishlist is worth.
- Admin accounts are created only from the command line; there is no public registration route.

## License

Released under the [MIT License](LICENSE).
