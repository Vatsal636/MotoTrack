# MotoTrack

MotoTrack is a PHP-based motorcycle tracking application. It records fuel logs, service history, reminders, and trip data, and generates simple reports for multiple bikes per user.

## Features
- Multi-bike support with user isolation
- Fuel logs with auto mileage calculation
- Service records and reminders (service/insurance/pollution)
- Trip tracking and distance computation
- Dashboard charts (Chart.js) and summaries
- Activity logging and secure session handling

## Tech Stack
- PHP (vanilla, server-rendered), PDO for MySQL
- MySQL 5.7+/8.0
- HTML/CSS + vanilla JavaScript (no framework)
- Chart.js (CDN) for charts

## Project Structure
- [index.php](index.php): Landing/Login redirect
- Pages: [dashboard.php](dashboard.php), [bikes.php](bikes.php), [fuel.php](fuel.php), [service.php](service.php), [reminders.php](reminders.php), [reports.php](reports.php), [trips.php](trips.php), [profile.php](profile.php), [settings.php](settings.php), [login.php](login.php), [register.php](register.php), [logout.php](logout.php)
- Includes: [includes/header.php](includes/header.php), [includes/footer.php](includes/footer.php)
- Config: [config/config.php](config/config.php), [config/database.php](config/database.php), [config/env.php](config/env.php)
- Assets: [assets/css/style.css](assets/css/style.css), [assets/js/script.js](assets/js/script.js)
- Database schema: [database/schema.sql](database/schema.sql)
- Logs: [logs/](logs/)

## Environment Variables (.env)
Create a `.env` in the project root (same folder as `index.php`). Required keys:
```
APP_NAME=MotoTrack
APP_ENV=production|development
APP_DEBUG=true|false
APP_URL=http://your-domain-or-local-base/

DB_HOST=your_db_host
DB_USER=your_db_user
DB_PASS=your_db_password
DB_NAME=mototrack
DB_CHARSET=utf8mb4

SESSION_TIMEOUT=3600
PASSWORD_MIN_LENGTH=8
SESSION_NAME=MOTOTRACK_SESSION
TIMEZONE=Asia/Kolkata
```
Notes:
- Include protocol and trailing slash in `APP_URL`.
- Production should set `APP_ENV=production` and `APP_DEBUG=false`.

## Local Development (XAMPP)
1. Start Apache and MySQL.
2. Create database `mototrack` (lowercase, exact name).
3. Import [database/schema.sql](database/schema.sql).
4. Copy [.env.production.example](.env.production.example) to `.env` and edit for local:
```
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/mototrack/
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=mototrack
```
5. Visit `http://localhost/mototrack/`.

## Deployment (InfinityFree)
See detailed steps in [DEPLOYMENT.md](DEPLOYMENT.md). Summary:
- Upload the project into your domain’s `htdocs` (e.g., `/mototrack.free.nf/htdocs/`).
- Place `.env` in the same folder as `index.php`.
- Set `APP_URL` to your public base, e.g., `http://mototrack.free.nf/`.
- Create DB in InfinityFree, import [database/schema.sql](database/schema.sql), and set `DB_*` values.
- Optional SSL: enable, then switch `APP_URL` to `https://...`.

## Security
- `.env`, `config/`, `database/`, and `logs/` are protected via [.htaccess](.htaccess).
- CSRF protection: `csrfField()` in forms and `validateCSRFToken()` on POST.
- Always call `requireLogin()` at the top of authenticated pages.
- Use prepared statements and filter by `getUserId()` on all queries.

## Troubleshooting
- CSS/JS not loading: verify `APP_URL` has protocol + trailing slash; open `/assets/css/style.css` directly to test.
- DB connection errors: check host (`sqlXXX.epizy.com` for InfinityFree), name, user, and case sensitivity.
- Sessions failing: clear browser cookies; ensure `SESSION_NAME` is unique; in production, `session.cookie_secure=1` requires HTTPS.
- Errors in production: with `APP_DEBUG=false`, check [logs/php_errors.log](logs/php_errors.log).

## Scripts / Utilities
- Environment test: [test-env.php](test-env.php)
- Activity logs: [logs/activity_YYYY-MM-DD.log](logs/)

## Contributing
- Keep changes minimal and consistent with existing vanilla PHP style.
- Use prepared statements (PDO) and escape outputs with `e()`.
- Add CSRF field to all forms and follow POST/Redirect/GET.

## License
Proprietary/internal use. Do not redistribute without permission.
