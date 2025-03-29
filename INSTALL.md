
# ConfigWiz Installation Guide

This guide explains how to install and deploy ConfigWiz on a local Linux server using NGINX and PHP.

---

## Requirements

- Debian-based Linux system (e.g., Ubuntu)
- NGINX
- PHP with FPM
- Git

---

## 1. Install Dependencies

```bash
sudo apt update
sudo apt install -y nginx php php-fpm php-cli git
```

- **NGINX**: The web server to serve the ConfigWiz app.
- **PHP**: The main PHP package.
- **PHP-FPM**: PHP FastCGI Process Manager to handle PHP requests.
- **PHP-CLI**: Command-line interface for PHP.
- **Git**: For cloning the repository.

---

## 2. Clone the Repository

```bash
sudo git clone https://github.com/<your-username>/configwiz2.git /var/www/configwiz
sudo chown -R www-data:www-data /var/www/configwiz
```

---

## 3. Set Permissions

Ensure the `sessions` and `uploads` directories are writable by the web server:

```bash
sudo chown -R www-data:www-data /var/www/configwiz/sessions /var/www/configwiz/uploads
sudo chmod -R 750 /var/www/configwiz/sessions /var/www/configwiz/uploads
```

---

## 4. Configure the Application

Move the sample configuration file into place for Google Analytics:

```bash
sudo mv /var/www/configwiz/includes/config.sample.php /var/www/configwiz/includes/config.php
```

Then edit it with your settings if needed:

```bash
sudo nano /var/www/configwiz/includes/config.php
```

---

## 5. Configure NGINX

Create a new site config:

```bash
sudo nano /etc/nginx/sites-available/configwiz
```

Paste:

```nginx
server {
    listen 80;
    server_name configwiz.local;

    root /var/www/configwiz;
    index index.php index.html;

    access_log /var/log/nginx/configwiz_access.log;
    error_log  /var/log/nginx/configwiz_error.log;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;  # Adjust if using a versioned socket
    }

    location ~ ^/(includes|sessions|configs|uploads)/ {
        deny all;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/configwiz /etc/nginx/sites-enabled/
```

(Optional) Disable the default site:

```bash
sudo rm /etc/nginx/sites-enabled/default
```

---

## 6. Restart Services

```bash
sudo systemctl restart php-fpm
sudo systemctl reload nginx
```

---

## 7. Access the App

Visit in your browser:

```
http://<your-server-ip>/
```

---

## 8. Optional

- Adjust `upload_max_filesize` and `post_max_size` in `php.ini` if needed.
- For public access, consider adding HTTPS and security hardening.

---

## Notes

- This guide assumes local or LAN-based deployment without HTTPS.
- Do not expose the server publicly without proper hardening and SSL setup.