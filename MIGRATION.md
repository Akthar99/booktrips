# BookTrips — Prototype → Laravel Migration

This document records how the `booktrips.lk` prototype was migrated into this Laravel
application, what changed on the way to production, and how to run, seed, test and deploy it.

The prototype folder is left untouched and remains the reference specification for product
behaviour.

---

## 1. Stack

| Layer                    | Choice                                                                        |
| ------------------------ | ----------------------------------------------------------------------------- |
| Backend                  | Laravel 13 on PHP 8.3                                                         |
| Database                 | MySQL (production), SQLite in-memory for tests                                |
| Frontend                 | Inertia.js v3 + React 19 + TypeScript                                         |
| Styling                  | Tailwind CSS v4 (CSS-first `@theme` design tokens in `resources/css/app.css`) |
| Routing to backend       | Laravel Wayfinder (`@/actions/*`, `@/routes/*` typed helpers)                 |
| Mail                     | Laravel Mailables, queued                                                     |
| Queue / cache / sessions | database driver                                                               |
| Static analysis          | Larastan (PHPStan level 7)                                                    |
| Tests                    | Pest 4                                                                        |
| Formatting               | Laravel Pint                                                                  |

No JSON files, no client-side routing tables, no hand-rolled auth tokens: the catalogue,
the booking rules and every money calculation now live in the Laravel app.

---

## 2. What maps to what

| Prototype concept                       | Laravel implementation                                                                                                                             |
| --------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| JSON data files / collections           | MySQL migrations: `users`, `businesses`, `packages`, `bookings`, `reviews`, `invoices`, `receipts`, `notifications`                                |
| Express route handlers                  | Controllers: `Home`, `Page`, `Package`, `Geo`, `Booking`, `Review`, `Account`, `Notification`, `ReceiptDownload`, `Auth\*`, `Partner\*`, `Admin\*` |
| REST API + fetch calls                  | Inertia page visits and Wayfinder-generated form helpers (`router`, `useForm`)                                                                     |
| Public HTML/CSS/JS pages                | React pages under `resources/js/pages/*` (30 pages) with shared components in `resources/js/components/booktrips/*`                                |
| localStorage / token auth               | Laravel session auth + CSRF, email verification, password reset, email change confirmation                                                         |
| Role checks in route handlers           | `partner` / `admin` middleware + policies (`PackagePolicy`, `BookingPolicy`, `InvoicePolicy`, `ReviewPolicy`)                                      |
| Server-side totals copied from requests | `App\Services\BookingService` recalculates every amount; client values are ignored                                                                 |
| Stripe-less "pay at destination" flow   | Unchanged: the platform takes no money at booking time; partners invoice commissions monthly                                                       |
| Prototype CSS                           | Rebuilt as a Tailwind design system (not a stylesheet port)                                                                                        |

Supporting services:

- `PricingService` — percentage / fixed discounts, `per_person`, `per_package`, `per_night` multipliers, date/applicability rules.
- `BookingService` — booking creation inside a transaction, unique `BT-XXXXXX` codes, capacity guard, status transitions, cancellation, mails + notifications.
- `CommissionService` — 10% of each finished booking appended to the business's monthly invoice (`YYYY-MM`, or `YYYY-MM-adj` once that month is paid).
- `ScheduleService` — weekday and date-range running rules.
- `AnalyticsService` — partner dashboard/analytics payloads.
- `NotificationService`, `MailService` — in-app notifications (database channel) and queued mail.
- `GeoSearchService` — Nominatim proxy with caching and rate limiting.
- `CatalogPresenter`, `BookingPresenter` — one shape per audience: `forGuest`, `forPartner` (contact details redacted until the booking is confirmed), `forAdmin`.

---

## 3. Route map (named routes)

**Public** — `home`, `search`, `map`, `packages.show`, `packages.quote`, `about`, `partners`, `geo.search`

**Guest auth** — `register`, `login`, `password.request`, `password.email`, `password.reset`, `password.store`, `partner.apply`, `partner.register`, `partner.pending`

`partner.apply` / `partner.register` are also reachable while signed in so a traveller can upgrade
their own account into a pending partner (one account, no duplicate email).

