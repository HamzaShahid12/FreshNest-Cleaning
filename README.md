# FreshNest Cleaning Services

Laravel 12 + Vue 3 (Composition API) + Inertia + Tailwind + MySQL. Public marketing site, booking wizard with server-side pricing, customer portal, employee portal, admin dashboard, role-based access, email notifications and Stripe-ready payments.

> **Honest status:** this source was written in a sandbox with no internet, no MySQL and no Composer/npm. It was **PHP-linted** and the pricing calculator was run standalone (10/10 checks). Migrations, the Laravel boot, the PHPUnit suite, the Vue compile and `npm run build` have **not been run**. Expect to fix a few small things on first run; `php artisan test` is the quickest way to find them.

## Requirements (Windows + Laragon)
- Laragon (Full) with **PHP 8.2+**, MySQL, Apache/Nginx
- Composer 2, Node.js 20+ and npm
- PHP extensions: mbstring, openssl, pdo_mysql, fileinfo, intl, curl, gd (all default in Laragon)

## Install
```bat
cd C:\laragon\www\freshnest-cleaning
copy .env.example .env
composer install
npm install
php artisan key:generate
```
Create an empty database named `freshnest` (collation utf8mb4_unicode_ci), e.g. in HeidiSQL or `mysql -uroot -e "CREATE DATABASE freshnest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"`. Laragon's default MySQL login is `root` with an empty password, which `.env.example` already uses.
```bat
php artisan migrate --seed
php artisan storage:link
npm run build
```
Or double-click `setup.bat`, which runs the same steps.

## Run
- Laragon auto virtual host: `http://freshnest-cleaning.test` (set `APP_URL` to match), **or**
- Dev servers in two terminals: `php artisan serve` and `npm run dev` → http://localhost:8000

## Development logins (local only)
`ChangeMe!Dev2026` is a **development-only** password and **must be changed at first sign-in** (forced).

| Role | Email | Portal |
|---|---|---|
| Super Admin | admin@freshnest.test | /admin |
| Demo admin / manager | administrator@ / manager@freshnest.test | /admin |
| Demo cleaners | sofia@, james@, aisha@freshnest.test | /staff (sign in at /staff/login) |
| Demo customer | customer@freshnest.test | /account |

Demo accounts and sample bookings are created only when `APP_ENV=local`. In any other environment the seeder creates just the Super Admin: set `ADMIN_SEED_PASSWORD` before seeding, otherwise a random password is generated and printed once.
Coupons seeded: `WELCOME10`, `SAVE20`.

## Modules
Dashboard, Bookings (list, detail, assign, reschedule, status, notes, payments, refunds, print), Calendar, Customers, Employees (hours, skills = availability), Services, Categories, Extras, Coupons, Service areas, Reviews, Blog, FAQs, Gallery, Enquiries, Newsletter, Users and roles, Reports (+CSV), Settings (business info, **pricing rates**, booking slot/notice rules, **email templates**), Audit log.
Mapping to the spec: *Availability* = employee working days/hours + slot settings; *Pricing management* = Settings + Services/Extras; *Notification management* = email templates in Settings.
Roles: super_admin, admin, manager (operations, no settings/audit), staff (own jobs), customer.

## Map, worldwide addresses, branding and profile photos
- **Map location picker (any country):** the booking wizard's Address step and *My account → Addresses* have a Leaflet + OpenStreetMap map. Search an address, tap the map, drag the pin or use "Use my location"; street, city, state/province, postal code, country and coordinates fill in automatically and stay editable. No API key is needed (search uses OpenStreetMap Nominatim, light personal/low-volume use; for heavy production traffic use your own tile/geocoding provider). State and postal code are optional and the US ZIP service-area list only applies to US addresses. Admins and cleaners get an "Open in Google Maps" link on each job.
- **Service areas without ZIP codes:** in *Admin → Service areas* each area is a city (state, country and postal codes are optional). If an area lists postal codes, a customer's postal code is matched; otherwise their **city name** must match the area name (so add e.g. "Lahore" with no codes). To accept bookings from anywhere, set *Settings → Booking rules → "Only accept bookings inside the listed Service areas"* to `0`. The public availability checker accepts a city or a postal code.
- **Logo and banners:** *Admin → System → Branding & banners* (Admin and Super Admin). Upload or reset the logo and the banner of Home, About, Services, Pricing, Gallery, Reviews, FAQ, Contact, Service areas, Blog and Privacy/Terms pages at any time. JPG/PNG/WebP only (SVG is blocked on purpose); logo up to 2 MB, banners up to 4 MB, 1600x600 px or wider recommended. Defaults ship in `public/images/banners`.
- **Profile photo:** every role can upload one (My account → Profile, or the avatar in the Staff/Admin header → profile). It shows in the site header and portals. JPG/PNG/WebP up to 2 MB.
- Uploads are stored in `storage/app/public` and served through the `public/storage` link, so `php artisan storage:link` must have been run.

## Updating an existing installation
If you already installed an earlier version, use the `freshnest-update-*.zip` patch (only changed files):
1. Back up your database (Laragon: HeidiSQL → Export) and your project folder.
2. Extract the patch **into** `C:\laragon\www\freshnest-cleaning` and choose "Replace the files". Your `.env`, `storage/` uploads and database are not in the patch, so they stay untouched.
3. In the project folder run:
```bat
npm install
composer dump-autoload
php artisan migrate
php artisan storage:link
php artisan optimize:clear
npm run build
```
`npm install` adds the `leaflet` map library; `php artisan migrate` adds the new columns (address country/coordinates, user avatar) and keeps all existing data. If `storage:link` says the link already exists that is fine.

## Payments
Pay-later and cash work out of the box. For card payments put your **test** keys in `.env` (`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`). Bookings are only marked paid by the verified webhook (`POST /stripe/webhook`; locally use `stripe listen --forward-to localhost:8000/stripe/webhook`). Nothing is ever marked paid without Stripe confirming. SMS/WhatsApp are not implemented.

## Email
`MAIL_MAILER=log` writes emails to `storage/logs/laravel.log`. For real delivery set SMTP values (Mailtrap, Gmail app password, etc.).

## Tests
`php artisan test` (SQLite in memory; no MySQL needed). Covers pricing, public pages, auth, access control, booking flow, booking management, payments and admin modules.

## Troubleshooting
- *Vite manifest not found*: run `npm run build` (or `npm run dev`).
- *Images not showing / uploads*: `php artisan storage:link`.
- *419 / session issues*: `php artisan optimize:clear`, make sure `APP_URL` matches the URL you browse.
- *Class not found after pulling changes*: `composer dump-autoload`.
- *Can't connect to MySQL*: start MySQL in Laragon and check `DB_*` in `.env`.

## Production notes
Set `APP_ENV=production`, `APP_DEBUG=false`, a real `APP_URL`, strong `ADMIN_SEED_PASSWORD`, live Stripe keys, SMTP, `QUEUE_CONNECTION=database` with a worker, then `composer install --no-dev -o`, `npm run build`, `php artisan migrate --force`, `php artisan config:cache route:cache view:cache`. Point the web root at `public/`.
