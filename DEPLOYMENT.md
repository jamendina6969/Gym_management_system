# IronPulse Gym Management System — Deployment Guide

## Requirements
- PHP 8.2+ (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `gd`)
- MySQL 8.0+ or MariaDB 10.6+
- Composer 2+ & Node.js 20+ (for asset compilation)
- HTTPS (required for webcam QR scanning in browsers and for PayMongo / Stripe webhooks)

## 1. Local Development Setup (XAMPP)
1. Start **MySQL** in XAMPP Control Panel.
2. Create database `gym_management` (`utf8mb4_unicode_ci`).
3. Run:
   ```bash
   composer install
   npm install && npm run build
   php artisan migrate --seed
   php artisan storage:link
   php artisan serve
   ```
4. Visit `http://127.0.0.1:8000`.

### Default Seeded Accounts (Password: `password`)
| Role | Email |
|---|---|
| Super Admin | `admin@ironpulse.gym` |
| Branch Manager | `manager@ironpulse.gym` |
| Staff / Receptionist | `staff@ironpulse.gym` |
| Trainer | `trainer@ironpulse.gym` |
| Member | `member@ironpulse.gym` |

> **Important:** Change all default passwords before going live in production!

## 2. Production VPS Deployment (Ubuntu + Nginx + Supervisor)
1. Clone repository and set `.env`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://gym.yourdomain.com
   APP_TIMEZONE=Asia/Manila
   APP_CURRENCY=PHP
   APP_CURRENCY_SYMBOL=₱

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_DATABASE=gym_management
   DB_USERNAME=your_db_user
   DB_PASSWORD=strong_password

   QUEUE_CONNECTION=database
   MAIL_MAILER=smtp
   ```
2. Install & optimize:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
3. Point Nginx root to `/path/to/app/public` with Let's Encrypt SSL (Certbot).
4. Add Cron job for Laravel Scheduler (runs daily expiry reminders at 08:00 Asia/Manila):
   ```cron
   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
   ```
5. Run a Supervisor worker for queued emails:
   ```bash
   php artisan queue:work --sleep=3 --tries=3
   ```

## 3. Online Payment Gateways Setup
1. Log in as **Super Admin** → **Settings & Gateways** → **Online Payment Gateways**.
2. Enable **PayMongo** (GCash, Maya, GrabPay, QR Ph, Card) and/or **Stripe**, paste your Public Key, Secret Key, and Webhook Signing Secret (all keys are encrypted at rest).
3. Copy the displayed Webhook URL (`https://gym.yourdomain.com/webhooks/payments/paymongo` or `/stripe`) into your PayMongo/Stripe dashboard and subscribe to:
   - **PayMongo:** `checkout_session.payment.paid`
   - **Stripe:** `checkout.session.completed`