**Traveller** — `account.index`, `account.profile`, `account.email`, `account.password`, `email.change.confirm`, `verification.notice`, `verification.verify`, `verification.send`, `notifications.read`, `notifications.read-all`, `receipts.download`, `bookings.index`, `bookings.show`, `bookings.create`, `bookings.store`, `bookings.cancel`, `reviews.store`

**Partner** (`/partners/*`, `partner` middleware) — `partner.dashboard`, `partner.profile.update`, `partner.packages.create|store|edit|update|destroy`, `partner.images.store`, `partner.bookings.index|show`, `partner.bookings.status`, `partner.payments.index`, `partner.payments.receipts.store`, `partner.analytics.index`

**Admin** (`/admin/*`, `admin` middleware) — `admin.overview`, `admin.users`, `admin.users.update`, `admin.partners`, `admin.partners.approve`, `admin.listings`, `admin.packages.update`, `admin.bookings`, `admin.bookings.status`, `admin.payments`, `admin.receipts.update`, `admin.reviews`

---

## 4. Domain rules (single source of truth)

| Rule              | Where                                                          | Value                                                                                                                                                                    |
| ----------------- | -------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Commission        | `config/booktrips.php` → `commission_rate`                     | 10% of the finished booking total                                                                                                                                        |
| Invoice period    | `CommissionService`                                            | `YYYY-MM` from the booking's check-out month; late entries on a paid month go to `YYYY-MM-adj`                                                                           |
| Escalation window | `config/booktrips.php` → `escalation_hours`                    | 24 h; unanswered `requested` bookings are escalated to admins                                                                                                            |
| Capacity guard    | `BookingService::assertCapacity` + `Booking::scopeOverlapping` | Sum of `guests` on overlapping date ranges may not exceed `packages.max_guests`; date bounds are inclusive, so day trips (`check_in == check_out`) are counted correctly |
| Booking statuses  | `App\Enums\BookingStatus`                                      | `requested → confirmed → completed`, plus `rejected` / `cancelled`; partners may only move along allowed transitions, admins may override                                |
| Invoice statuses  | `App\Enums\InvoiceStatus`                                      | `open → submitted → paid` (a rejected receipt returns the invoice to `open`)                                                                                             |
| Receipt statuses  | `App\Enums\ReceiptStatus`                                      | `pending → confirmed` / `rejected`                                                                                                                                       |
| Payment method    | `BookingService`                                               | `pay_at_destination` only — no online payment at booking time                                                                                                            |
| Uploads           | `config/booktrips.php` → `uploads`                             | max 8 package images (5 MB each), receipt 8 MB                                                                                                                           |

---

## 5. Deliberate security changes vs. the prototype

These are the differences that matter for production:

1. **Session auth, not tokens.** Login uses Laravel sessions with CSRF protection on every write; nothing sensitive lives in `localStorage`.
2. **Money is server-authoritative.** `BookingService` recomputes base total, discount and final total from the package record. Client-supplied totals are never read.
3. **Overbooking is impossible.** All booking writes run in a transaction and re-check capacity against overlapping confirmed/requested bookings.
4. **Guest contact details are redacted** in partner views (`BookingPresenter::forPartner`) until the partner confirms — partners cannot farm phone numbers from unconfirmed requests.
5. **Receipts are private.** Files are stored on the `local` disk (`storage/app/private/...`) and served only through the authorised `receipts.download` route; the prototype served them from a public folder.
6. **Mass-assignment is locked down** with `#[Fillable]` / `#[Hidden]` attributes; package slugs are generated server-side and cannot be injected.
7. **Partner applications are verified.** The mobile number must pass an SMS one-time code (Text.lk) before the form submits, at least one social/web profile is required, codes are hashed with a 10-minute life, five-attempt cap, per-phone cooldown and hourly cap, and the endpoints are rate limited per IP.
8. **Uploaded photos are branded.** A queued job stamps a translucent Booktrips.lk watermark on every package photo (GD), so a partner's images carry the marketplace brand wherever they are shared; uploads are capped at 6 MB per image.
9. **The admin console is unadvertised.** Non-admins receive a 404 from every `/admin` route, and public copy refers to "our team" rather than an admin panel.
10. **Rate limiting** on login, registration, password reset, email verification resend (with client-side countdowns), geo search, quote, upload and the OTP endpoints.
11. **Suspended accounts are ejected** mid-session by `EnsureAccountIsActive`; suspension also blocks login.
12. **Admin safety rails.** Admins cannot suspend or modify their own account or another admin, and cannot change partner-visible roles.
13. **Production guards**: `DB::prohibitDestructiveCommands` in production, `URL::forceScheme('https')` plus secure session cookies, security headers, and `Model::shouldBeStrict()` outside production (which is what surfaced the bugs fixed during testing).
14. **Disputes keep both sides honest.** A report always notifies the accused and gives them 48 hours to answer before a verdict is possible; verdicts are admin-only and turn into warnings, strikes (three suspend the account) or commission penalties. Nothing is charged automatically without review.

