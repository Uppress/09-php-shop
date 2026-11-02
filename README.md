# SWD Shop

A PHP 8 and MariaDB shop for XAMPP, using PDO and the `swd_shop` database.

## Requirements

- XAMPP with PHP 8.1+ and MySQL/MariaDB
- PHP extensions `pdo_mysql` and `openssl`

## Setup

1. Copy the project to `C:\xampp\htdocs\swd\09-php-shop`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Import [`database.sql`](./database.sql) into MySQL. From PowerShell:

   ```powershell
   cmd.exe /c "C:\xampp\mysql\bin\mysql.exe --user=root < C:\xampp\htdocs\swd\09-php-shop\database.sql"
   ```

   The export creates the database and tables and inserts the three categories. Existing tables are left intact.
4. If your local database credentials differ from XAMPP defaults, configure `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` in Apache's environment. Defaults are `127.0.0.1`, `swd_shop`, `root`, and an empty password.
5. Seed the 15 example products (safe to rerun):

   ```powershell
   C:\xampp\php\php.exe C:\xampp\htdocs\swd\09-php-shop\seed.php
   ```

6. Open [http://localhost/swd/09-php-shop/](http://localhost/swd/09-php-shop/).

## Admin login

Admin credentials are not hard-coded. The login requires an `ADMIN_USERNAME` and a password hash in `ADMIN_PASSWORD_HASH`. Generate a hash locally with PHP; enter the password at the prompt:

```powershell
C:\xampp\php\php.exe -r "echo password_hash(readline('Admin password: '), PASSWORD_DEFAULT), PHP_EOL;"
```

Add these directives to the Apache virtual host serving the shop (or to a local Apache configuration file that applies to this site), replacing the example username and hash with your values:

```apache
SetEnv ADMIN_USERNAME "your-admin-username"
SetEnv ADMIN_PASSWORD_HASH "$2y$10$replace-with-your-generated-password-hash"
```

Restart Apache after changing its environment. Do not commit the real password or hash to the project. The login verifies passwords using PHP's `password_verify()`. For a public deployment, use HTTPS and a strong unique admin password.

Visit [http://localhost/swd/09-php-shop/admin/login.php](http://localhost/swd/09-php-shop/admin/login.php). Admin pages require a successful login. Order status changes follow `pending -> paid -> shipped -> delivered`; eligible orders can also be cancelled. Cancelling restores item stock in the same transaction as the status change.

## Order lookup

Customers can look up an order at [http://localhost/swd/09-php-shop/order-lookup.php](http://localhost/swd/09-php-shop/order-lookup.php) with the order reference and checkout email address.

## Database export

[`database.sql`](./database.sql) contains the database and table definitions plus the initial categories. It does not contain customer orders or product seed data; run `seed.php` to insert the example catalogue.
