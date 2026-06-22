# Igniter CMS

![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)

Igniter CMS is a light but powerful open source Content Management System built on the robust [CodeIgniter 4](http://codeigniter.com/) framework. It offers a comprehensive solution for website management, content creation, and digital presence optimization.

![Screen](https://assets.aktools.net/image-stocks/igniter-cms/dashboard.png "Dashboard")

## Documentation

Visit [Documentation](https://docs.ignitercms.com/) section in the website

## System Requirements

- PHP 8.2+ with the `zip`, `intl`, and `gd` extensions enabled
- Composer
- MySQL (or other supported database)
- A web server (Apache, Nginx, etc., or PHP's built-in server)

## Installation

The steps below describe a local setup. They use [Laragon](https://laragon.org/) on Windows as the reference environment, but the commands work on any platform where `php`, `composer`, and `mysql` are on your PATH.

> **Tip (Laragon/Windows):** Run all commands in **Laragon's Terminal** (open Laragon → **Terminal**) so `php`, `composer`, and `mysql` are available. To add PHP, right-click the Laragon tray icon → **Tools → Quick Add → PHP**. To enable extensions, right-click → **PHP → Extensions**.

### 1. Clone the repository

```bash
git clone https://github.com/akassama/igniter-cms.git
cd igniter-cms
```

### 2. Install PHP dependencies

```bash
composer install
```

This creates the `vendor/` folder. The final **"Generating optimized autoload files"** step scans `app/Models` and `app/Controllers` to build a class map — it can take a few seconds to ~30s (longer if antivirus scans `vendor/`). This is normal; let it finish.

Make sure `zip`, `intl`, and `gd` are enabled in your `php.ini` (Laragon → Menu → PHP → Extensions).

### 3. Create your `.env` file

Copy the example `env` file to `.env`:

```bash
copy env .env   # Windows
# cp env .env   # macOS / Linux
```

Then edit `.env` and set these values (Laragon's default MySQL password is usually empty):

```ini
CI_ENVIRONMENT = development

# Use whichever base URL matches your setup:
app.baseURL = 'http://localhost:8080/'
#app.baseURL = 'http://igniter-cms.test/'
#app.baseURL = 'http://localhost:8080/igniter-cms/'

database.default.hostname = localhost
database.default.database = igniter_cms_db
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

**Base URL notes:**

- With Laragon auto-vhosts, the site is `http://igniter-cms.test`.
- Without vhosts, use `app.baseURL = 'http://localhost/igniter-cms/public/'`.

### 4. Create the database

Create an empty database named `igniter_cms_db` (via Laragon → Database, HeidiSQL, or phpMyAdmin). Or via CLI:

```bash
mysql -u root -e "CREATE DATABASE igniter_cms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

### 5. Generate the app key

```bash
php spark generate:key
```

This sets/updates `APP_KEY` in your `.env`.

### 6. Create the tables

Run the migrations (recommended):

```bash
php spark recreate:tables
```

Type `yes` when prompted. This executes all available migrations and creates the necessary tables.

> **Alternative:** this repo ships a SQL dump at `install/database.sql` you can import instead — but migrations are the documented path.

### 7. Start the app

With your web server and MySQL running, open the base URL you configured, e.g. `http://igniter-cms.test`.

Or use CodeIgniter's built-in server for quick testing:

```bash
php spark serve
```

→ `http://localhost:8080`

### 8. Log in

Default admin credentials:

- **Email:** `admin@example.com`
- **Password:** `Admin@1`

To change the defaults, edit the migration at `app/Database/Migrations/2024-08-27-210112_Users.php` and update the `$data[]` array.

## Notes

- **Permissions:** ensure `writable/` and `public/uploads/` are writable by the web server.
- **Email:** configure SMTP in `.env` (the `email.*` settings) to enable mail.
- **Captcha / OAuth / AI:** optional integrations are configured in `.env` (disabled by default).

## Demo

Content Management System [https://demo.ignitercms.com](https://demo.ignitercms.com)

Themes [https://themes.ignitercms.com](https://themes.ignitercms.com)

## Support

If you find this project helpful, consider buying me a coffee:

<a href="https://www.buymeacoffee.com/akassama">
  <img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" width="160">
</a>

## License

The Lavalite CMS is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
