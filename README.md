# BookTrips.lk — README

Sri Lanka's trip marketplace. Travellers discover and book local experiences and pay **at the
destination**; partners publish packages and pay BookTrips a **10% commission monthly**; super
admins approve partners, receipts and keep an eye on everything.

- **Stack:** Laravel 13 · PHP 8.3 · MySQL · Inertia v3 · React 19 · TypeScript · Tailwind v4 · Pest
- **Architecture / migration notes:** see [`MIGRATION.md`](MIGRATION.md)

---

## 1. Run it locally

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed     # seeds the catalogue (8 partners, 20 packages)
npm install
composer run dev               # server + Vite + queue + log viewer
```

Open the URL from `APP_URL` in `.env` (default <http://localhost:8000>).

Minimum `.env` values that matter:

```dotenv
APP_URL=http://localhost:8000
DB_CONNECTION=mysql / DB_DATABASE=... / DB_USERNAME=... / DB_PASSWORD=...
QUEUE_CONNECTION=database      # booking mails, notifications and photo watermarking are queued
MAIL_MAILER=smtp               # or "log" locally to skip a real mailbox
MAIL_HOST=... MAIL_PORT=... MAIL_USERNAME=... MAIL_PASSWORD=... MAIL_FROM_ADDRESS=...

TEXTLK_API_KEY=                # leave empty to log OTP codes instead of sending SMS
TEXTLK_SENDER_ID=BookTrips     # alphanumeric sender ids are limited to 11 characters
# BOOKTRIPS_CA_BUNDLE=C:/php/cacert.pem   # only needed when PHP has no curl.cainfo
```

> **Windows hosts:** if SMS fails with a cURL/SSL error, PHP has no root certificates. Either set
> `curl.cainfo` in `php.ini`, or download <https://curl.se/ca/cacert.pem> and point
> `BOOKTRIPS_CA_BUNDLE` at it. BookTrips keeps TLS verification on either way.

> If mail is not configured, set `MAIL_MAILER=log`. Verification and reset links then appear in
> `storage/logs/laravel.log` instead of an inbox. Keep `.env` out of version control — it holds
> mail and database credentials.

---

## 2. Accounts and roles

| Role | Created by | Lands on |
| --- | --- | --- |
| **Traveller** (`user`) | `/register` | `/account/bookings` |
| **Partner** (`business`) | `/partners/apply` — as a guest, or while signed in as a traveller (your account is upgraded) | `/partners/pending` until a super admin approves, then `/partners/dashboard` |
| **Super admin** (`admin`) | Promoted manually (see below) | `/admin` |

There is **no seeded admin and no demo login**. The seeded catalogue belongs to inactive
placeholder owners (`…@seed.booktrips.invalid`) that cannot log in — they only exist to own the
listings. To exercise the partner panel, apply as a new partner and approve it from the admin
console.

### How to access the admin console

1. Register an account normally at `/register` (any email you can verify, or verify it from the
   admin console itself later).
2. Promote that account:

   ```bash
   php artisan tinker --execute '$u = App\Models\User::where("email", "you@example.com")->firstOrFail(); $u->forceFill(["role" => "admin"])->save();'
   ```

   `role` is deliberately **not** mass-assignable (so nobody can self-promote through a form),
   which is why the snippet uses `forceFill`. Raw SQL works too:
   `UPDATE users SET role = 'admin' WHERE email = 'you@example.com';`

3. Log in and open **`/admin`** (link also appears in the header menu once you are an admin).
4. To revoke: set `role` back to `user` with the same snippet. Built-in rails stop an admin from
   deactivating their own account or another admin.

---

## 3. Visitor walkthrough (no account needed)

| Page | Path | What you can do |
| --- | --- | --- |
| Home | `/` | Hero search (where / dates / guests), featured trips, categories, how-it-works, partner call-to-action |
| Explore | `/search` | Filter by category, location/district, price range, guests and dates; sort by featured, price or rating; paginated results |
| Map | `/map` | All located packages as pins, with filters and a side list — great for finding trips by region |
| Package page | `/packages/{slug or id}` | Photo gallery (click any photo, or swipe on a phone, for the full-screen viewer with arrows and thumbnails), highlights, what's included/excluded, day-by-day plan, meeting point, host details, reviews, map, a **Share** button and a booking widget with a live price quote |
| About | `/about` | Platform story and how the pay-at-destination model works |
| For partners | `/partners` | Commission explanation, benefits, and the "Apply" button |
| Search suggestions | `/geo/search` | Location autocomplete behind the search boxes (proxied and cached) |

Only **active** packages appear anywhere publicly.

---

## 4. Traveller journey

1. **Register** — `/register` (name, email, phone, password). A verification email is sent.
2. **Verify email** — `/verify-email` shows a notice with a resend button until you click the link.
   Booking is blocked until then.
3. **Verify your mobile** — on the booking form, press *Send code*, key in the SMS code and press
   *Verify*. Hosts need a reachable number, and verified numbers carry more weight in any dispute.
4. **Find a trip** — browse, filter or use the map, then open a package.
5. **Book it** — press **Book** on the package page (goes to `/book/{package}`):
   - check-in / check-out dates (validated against the host's running days and availability),
   - number of guests (validated against the package's min/max),
   - the guest name, phone and any notes for the host,
   - the total is calculated by the server; the price never comes from the browser.
   - Submitting creates a **requested** booking, emails you and notifies the host.
5. **Wait for the host** — the partner confirms or rejects. You get an email and an in-app
   notification either way. Contact details stay hidden from the host until they confirm.
6. **Trip day** — pay the host directly at the destination (no card is taken online).
7. **Review** — once the booking is **completed**, open it and leave one review
   (rating 1–5, optional title, comment up to 1200 characters). The package rating updates.

Your stuff, under the header avatar (or the "My trips" button):

| Item | Path | Notes |
| --- | --- | --- |
| My trips | `/account/bookings` | All bookings with status badges; open one for the full receipt-style detail |
| Booking detail | `/account/bookings/{id}` | Booking code, dates, guests, totals, discount applied, host info, review form |
| Cancel | button on the booking | Allowed while the booking is `requested` or `confirmed`; not after completion |
| Notifications | bell in the header | Booking decisions (confirmed, rejected, cancelled, finished) arrive here too — click one to jump to the booking |
| My account | `/account` | Update name/phone, change password, change email (confirmed through a signed link), see booking/review counts |
| Log out | menu | `/logout` |

---

## 5. Partner journey

### 5.1 Apply

1. Go to `/partners/apply` and submit the business details (owner name, email, phone, password,
   business name, type, city/district, description, address, website, socials, cover image).
2. **Verify your mobile number.** Press *Send code*, key in the 6 digits from the SMS and press
   *Verify*. The form will not submit until a number is verified — BookTrips calls partners on
   this number. Codes expire in 10 minutes, allow 5 wrong tries and a new code can be requested
   after a 60-second cooldown (counted down on the button).
3. **Add at least one of** website, Instagram, Facebook, TikTok or WhatsApp — applications without
   a public profile are rejected automatically.
4. **Already signed in as a traveller?** The same page detects that and asks only for the business
   details — your existing account is upgraded in place (same login, same booking history), so there
   is no second email or password.
5. Either way you land on `/partners/pending` — a waiting room that explains the review and nags
   you to verify your email if you have not yet. It flips to the dashboard the moment a super admin
   approves.
6. Every new application pushes an in-app notification to all active super admins (the bell links
Only approved partners open these pages: a traveller is redirected to the application form, and a
super admin is sent back to `/admin`.

| Page | Path | What it does |
| --- | --- | --- |
| Dashboard | `/partners/dashboard` | Counters (listings, bookings, upcoming trips), recent reservations and quick actions |
| Packages | `/partners/packages/new`, `/partners/packages/{id}/edit` | Create and edit listings: title, category, description, highlight, location, district, running days (always / date range / weekdays), duration, price + price type, discounts (percentage or fixed, optional date window), guest limits, included/excluded lists, a **plan by day** editor, meeting point, cancellation policy, photos, and a *List this package in search* toggle |
| Map pin | inside the package form | Search an address or **click anywhere on the map** to drop the pin; the exact coordinates are shown and can be cleared. Packages without a pin are flagged because they never appear on the map |
| Photos | upload inside the package form (`/partners/images`) | Up to 8 images, **6 MB each**, JPG/PNG/WebP. Every photo is stamped with a translucent **Booktrips.lk** watermark by a queued job, so uploading stays instant |
| Share a package | **Share** button on the dashboard row, the edit page and the public listing | Copies the public link and offers WhatsApp / Facebook / the phone's native share sheet |
| Hide / re-list | **Hide** and **List again** buttons on the dashboard | Hiding deactivates the listing (`active = false`) and drops it from search; *List again* publishes it back. The edit form has the same switch |
| Reservations | `/partners/bookings` | List + day view, search by code/guest/package, filter by status or date, with counts of what needs an answer. You get a notification when a guest books **and** when a guest cancels |
| Reservation detail | `/partners/bookings/{id}` | Full trip details; guest phone/email are **redacted until you confirm** |
| Move a reservation | status buttons | `requested → confirmed \| rejected \| cancelled`, `confirmed → completed \| cancelled`. Completing a trip is what raises the commission |
| Finance | `/partners/payments` | Monthly commission invoices with status (`open` / `submitted` / `paid`), the BookTrips bank details, and the receipt upload form (JPG/PNG/PDF up to 8 MB + a note). Uploading marks the invoice as **submitted** and pings the admins; the review result comes back as a notification |
| Analytics | `/partners/analytics` | Business summary, monthly performance and per-package rows |
| Business profile | `/partners/profile` | Update public business details (name, type, description, address, phone, website, socials, cover image) |

### 5.3 Commission in one paragraph

Every booking you mark **completed** adds 10% of its total to that month's invoice (billed by the
trip's check-out month). If the month was already settled, the entry goes onto a separate
`-adj` adjustment invoice, so paid history is never rewritten. Pay by bank transfer using the
invoice number as the reference, upload the receipt, and a super admin marks it paid.

---

## 6. Super admin console (`/admin`)

> The console is deliberately invisible: anyone who is not a super admin gets a plain **404** from
> any `/admin` URL, so no one can tell an admin panel exists. Public pages say "our team" instead.

| Page | Path | What you can do |
| --- | --- | --- |
| Overview | `/admin` | Counters for users, partners (and pending applications), live listings, bookings by status, GMV, commission due, pending receipts and escalated requests |
| Users | `/admin/users` | Search/filter everyone; confirm email addresses; suspend or reactivate accounts (suspended users are logged out immediately and cannot log back in). You cannot change your own account or another admin |
| Partners | `/admin/partners` | **View** any application to read the full submission — description, address, contact phone, whether the mobile number was SMS-verified, every social profile, cover image, owner details and when it was sent — then approve or revoke. Approving emails the partner that they are in and can publish their first package |
| Listings | `/admin/listings` | Every package (including hidden ones); feature/unfeature for the home page and activate/deactivate to pull a listing from the catalogue |
| Bookings | `/admin/bookings` | Every booking across all partners; override status when a host is unresponsive. Marking one **completed** raises the correct commission automatically |
| Payments | `/admin/payments` | All commission invoices and uploaded receipts (you are notified when one arrives); **confirm** a receipt (invoice → `paid`) or **reject** it (invoice → back to `open` so the partner can upload again) |
| Reviews | `/admin/reviews` | Read-only view of all published reviews, newest first |
| Reports | `/admin/disputes` | Every traveller ↔ partner report with both accounts side by side; decide fault, penalty and (for partner faults) a billed amount once the response window closes |
| Support | `/admin/support` | The support inbox: reply to users, mark threads resolved, see who is waiting on BookTrips |

The **Overview** tab adds this-month numbers, six months of GMV, billed vs collected commission,
top partners and a "needs attention" list (reports ready for a verdict, unread support, receipts,
applications, escalations). Click a business or traveller anywhere to open their **history page**:
profile, strikes, bookings, invoices and receipts, reports and support threads.

Escalations: any booking still `requested` after 24 hours is flagged, and admins are notified by
the scheduled `booktrips:escalate-stale-bookings` job (see below).

---

## 7. Statuses you will see

**Booking:** `requested` → `confirmed` → `completed`, or `rejected` / `cancelled` along the way.
Only `requested` and `confirmed` hold capacity for the dates, and travellers may only cancel from
those two.

**Commission invoice:** `open` → `submitted` (receipt uploaded) → `paid`. A rejected receipt puts
it back to `open`.

**Receipt:** `pending` → `confirmed` / `rejected`.

---

## 7a. Reports & disputes (both sides protected)

Real life happens: guests do not turn up, hosts claim they were never paid. Every booking page has a
**Reports & disputes** panel where either side can raise the issue:

1. **The report** — a partner reports a no-show after the trip date, or a traveller reports
   "paid but denied" / "service not delivered". The reporter writes a headline and details.
2. **The response window** — the accused party is notified by email and in-app, and gets **48 hours**
   to tell their side. Nothing is decided before they answer, or before the window closes.
3. **The verdict** — a super admin reads both accounts in **Reports** in the console and rules:
   traveller at fault, partner at fault, or nobody at fault, with a penalty:
   - *warning* — noted on the account,
   - *strike* — recorded against the traveller or the business; **3 strikes suspends the account**,
   - *suspend* — immediate,
   - for partner faults an amount can be billed, which lands as a penalty line on their next
     commission invoice.
4. **Outcome** — both sides are notified in-app and by email; every report stays on the booking and on
   the traveller/business history pages.

---

## 7b. Help & support

**Help & support** in the header menu (or the footer) opens a support inbox: open a request with a
subject, category and message, then reply back and forth with the BookTrips team. Staff replies also
arrive by email, and unanswered threads show up in the admin console under **Support**.

---

## 8. Suggested local test drive

1. `php artisan migrate:fresh --seed` for a clean catalogue.
2. Register a traveller at `/register`, verify via the link in `storage/logs/laravel.log` (with
   `MAIL_MAILER=log`), then book a seeded package.
3. Apply as a partner at `/partners/apply`: press *Send code*, then read the 6-digit code from
   `storage/logs/laravel.log` (the SMS service falls back to the log when `TEXTLK_API_KEY` is
   empty), press *Verify* and submit.
4. Promote one account to admin (section 2) and open `/admin/partners` → **View** to read the whole
   application before approving it. The partner gets an approval email.
5. Sign in as the partner, publish a package with a map pin and a plan by day, then confirm the
   booking from Reservations, complete it and check `/partners/payments` for the generated
   commission invoice.
6. Upload a receipt as the partner, confirm it as the admin, and watch the invoice turn `paid`.
7. Drag a photo into the package form and confirm the Booktrips.lk watermark appears once the queued
   job runs (`php artisan queue:work`, or `composer run dev` which runs the worker for you).

---

## 9. Common commands

| Task | Command |
| --- | --- |
| Everything in dev (server + Vite + queue + logs) | `composer run dev` |
| Frontend production build | `npm run build` |
| Tests | `php artisan test --compact` or `vendor/bin/pest` |
| Formatting | `composer run lint` (Pint) |
| Static analysis | `composer run types:check` (PHPStan level 7) |
| TypeScript check | `npm run types:check` |
| Full gate (lint check + PHPStan + tests) | `composer test` |
| Queue worker | `php artisan queue:work` |
| Scheduler (local) | `php artisan schedule:work` |

Tests run on SQLite in-memory and need the `pdo_sqlite` and `gd` PHP extensions enabled.

---

## 10. Where things live

```
app/
  Console/Commands/     EscalateStaleBookings (scheduled every 5 min)
  Enums/                BookingStatus, InvoiceStatus, ReceiptStatus, UserRole, …
  Http/Controllers/     Home, Page, Package, Geo, Booking, Review, Account, Auth/*, Partner/*, Admin/*
  Http/Middleware/      EnsureAccountIsActive, EnsurePartnerIsApproved, EnsureUserIsAdmin, …
  Mail/                 verification, reset, booking requested, host notice, decision
  Models/               User, Business, Package, Booking, Review, Invoice, Receipt
  Presenters/           CatalogPresenter, BookingPresenter (per-audience shapes)
  Services/             BookingService, PricingService, CommissionService, ScheduleService,
                        AnalyticsService, NotificationService, MailService, GeoSearchService,
                        SmsService (Text.lk), PhoneVerificationService (partner OTP),
                        ImageWatermarker (GD)
  Jobs/                 WatermarkPackageImage (queued after each photo upload)
config/booktrips.php    commission rate, escalation window, capacity guard, bank details,
                        categories, destinations, upload limits
resources/js/pages/     every screen (public, auth, traveller, partner, admin)
resources/js/components/booktrips/   shared UI kit
routes/web.php          the whole route map
tests/Feature/          53 tests across auth, catalogue, booking, reviews, partner, admin, escalation
```

Commission rate, escalation window, capacity guard and the bank details shown to partners are all
in `config/booktrips.php` (or `BOOKTRIPS_*` env vars) — change them there, not in the UI.
