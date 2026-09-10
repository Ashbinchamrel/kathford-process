#!/usr/bin/env bash
# ============================================================
# Kathford Process Management System – Deployment Script
# Target: Ubuntu 22.04 LTS, Nginx, PHP 8.2, MySQL 8.0, Redis
# Run as root or a user with sudo privileges.
# ============================================================
set -e

APP_DIR="/var/www/kathford-process"
DOMAIN="process.kathford.edu.np"
PHP_VER="8.2"
DB_NAME="kathford_process"
DB_USER="kathford_app"
# DB_PASS is prompted at runtime

echo "========================================"
echo " Kathford Process – Deployment"
echo "========================================"

# ── 1. System packages ─────────────────────────────────────
echo "[1/10] Installing system packages..."
apt-get update -qq
apt-get install -y -qq \
  nginx \
  mysql-server \
  redis-server \
  php${PHP_VER}-fpm \
  php${PHP_VER}-cli \
  php${PHP_VER}-mbstring \
  php${PHP_VER}-xml \
  php${PHP_VER}-bcmath \
  php${PHP_VER}-curl \
  php${PHP_VER}-zip \
  php${PHP_VER}-mysql \
  php${PHP_VER}-redis \
  php${PHP_VER}-gd \
  php${PHP_VER}-intl \
  php${PHP_VER}-imagick \
  composer \
  git \
  unzip \
  certbot \
  python3-certbot-nginx \
  ufw \
  fail2ban

# ── 2. Firewall ────────────────────────────────────────────
echo "[2/10] Configuring UFW firewall..."
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 'Nginx Full'
ufw --force enable

# ── 3. MySQL ───────────────────────────────────────────────
echo "[3/10] Setting up MySQL..."
read -s -p "Enter MySQL password for ${DB_USER}: " DB_PASS
echo
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
echo "Database configured."

# ── 4. Application files ───────────────────────────────────
echo "[4/10] Installing application..."
mkdir -p "${APP_DIR}"
cp -r . "${APP_DIR}/"
cd "${APP_DIR}"

# Install Composer dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# ── 5. Environment file ────────────────────────────────────
echo "[5/10] Creating .env..."
if [ ! -f "${APP_DIR}/.env" ]; then
  cp "${APP_DIR}/.env.example" "${APP_DIR}/.env"
  php artisan key:generate
fi

# Prompt for critical env values
read -p "Google Client ID: " GOOGLE_CLIENT_ID
read -s -p "Google Client Secret: " GOOGLE_CLIENT_SECRET
echo
read -p "SMTP Host: " MAIL_HOST
read -p "SMTP Port [587]: " MAIL_PORT
MAIL_PORT=${MAIL_PORT:-587}
read -p "SMTP Username: " MAIL_USERNAME
read -s -p "SMTP Password: " MAIL_PASSWORD
echo

sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
sed -i "s|GOOGLE_CLIENT_ID=.*|GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID}|" .env
sed -i "s|GOOGLE_CLIENT_SECRET=.*|GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET}|" .env
sed -i "s|GOOGLE_REDIRECT_URI=.*|GOOGLE_REDIRECT_URI=https://${DOMAIN}/auth/google/callback|" .env
sed -i "s|MAIL_HOST=.*|MAIL_HOST=${MAIL_HOST}|" .env
sed -i "s|MAIL_PORT=.*|MAIL_PORT=${MAIL_PORT}|" .env
sed -i "s|MAIL_USERNAME=.*|MAIL_USERNAME=${MAIL_USERNAME}|" .env
sed -i "s|MAIL_PASSWORD=.*|MAIL_PASSWORD=${MAIL_PASSWORD}|" .env
sed -i "s|SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|" .env
sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" .env

# ── 6. Storage & permissions ───────────────────────────────
echo "[6/10] Setting permissions..."
php artisan storage:link || true
chown -R www-data:www-data "${APP_DIR}"
chmod -R 755 "${APP_DIR}"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

# ── 7. Database migrations & seed ─────────────────────────
echo "[7/10] Running migrations..."
php artisan migrate --force
php artisan db:seed --force

# ── 8. Cache ───────────────────────────────────────────────
echo "[8/10] Caching config & routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── 9. Nginx config ────────────────────────────────────────
echo "[9/10] Configuring Nginx..."
cat > /etc/nginx/sites-available/kathford-process <<NGINX
server {
    listen 80;
    server_name ${DOMAIN};
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name ${DOMAIN};

    root ${APP_DIR}/public;
    index index.php;

    # SSL – certbot fills these in
    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    # HSTS
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Block dot-files
    location ~ /\. { deny all; }

    # Laravel
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VER}-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Deny private storage access
    location ~ ^/storage/app/private { deny all; }

    # Static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }

    client_max_body_size 20M;
}
NGINX

ln -sf /etc/nginx/sites-available/kathford-process /etc/nginx/sites-enabled/
nginx -t

# SSL
echo "[9b] Obtaining SSL certificate..."
certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m "it@kathford.edu.np" || \
  echo "SSL setup skipped – run manually: certbot --nginx -d ${DOMAIN}"

# ── 10. Supervisor / Queue worker ─────────────────────────
echo "[10/10] Setting up queue worker..."
apt-get install -y supervisor -qq

cat > /etc/supervisor/conf.d/kathford-process.conf <<SUP
[program:kathford-queue]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
SUP

supervisorctl reread
supervisorctl update
supervisorctl start kathford-queue:*

# Restart services
systemctl restart php${PHP_VER}-fpm
systemctl restart nginx
systemctl enable redis-server
systemctl start redis-server

# Fail2ban – basic jail
cat >> /etc/fail2ban/jail.local <<F2B

[nginx-http-auth]
enabled = true
port = http,https

[nginx-limit-req]
enabled = true
F2B
systemctl restart fail2ban

echo ""
echo "========================================"
echo " Deployment complete!"
echo " Site: https://${DOMAIN}"
echo ""
echo " Next steps:"
echo "  1. Update .env with correct GOOGLE_CLIENT_ID/SECRET"
echo "  2. Add your Google Workspace OAuth callback:"
echo "     https://${DOMAIN}/auth/google/callback"
echo "  3. Login as the first user → promote to super_admin via:"
echo "     php artisan tinker"
echo "     User::first()->update(['role_id' => Role::where('name','super_admin')->first()->id])"
echo "========================================"
