# CineVerse - PHP + MySQL Booking Application

CineVerse is a movie ticket booking demo built with plain HTML, CSS, JavaScript, PHP 8+, and MySQL/MariaDB. The frontend stays close to the original design while the browser communicates only with PHP API endpoints.

## Project architecture

- Presentation tier: HTML, CSS, JavaScript in the root project folder
- Application tier: PHP REST-style API under `api/`
- Database tier: MySQL/MariaDB schema and seed files under `database/`

## Folder structure

```text
cineverse/
├── index.html
├── style.css
├── script.js
├── api/
│   ├── config.php
│   ├── db.php
│   ├── movies.php
│   ├── shows.php
│   ├── seats.php
│   ├── bookings.php
│   └── health.php
├── database/
│   ├── schema.sql
│   ├── seed.sql
│   └── reset.sql
├── config/
│   └── config.example.php
├── includes/
│   ├── response.php
│   ├── validation.php
│   └── cors.php
├── admin/
│   └── index.php
├── .htaccess
├── .gitignore
├── README.md
```

## Requirements

- Amazon Linux EC2
- Apache HTTP Server
- PHP 8+
- MariaDB/MySQL
- SELinux optionally enabled

## Install Apache

### Amazon Linux 2

```bash
sudo amazon-linux-extras enable php8.2
sudo yum clean metadata
sudo yum install -y httpd php php-mysqlnd mariadb105-server
```

### Amazon Linux 2023

```bash
sudo dnf install -y httpd php php-mysqlnd mariadb105-server
```

Amazon Linux 2023 uses `dnf` instead of `yum` and may also require `php-mysql`. Check with:

```bash
php -v
```

## Install PHP

```bash
php -v
php -m | grep -i pdo
php -m | grep -i mysql
```

## Install MariaDB/MySQL

```bash
sudo systemctl enable --now mariadb
sudo mysql_secure_installation
```

## Start services

```bash
sudo systemctl enable --now httpd
sudo systemctl enable --now mariadb
sudo systemctl status httpd
sudo systemctl status mariadb
```

## Create the database

```bash
sudo mysql -u root -p
CREATE DATABASE cineverse_db;
CREATE USER 'cineverse_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON cineverse_db.* TO 'cineverse_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Import schema and seed data

```bash
mysql -u cineverse_user -p cineverse_db < /path/to/cineverse/database/schema.sql
mysql -u cineverse_user -p cineverse_db < /path/to/cineverse/database/seed.sql
```

## Configure database credentials

Create a real config file from the example:

```bash
cp /var/www/html/cineverse/config/config.example.php /var/www/html/cineverse/config/config.php
```

Then edit the file and set real values:

```php
<?php

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cineverse_db');
define('DB_USER', 'cineverse_user');
define('DB_PASSWORD', 'your_secure_password');
define('DB_CHARSET', 'utf8mb4');
```

Do not commit real passwords. The file is already listed in `.gitignore`.

## Set Apache document root

```bash
sudo mkdir -p /var/www/html/cineverse
sudo cp -r /path/to/cineverse/* /var/www/html/cineverse/
sudo chown -R apache:apache /var/www/html/cineverse
sudo chmod -R 755 /var/www/html/cineverse
```

Ensure Apache serves the folder. Usually this is the default `/var/www/html` document root, so `/cineverse` works as a subfolder.

## Set permissions

```bash
sudo chown -R apache:apache /var/www/html/cineverse
sudo find /var/www/html/cineverse -type f -exec chmod 644 {} \
sudo find /var/www/html/cineverse -type d -exec chmod 755 {} \
```

## Testing PHP

```bash
php -l /var/www/html/cineverse/api/health.php
php -l /var/www/html/cineverse/api/bookings.php
```

## Testing API

Open:

```text
http://EC2_PUBLIC_IP/cineverse/api/health.php
http://EC2_PUBLIC_IP/cineverse/api/movies.php
```

Expected:

```json
{"success":true,"message":"CineVerse API is running"}
```

## Testing database connection

```bash
php /var/www/html/cineverse/api/health.php
```

If the database cannot connect, check MySQL service and the credentials in `config/config.php`.

## Opening the website

```text
http://EC2_PUBLIC_IP/cineverse/
```

## Troubleshooting

- 500 errors: confirm PHP and DB credentials are correct.
- White screen: check `/var/log/httpd/error_log`.
- API returns 500: verify MariaDB is running and schema imported.
- No CSS or JS: check permissions and Apache document root.
- Booking fails: verify seat rows exist and the unique constraint is in place.

## Security notes

- Do not expose DB credentials in frontend files.
- Disable PHP error display in production.
- Do not open port 3306 publicly.
- Keep `.htaccess` in place to block access to config and database files.
- Do not use unrestricted CORS.

## Future migration to Amazon RDS

If the database is moved to Amazon RDS, keep the EC2 application server and only update `DB_HOST` to the RDS endpoint. The security group for RDS should allow MySQL traffic only from the EC2 security group, not from the public internet.

## Security Group requirements

For EC2:

- HTTP: TCP 80 from `0.0.0.0/0`
- HTTPS: TCP 443 from `0.0.0.0/0` (later)
- SSH: TCP 22 from your own public IP only

Do not expose MySQL port 3306 publicly. If using AWS RDS, allow connections only from the EC2 security group.

## CORS policy

This project intentionally avoids wildcard CORS. The browser and API are expected to live under the same Apache server, so only a small allowlist is used when needed.
