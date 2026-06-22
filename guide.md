# Igniter CMS — Local Setup Guide (Laragon / Windows)

Step-by-step instructions to run this project locally.

## Requirements

- PHP 8.1+ (this machine has 8.3) with `zip`, `intl`, and `gd` extensions enabled
- Composer
- MySQL
- A web server (Laragon's Apache/Nginx, or PHP's built-in server)

> Tip: Run all commands below in **Laragon's Terminal** (open Laragon → **Terminal**). It has `php`, `composer`, and `mysql` on the PATH.

Project location: `C:\laragon\www\igniter-cms`

---

## Step 1 — Install PHP dependencies

From the project root:

```bash
composer install
```

This creates the `vendor/` folder. The final step, **"Generating optimized autoload files,"** scans `app/Models` and `app/Controllers` to build a class map — it can take a few seconds to ~30s (longer if antivirus scans `vendor/`). This is normal; just let it finish.

Make sure `zip`, `intl`, and `gd` are enabled (Laragon → Menu → PHP → Extensions).

---

## Step 2 — Create your `.env` file

Copy the example `env` file to `.env`:

```bash
copy env .env
```

Then edit `.env` and set these values (Laragon's default MySQL password is usually empty):

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://igniter-cms.test/'

database.default.hostname = localhost
database.default.database = igniter_cms_db
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

URL notes:
- With Laragon auto-vhosts, the site is `http://igniter-cms.test`.
- Without vhosts, use `app.baseURL = 'http://localhost/igniter-cms/public/'`.

---

## Step 3 — Create the database

Create an empty database named `igniter_cms_db` (via Laragon → Database, HeidiSQL, or phpMyAdmin). Or via CLI:

```bash
mysql -u root -e "CREATE DATABASE igniter_cms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

---

## Step 4 — Generate the app key

```bash
php spark generate:key
```

This sets `APP_KEY` in your `.env`.

---

## Step 5 — Create the tables

Run the migrations (recommended):

```bash
php spark recreate:tables
```

Type `yes` when prompted.

> Alternative: this repo ships a SQL dump at `install/database.sql` you can import instead — but migrations are the documented path.

---

## Step 6 — Start the app

With Laragon running (Apache/Nginx + MySQL started), open:

```
http://igniter-cms.test
```

Or use CodeIgniter's built-in server for quick testing:

```bash
php spark serve
```

→ `http://localhost:8080`

---

## Step 7 — Log in

Default admin credentials:

- **Email:** `admin@example.com`
- **Password:** `Admin@1`

To change the defaults, edit the migration at
`app/Database/Migrations/2024-08-27-210112_Users.php` and update the `$data[]` array.

---

## Notes

- **Permissions:** ensure `writable/` and `public/uploads/` are writable.
- **Email:** configure SMTP in `.env` (the `email.*` settings) to enable mail.
- **Captcha / OAuth / AI:** optional integrations are configured in `.env` (disabled by default).