---

## 6. Running it locally

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # seeds the catalogue only (see §7)
npm install
composer run dev                # php artisan dev: server + vite + queue + logs
```

Useful single commands: `npm run build`, `composer lint` (Pint), `composer types:check` (PHPStan),
`npm run types:check` (TypeScript), `composer test` (lint check + static analysis + Pest).

### Environment

Set these in `.env` (production values in brackets):

```dotenv
APP_NAME=BookTrips
APP_ENV=production
APP_DEBUG=false
APP_URL=https://booktrips.lk

DB_CONNECTION=mysql
DB_HOST=... DB_PORT=3306 DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

QUEUE_CONNECTION=database        # REQUIRED - decision mails and notifications are queued
CACHE_STORE=database
FILESYSTEM_DISK=local            # receipts are private; package images use the "public" disk

MAIL_MAILER=smtp                 # or ses / postmark / resend
MAIL_HOST=... MAIL_PORT=587 MAIL_USERNAME=... MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=bookings@booktrips.lk
MAIL_FROM_NAME="${APP_NAME}"

# Text.lk SMS gateway (partner mobile verification). With no key the codes are
# written to the log instead, which is handy for local development.
TEXTLK_API_KEY=
TEXTLK_SENDER_ID=Booktrips.lk
TEXTLK_BASE_URL=https://app.text.lk/api/v3

# Package photos are watermarked with GD; needs the gd extension enabled.
BOOKTRIPS_WATERMARK=true
BOOKTRIPS_WATERMARK_TEXT="Booktrips.lk"
# BOOKTRIPS_WATERMARK_ALPHA=100
# BOOKTRIPS_WATERMARK_FONT=/path/to/bold.ttf

# Only needed on hosts without a system CA bundle (bare Windows PHP):
# BOOKTRIPS_CA_BUNDLE=C:/php/cacert.pem

BOOKTRIPS_BANK_NAME=...          # shown to partners on the payments page
BOOKTRIPS_BANK_ACCOUNT_NAME=...
BOOKTRIPS_BANK_ACCOUNT_NUMBER=...
BOOKTRIPS_BANK_BRANCH=...
BOOKTRIPS_BANK_SWIFT=...
```

If the app sits behind a proxy or load balancer, uncomment the `trustProxies()` example in
`bootstrap/app.php` and set `TRUSTED_PROXIES` so scheme and IP detection stay correct.

---

## 7. Catalogue seeding and handing it to real partners

`database/seeders/CatalogSeeder.php` seeds 8 business records and 20 Sri Lankan packages with
placeholder owners (`{key}@seed.booktrips.invalid`, random passwords, inactive, `approved = true`
so the listings render).

- `php artisan db:seed --force` is required in production — the seeder refuses to run there otherwise.
- The placeholder accounts cannot log in and are only there to own the catalogue rows.
- When the real partner signs up and is approved, move the listings by updating
  `packages.business_id` (and the matching `businesses` record) to the real business, then delete
  the placeholder rows. No listing data has to be re-entered.
- To start with an empty catalogue instead: `php artisan db:seed --class=DatabaseSeeder --force`
  without `CatalogSeeder`, or simply skip seeding.

---

## 8. Queue and scheduler

```bash
# worker (required in production; supervision by your host)
php artisan queue:work --tries=3 --max-time=3600

