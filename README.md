# Secret Santa

A Secret Santa web app for groups of friends: sign up, create or join a group through an invite link, let the server run the draw, and reveal who you got with a small roulette animation. Built to be opened from WhatsApp on a phone, in Brazilian Portuguese, English or Spanish.

> Portfolio project. Code, commits and docs are in English; the UI ships in three languages.

<!-- Screenshots: replace the placeholders below with real captures (e.g. docs/screenshots/*.png). -->

| Groups                  | Group page                           | Reveal                         |
| ----------------------- | ------------------------------------ | ------------------------------ |
| _screenshot: dashboard_ | _screenshot: group page (organizer)_ | _screenshot: reveal on mobile_ |

## Features

- **Sign in your way**: email + password, **Google**, or a **one-time code sent by email** (no password needed). Password reset included.
- **Groups**: name, description, gift-exchange date and an optional spending limit ("up to R$ 50,00") in the currency of your choice. The creator is the organizer.
- **Invite link**: unguessable token, shareable on WhatsApp, can be regenerated. Opening it never joins anyone; joining is an explicit button.
- **Exclusions**: pairs who must not draw each other (couples), always in both directions.
- **Special rules per person**: e.g. _"Ana prefers chocolate instead of a gift"_. Whoever draws Ana sees the rule, and Ana sees it on her own reveal. Nobody else does.
- **Draw**: organizer-only, at least 3 people, runs once on the server inside a transaction, prefers a single gift chain, respects every exclusion and explains when the constraints make a draw impossible. It can be reset (with confirmation), which unlocks the group.
- **Reveal**: a roulette spins through the names and stops on your match, which always comes from the server. Respects `prefers-reduced-motion`.
- **Secrecy**: each person can only ever see their own match, the organizer included.
- **i18n**: pt_BR (default), en, es, including validation messages, emails and flash messages. Dates and money are formatted for the active language.

## Tech stack

| Layer        | Choice                                                                                                                                                              |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Backend      | Laravel 13, PHP 8.4, Fortify, Socialite                                                                                                                             |
| Frontend     | Inertia 3 + React 19 + TypeScript (strict), Tailwind CSS 4, shadcn/ui — from the official [Laravel React starter kit](https://github.com/laravel/react-starter-kit) |
| Routes in TS | Laravel Wayfinder (typed route helpers)                                                                                                                             |
| Database     | PostgreSQL 18 (Docker locally, [Neon](https://neon.tech) in production)                                                                                             |
| Quality      | Pest, Larastan (level max), Pint, Oxlint + Oxfmt (via Vite+), `tsc --noEmit`                                                                                        |
| Runtime      | FrankenPHP in a multi-stage Docker image, deployed on [Render](https://render.com)                                                                                  |
| CI           | GitHub Actions with a Postgres service                                                                                                                              |

## Architecture and key decisions

### Why the draw runs on the server

The draw is the only moment the full set of matches exists, so it never happens in the browser. `POST /groups/{group}/draw` runs [`RunDraw`](app/Actions/Draw/RunDraw.php), which:

1. locks the group row (`SELECT … FOR UPDATE`), so a second click, a late join or a removal cannot interleave;
2. calls [`DrawSolver`](app/Draw/DrawSolver.php), a framework-free class that takes plain ids and returns `giver => receiver`;
3. inserts every assignment and sets `drawn_at` in the same transaction, so either everything is saved or nothing is.

**The algorithm.** The solver first runs a randomized backtracking search for a _single cycle_ (A → B → C → … → A), which avoids closed pairs of two. Finding such a cycle is NP-hard in general, so the search has a step budget. If it finds nothing, the solver falls back to a randomized bipartite matching (Kuhn's augmenting paths). That search is exact, so when it fails the draw is provably impossible, and the organizer gets a translated explanation. Every result is checked again (a permutation, no self-draws, no exclusion broken) before it is returned. All randomness comes from an injected `Random\Randomizer`: production uses the default CSPRNG-backed engine, and tests use a seeded `Mt19937` to get reproducible draws.

### How secrecy is enforced

Defense in depth, from the database up:

- **Data model**: `assignments` has unique `(group_id, giver_id)` and `(group_id, receiver_id)` plus `CHECK (giver_id <> receiver_id)`. Composite foreign keys point to the membership table, so a person who is part of a draw cannot be removed (the account deletion is blocked too).
- **No model is ever serialized into a page.** Props are built field by field in [`GroupPage`](app/Http/Resources/GroupPage.php), which also decides who sees gift rules, the invite link and exclusions. `Assignment` hides `giver_id`/`receiver_id`; `Group` hides its id and invite token.
- **One door to a match**: the group page declares `my_match` as an Inertia _optional_ prop. It is never part of the page; it is only resolved when the viewer clicks **Reveal** (a partial reload asking for it). It is authorized by `GroupPolicy::reveal` and looked up from the authenticated user ([`FindMyMatch`](app/Actions/Draw/FindMyMatch.php)): no id from the request is involved, so there is nothing to tamper with.
- **Policies** ([`GroupPolicy`](app/Policies/GroupPolicy.php)) decide who may do what. Actions re-check state rules ("not drawn yet") under the row lock.
- **Tests** ([`SecrecyTest`](tests/Feature/Draw/SecrecyTest.php)) assert that every member, the organizer included, gets exactly their own match. They sweep every `GET` route that takes a group, try tampered query strings and partial-reload headers, and cover outsiders, guests and gift-rule visibility.

Groups are addressed by a ULID in URLs, so ids cannot be enumerated.

### How i18n works

- `lang/` is the single source of truth. Framework strings (validation, auth, passwords) come from [laravel-lang](https://laravel-lang.com); app strings live in `lang/{locale}/*.php`.
- Only `lang/{locale}/ui/*.php` is shared with React, flattened to dot keys, as an Inertia **once-prop** keyed by locale and a file fingerprint. It is sent on the first visit and after a language switch, not on every navigation.
- `php artisan i18n:types` generates a `TranslationKey` union ([resources/js/types/translation-keys.ts](resources/js/types/translation-keys.ts)), so `t('groups.draw.run')` is type-checked and typos fail `tsc`.
- Locale resolution ([`SetLocale`](app/Http/Middleware/SetLocale.php)): the user's saved preference, then the session, then `Accept-Language` (exact match, then same language, e.g. `es-MX` → `es`), then `APP_LOCALE`. Notifications use the recipient's language (`HasLocalePreference`).
- Tests fail if a key or a `:placeholder` exists in one locale but not in another, or if the generated TypeScript keys are stale.

### Other decisions

- **Starter kit visuals, on purpose**: screens are composed from the kit's layouts and shadcn/ui components rather than a custom design.
- **No 2FA, passkeys or mandatory email verification** (all present in the kit): the app must work without a mail server, and passwordless users could never pass a "confirm your password" step. Google and email codes already prove email ownership.
- **Email codes** are 6 digits, expire in 10 minutes, are single-use, allow 5 attempts, are stored as an HMAC bound to the address, and are rate limited per address and per IP. The endpoint answers the same way whether or not an account exists.
- **Invite links are joined with a POST**: chat apps fetch links to build previews, so a GET must never change state.
- **Eloquent strict mode** outside production (lazy loading, unknown attributes), plus a test that the dashboard's query count does not grow with the number of groups.
- **Rate limits** on login, email codes, Google callback, joining and the draw.

## Local setup

Requirements: PHP 8.4 (e.g. [Laravel Herd](https://herd.laravel.com)), Composer, Node 22, Docker.

```bash
git clone <your-fork-url> secret-santa && cd secret-santa
cp .env.example .env
composer install
npm install
php artisan key:generate

# PostgreSQL (port 5442) and Mailpit (UI on http://localhost:8025)
docker compose up -d pgsql mailpit

php artisan migrate --seed   # demo data, see below
composer run dev             # Laravel on :8000 + Vite
```

Open http://localhost:8000. The seeder creates a group named _Natal da família_ with six people. Every demo account uses the password `password`: log in as `organizer@example.com` to run the draw, then as `ana@example.com`, `bruno@example.com`, … to reveal.

To try **email codes** locally, set `MAIL_MAILER=smtp` (Mailpit on `127.0.0.1:1025`) and read the code at http://localhost:8025.

To try **Google**, create OAuth credentials (see the deploy section) with `http://localhost:8000/auth/google/callback` as an authorized redirect URI and set `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET`.

> Prefer everything in containers? `compose.yaml` is a regular Laravel Sail file: `./vendor/bin/sail up -d` (macOS, Linux or WSL2), with `DB_HOST=pgsql` and `DB_PORT=5432`.

## Tests and linters

Tests run against PostgreSQL (the schema uses partial indexes, composite foreign keys and CHECK constraints), using the `testing` database that the Postgres container creates.

```bash
php artisan test              # Pest
vendor/bin/pint --test        # code style
vendor/bin/phpstan analyse    # Larastan, level max
npm run check                 # Oxlint + Oxfmt
npm run types:check           # tsc --noEmit
npm run build                 # production assets
php artisan i18n:types        # regenerate translation key types after editing lang/*/ui
```

CI ([.github/workflows/ci.yml](.github/workflows/ci.yml)) runs all of the above on every push and pull request.

## Deploying for free: Render + Neon

The app is designed for free tiers: no local file uploads, no SQLite, no queue workers or cron, logs to `stderr`, sessions and cache in the database.

1. **Database (Neon)**: create a project at [neon.tech](https://neon.tech) and copy the **direct** (non-pooled) connection string. It looks like `postgresql://user:pass@ep-xxx.region.aws.neon.tech/neondb?sslmode=require`.
2. **App key**: run `php artisan key:generate --show` locally and keep the value.
3. **Render**: push the repository to GitHub, then in Render choose **New → Blueprint** and select the repo. [`render.yaml`](render.yaml) creates a free Docker web service with `/up` as its health check. When asked, fill in:
    - `APP_KEY`: the value from step 2
    - `APP_URL`: `https://<service-name>.onrender.com`
    - `DB_URL`: the Neon connection string
    - `MAIL_FROM_ADDRESS`, `RESEND_API_KEY`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`: optional, leave empty for now if you like.
4. **Deploy**: the image builds the assets, and on every boot [`docker/start.sh`](docker/start.sh) caches config, routes, views and events, runs `php artisan migrate --force`, and starts FrankenPHP on `$PORT`. Render terminates HTTPS and the app trusts its proxy headers, so URLs and cookies are `https`.
5. **Email (optional)**: Render's free web services [block outbound SMTP](https://render.com/changelog/free-web-services-will-no-longer-allow-outbound-traffic-to-smtp-ports) (ports 25/465/587), so use an HTTP API. With [Resend](https://resend.com): verify a domain, then set `MAIL_MAILER=resend`, `RESEND_API_KEY` and `MAIL_FROM_ADDRESS`. Sign-in codes appear on the login page automatically. Without email, everything else still works.
6. **Google (optional)**: in [Google Cloud Console](https://console.cloud.google.com/apis/credentials), create an _OAuth client ID_ (Web application) with `https://<service-name>.onrender.com/auth/google/callback` as an authorized redirect URI. Then set the two variables and redeploy.

**Cold starts**: a free Render service sleeps after about 15 minutes without traffic, and the next request can take up to a minute while it wakes up (Neon's free compute also suspends when idle). Warn your friends, or open the link yourself before sharing it.

### Running the image locally

```bash
docker build -t secret-santa .
docker run --rm -p 8080:8080 \
  -e APP_KEY="$(php artisan key:generate --show)" -e APP_URL=http://localhost:8080 \
  -e DB_HOST=host.docker.internal -e DB_PORT=5442 -e DB_DATABASE=santa_secret \
  -e DB_USERNAME=sail -e DB_PASSWORD=password secret-santa
```

## Adding a new language

1. Add a case to [`App\Enums\Locale`](app/Enums/Locale.php) (e.g. `case French = 'fr';`) with its native name and, in [`Currency::defaultFor()`](app/Enums/Currency.php), a default currency.
2. Pull framework strings: `php artisan lang:add fr` (laravel-lang, dev dependency).
3. Copy `lang/en/messages.php`, `lang/en/groups.php` and `lang/en/ui/` to `lang/fr/` and translate them.
4. Run `php artisan test --filter=TranslationParity`: it lists every missing key and broken placeholder.

The language switcher, `Accept-Language` matching and the frontend pick the new locale up automatically.

## Project layout

```
app/
  Actions/        Auth (codes, Google), Groups (create, join, remove, exclusions), Draw (run, reset, find my match)
  Draw/           DrawSolver: framework-free draw algorithm
  Enums/          Locale, Currency, ParticipantRole
  Http/           thin controllers, Form Requests, page-prop builders (Resources), SetLocale middleware
  Policies/       GroupPolicy
  Support/        i18n translations loader, sign-in options
lang/{locale}/    framework + app strings; ui/ is what React receives
resources/js/     Inertia pages, components (kit + groups/), typed i18n helpers
tests/            Pest: Unit (solver) and Feature (auth, groups, draw, secrecy, i18n, production setup)
docker/           runtime php.ini and start script
```

## Roadmap

Not in the MVP, on purpose:

- Anonymous chat between giver and receiver
- Push notifications (and reminders before the exchange date)
- Payments or gift purchasing
- More social sign-ins (Apple, Facebook)
- Native mobile apps
- Wishlists per participant

## License

[MIT](LICENSE)