# local: schedule:work - production: one cron entry
php artisan schedule:work
* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled jobs: `booktrips:escalate-stale-bookings` every five minutes (`withoutOverlapping`),
which flags booking requests unanswered for 24 h, notifies admins and re-nudges the partner, and
`booktrips:sitemap:generate` nightly at 03:30 which rebuilds `public/sitemap.xml` from the active
catalogue.

The worker processes all queued mail — keep it running in production. Package photos are
watermarked inline during the upload (before they are stored, S3 included), so they never wait
on the worker.

---

## 9. Tests

`vendor/bin/pest` (or `php artisan test --compact`) runs 123 feature tests covering:

| File                                       | Covers                                                                                                                                                                                                                                                                     |
| ------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `tests/Feature/AuthTest.php`               | registration, verification mail, role mass-assignment block, weak password/duplicate email, login/logout, suspended login, signed verification, bad signature, password reset, email change                                                                                |
| `tests/Feature/PartnerApplicationTest.php` | guest applications (business + owner created, admins notified with a deep link), duplicate-email guidance, signed-in traveller upgrades on the same account, pending/approved/admin redirects, no second business per owner, unverified-phone and missing-social rejection |
| `tests/Feature/PhoneVerificationTest.php`  | number normalisation, Text.lk payload (bearer token, sender id, plain type), invalid numbers, per-phone cooldown, hourly cap, per-IP throttling, wrong-code limits, expiry, session proof                                                                                  |
| `tests/Feature/DisputeTest.php`            | no-show and payment reports, wrong-type/early/duplicate guards, stranger 403s, 48-hour response window, strikes, auto-suspension at three, partner penalties billed to their invoice, admin-only queue                                                                     |
| `tests/Feature/SupportTest.php`            | opening a thread (admins notified), back-and-forth handover, privacy between users, resolved-thread rules, booking ownership, admin inbox                                                                                                                                  |
| `tests/Feature/AdminInsightsTest.php`      | dashboard insights (finance, monthly, attention, top partners), finance summary, business and traveller history pages, access control                                                                                                                                      |
| `tests/Feature/PackageImageTest.php`       | watermark job dispatched per photo, 6 MB limit enforced, watermark changes the file, switch-off behaviour                                                                                                                                                                  |
| `tests/Feature/CatalogTest.php`            | home props, search filters, hidden packages, slug/id lookup, quote maths, guest/weekday validation, map pins, geo search                                                                                                                                                   |
| `tests/Feature/BookingFlowTest.php`        | verified-email requirement, server-side totals, capacity guard, partner confirm + contact redaction, commission/invoice creation, month adjustments, transition rules, cross-partner access, cancellation (partner notified), traveller decision notifications             |
| `tests/Feature/ReviewTest.php`             | one review per completed own booking, rating/comment validation, rating recomputation                                                                                                                                                                                      |
| `tests/Feature/PartnerPanelTest.php`       | pending-partner redirect, traveller/admin redirects away from partner areas, package CRUD + slug + discount guard, hide/re-list, cross-partner 403, image upload, private receipt upload/download + admin notification, analytics, dashboard counts                        |
| `tests/Feature/AdminPanelTest.php`         | console protection (404 to non-admins), application detail payload, partner approval + approval email, listing feature/unlist, suspension, admin self-protection, receipt confirm/reject (partner notified), admin-driven completion + commission                          |
| `tests/Feature/EscalationTest.php`         | stale escalation job, admins notified, answered bookings untouched                                                                                                                                                                                                         |
| `tests/Feature/PerformanceTest.php`        | query budgets per page (catalogue, admin overview, partner dashboard, admin users, traveller bookings), SQL-side catalogue filtering/price sort, pagination                                                                                                                |

The suite runs on SQLite in-memory (`phpunit.xml`); locally it needs the `pdo_sqlite` and `gd`
extensions enabled in `php.ini` (uploads use generated image fixtures).

---

## 9a. Performance

- **Pagination happens in SQL.** The catalogue used to load every active listing into PHP, filter it in memory and slice it for the page. It now filters, sorts and paginates in the database (`?page=`, 12 per page), so the work is constant no matter how many listings exist.
- **The discounted price has an SQL twin.** `PackageController::PRICE_SQL` mirrors `PricingService::pricingFor()` (percentage and fixed discounts, with the active-window date checks) so price filters and price sorting run in the database. If you change the pricing rules, change both — the four `?` placeholders take today's date.
- **One aggregate per table on the admin overview.** It previously fired ~50 count/sum queries (18 of them for the six-month chart); it is now ~12, and the chart is two grouped queries.
- **No per-row queries in lists.** `AdminUserController` uses `withCount('bookings')`; partner dashboard stats come from one `sum(case when ...)` aggregate; pin payloads for the maps only carry the fields the marker renders.
- **Indexes** — `database/migrations/*_add_query_performance_indexes.php` adds the ones the hot paths need (catalogue price, partner booking counts, receipt queue, invoice periods, dispute queue, review author, ticket ordering). The migration is idempotent (`Schema::hasIndex`), so it is safe to re-run.
- **Query budgets are tested.** `tests/Feature/PerformanceTest.php` pins the number of queries per page, so a regression (an N+1, a lost eager load, a stat returning to a per-card count) fails the suite instead of slowing the site down.
- **Local reminder:** development currently points at the remote MariaDB host, so every query costs a network round trip (~80 ms each). Prefer a local MySQL/SQLite database for day-to-day work, or a local `SESSION_DRIVER`/`CACHE_STORE` of `file`, before blaming the code for slow pages.

---

1. Set the production `.env` from §6 (never commit it; `APP_KEY` unique per environment).
2. `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force` (and `db:seed --force` only if you want the placeholder catalogue).
4. Enable the `gd` PHP extension — partner photo watermarks are drawn with GD (uploads still work without it, they are simply left unbranded and a warning is logged).
5. Run a queue worker and the scheduler (§8) — the worker also brands uploaded photos.
6. Set `TEXTLK_API_KEY` and `TEXTLK_SENDER_ID` so partner mobile verification actually sends SMS; without a key the codes only reach the log and no real application can be completed.
7. Serve over HTTPS only (the app forces the scheme and sets secure cookies).
8. `/up` is the health endpoint; point uptime monitoring at it.
9. Laravel Cloud is the recommended host — activate the `deploying-to-cloud` skill for the CLI
   workflow (app/environment/database creation, secrets, domains, workers, scheduled tasks).
   Note that Laravel Cloud local disks are ephemeral: move public package images to object storage
   (`FILESYSTEM_DISK` / the `public` disk) if the app runs on ephemeral compute; receipts stay on
   the private disk either way. (Remote disks have no local path, so the watermark job logs and
   skips itself when the public disk is not local.)

---

## 11. Known follow-ups

- Replace the sample bank details in `config/booktrips.php` (or set the `BOOKTRIPS_BANK_*` env vars) with the real company account before partners see the payments page.
- Confirm the placeholder catalogue should ship at all; if not, drop `CatalogSeeder` from `DatabaseSeeder` before the first production seed.
- Social logins, SMS notifications and online payment collection were intentionally **not** migrated — the prototype has none of them.
- `laravel:fonts` warns that the optional `fontaine` package is missing for optimised font fallbacks; install it or set `optimizedFallbacks: false` in `vite.config.ts`.
- Guest checkout does not exist: booking requires an account with a verified email (`auth` + `verified` middleware).
- The watermark text, opacity and font are configurable (`BOOKTRIPS_WATERMARK*`); a TrueType font gives the crispest result — otherwise the built-in bitmap font is scaled up.
- Partner phone verification is SMS-only. If you later add WhatsApp or email fallback, extend `PhoneVerificationService` — the codes, limits and session proof live there.
- The frontend formatter has never been run repo-wide: `npm run check` currently reports formatting drift in ~70 files (including untouched starter files). Run `npm run check:fix` once when you are happy to take that large diff.
- On hosts without a system CA bundle (bare Windows PHP), set `curl.cainfo` in `php.ini` or `BOOKTRIPS_CA_BUNDLE` — outbound HTTPS (SMS, address lookup) fails with cURL error 60 otherwise. TLS verification is never disabled by the app.
