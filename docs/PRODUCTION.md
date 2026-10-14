# Remique Production Deployment, Security Hardening & Operations Guide

This guide details the complete production setup, architectural constraints, security controls, backup procedures, and operational runbooks for **Remique** — an AI-powered WhatsApp personal assistant and zero-knowledge memory vault.

---

## 1. System Requirements & Hosting Architecture

Remique is architected for maximum deployment flexibility. It runs seamlessly on both dedicated VPS environments (Ubuntu/Debian with Nginx/PHP-FPM) and standard **cPanel / shared hosting** environments without requiring Docker or Redis.

| Component | Minimum Requirement | Recommended Production |
| :--- | :--- | :--- |
| **PHP Runtime** | PHP 8.2.0+ (BCMath, cURL, Fileinfo, JSON, Mbstring, OpenSSL, PDO_MySQL, Tokenizer) | PHP 8.2 or 8.3 with OPcache enabled |
| **Database** | MySQL 8.0+ or MariaDB 10.5+ (InnoDB, utf8mb4) | MySQL 8.0+ with utf8mb4_unicode_ci |
| **Web Server** | Apache 2.4 with mod_rewrite & headers OR Nginx 1.20+ | Nginx with TLS 1.3 |
| **Queue / Cache** | Database driver (zero-dependency for shared hosting) | Database or Redis |
| **Storage** | Local disk write permissions for `storage/` and `bootstrap/cache` | Local SSD or Cloud Block Storage |
| **External APIs** | NVIDIA NIM API (`openai/gpt-oss-20b`), Meta WhatsApp Cloud API | Outbound HTTPS (Port 443) enabled |

---

## 2. Environment Configuration & Production Secrets

Copy `.env.example` to `.env` on your production server.

```bash
cp .env.example .env
php artisan key:generate
```

### Critical Production Environment Variables

```ini
APP_NAME="Remique Enterprise"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Session Security (HTTPS)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=remique_prod
DB_USERNAME=remique_dbuser
DB_PASSWORD=YOUR_STRONG_DATABASE_PASSWORD

# Cache & Queues (Shared-Hosting Compatible)
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

# NVIDIA NIM AI Configuration
NVIDIA_NIM_API_KEY=nvapi-YOUR_NVIDIA_NIM_TOKEN
NVIDIA_NIM_BASE_URL=https://integrate.api.nvidia.com/v1
NVIDIA_NIM_MODEL=openai/gpt-oss-20b
NVIDIA_NIM_TIMEOUT=30

# Official Meta WhatsApp Cloud API
WHATSAPP_CLOUD_API_BASE_URL=https://graph.facebook.com
WHATSAPP_CLOUD_API_VERSION=v21.0
WHATSAPP_PHONE_NUMBER_ID=YOUR_META_PHONE_NUMBER_ID
WHATSAPP_BUSINESS_ACCOUNT_ID=YOUR_WABA_ID
WHATSAPP_ACCESS_TOKEN=YOUR_SYSTEM_USER_PERMANENT_TOKEN
WHATSAPP_WEBHOOK_VERIFY_TOKEN=YOUR_RANDOM_WEBHOOK_VERIFY_STRING
WHATSAPP_APP_SECRET=YOUR_META_APP_SECRET
WHATSAPP_MAX_FILE_SIZE_MB=15
```

---

## 3. Application Security Hardening

### A. HTTP Security Headers
The application automatically attaches modern security headers to all responses via `SecurityHeadersMiddleware`:
- `X-Frame-Options: SAMEORIGIN` (prevents clickjacking)
- `X-Content-Type-Options: nosniff` (prevents MIME sniffing)
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` (enforced on HTTPS)
- `Content-Security-Policy`: Configured to permit Google Fonts and Alpine.js while restricting unauthorized script sources.

### B. WhatsApp Webhook Cryptographic Verification
1. **GET Verification:** Validates `hub.mode === 'subscribe'` and checks `hub.verify_token` against `WHATSAPP_WEBHOOK_VERIFY_TOKEN` using `hash_equals` (timing-attack resistant).
2. **POST Inbound Payload:** Every incoming webhook POST computes HMAC-SHA256 over the raw request payload with `WHATSAPP_APP_SECRET` and validates against `X-Hub-Signature-256`. Any payload with an invalid or missing signature is immediately rejected with HTTP 403 before parsing.
3. **Deduplication:** Message deduplication uses the Meta provider ID (`wamid`) with unique database constraints to prevent replay attacks or repeated webhook deliveries.

### C. WhatsApp OTP Brute-Force Rate Limiting
- WhatsApp customer verification tokens are limited to 5 attempts per identity per hour via `Illuminate\Support\Facades\RateLimiter`.
- If 5 consecutive incorrect tokens are received, the phone number is locked for 60 minutes, an alert is logged in `audit_logs`, and an automated notification is sent to the sender.

### D. Memory Vault File Signature Inspection
All uploaded files (via WhatsApp or web) are strictly inspected before storage:
- **Whitelisted MIME types:** JPEG (`image/jpeg`), PNG (`image/png`), WEBP (`image/webp`), and PDF (`application/pdf`).
- **Magic byte header verification:** Validates exact binary signatures (`\xFF\xD8\xFF` for JPEG, `\x89PNG` for PNG, `RIFF...WEBP` for WEBP, and `%PDF-` for PDF).
- **Active exploit rejection:** Any file containing embedded `<?php`, `<?=`, `<script`, `<html`, or `<svg` payload markers is rejected, preventing SVG stored-XSS and disguised server script execution.
- **Opaque Storage Keys:** Files are saved under UUID keys on the private local disk (`storage/app/vault/{user_id}/{uuid}.{ext}`). Client-supplied filenames are never used for filesystem operations.

### E. Privilege Escalation & IDOR Protection
- Public registration strictly forces `'role' => 'customer'`. Admin privileges cannot be acquired through API or form parameters.
- Super Admin routes (`/admin/*`) require server-side gate authorization (`EnsureSuperAdmin` middleware).
- All customer reminders and vault files are scoped strictly to the authenticated user ID in controllers and policies (`Gate::authorize`).

---

## 4. Database Performance & Optimization

High-throughput indexes have been implemented to guarantee sub-millisecond lookups under high messaging volume:
- `reminders`: Composite index `(status, due_at)` to accelerate the 60-second scheduled reminder worker query.
- `whatsapp_messages`: Composite index `(from_number, created_at)` for instant conversational context retrieval.
- `whatsapp_messages`: Composite index `(status, created_at)` for real-time delivery telemetry.
- `reminder_delivery_attempts`: Composite index `(whatsapp_identity_id, status)` for customer delivery reports.
- `audit_logs`: Composite indexes `(action, created_at)` and `(user_id, created_at)` for rapid audit log analysis.

---

## 5. Automated Backups & Disaster Recovery

Remique includes a native, zero-dependency backup engine that dumps table schemas, records, and checksum manifests without requiring external `mysqldump` utilities.

### Running a Manual Backup with Rollback Verification
```bash
php artisan remi:backup --verify
```

### Backup Command Options
- `--verify`: Recalculates the SHA-256 checksum and validates file readability.
- `--keep=7`: Purges backups older than 7 days (configurable retention).

### Output Artifacts
- **SQL Archive:** `storage/app/backups/remique_db_YYYY-MM-DD_HHMMSS.sql`
- **Metadata Manifest:** `storage/app/backups/remique_manifest_YYYY-MM-DD_HHMMSS.json`
- **Audit Log:** Recorded automatically in `audit_logs` with row counts and SHA-256 hash.

### Restoring from Backup
```bash
mysql -u remique_dbuser -p remique_prod < storage/app/backups/remique_db_YYYY-MM-DD_HHMMSS.sql
```

---

## 6. Operational Health Diagnostics

Run the comprehensive health diagnostic command anytime to verify system readiness:

```bash
php artisan remi:health-check
```

### Diagnostics Performed
1. **Database Connectivity:** Executes latency benchmark and verifies connection state.
2. **Storage Writeability:** Tests file creation, reading, and deletion in `storage/app`.
3. **Cache System:** Validates cache driver operations.
4. **Security Flags:** Validates `APP_KEY`, alerts if `APP_DEBUG=true` in production.
5. **NVIDIA NIM AI Service:** Checks API key and target LLM model configuration.
6. **WhatsApp Cloud API:** Validates access token and phone number ID configuration.
7. **Meta App Secret:** Confirms HMAC signature secret is present for webhook validation.

---

## 7. Cron & Scheduler Setup (Shared Hosting & Dedicated)

### Shared Hosting / cPanel Setup
In cPanel > **Cron Jobs**, add a cron job running once per minute:

```bash
* * * * * /usr/local/bin/php /home/username/public_html/artisan schedule:run >> /dev/null 2>&1
```
*(Replace `/usr/local/bin/php` with your hosting provider's PHP 8.2 binary path and `/home/username/public_html` with your actual document root).*

### Dedicated Server (Systemd / Crontab)
Add to system crontab (`crontab -e`):
```bash
* * * * * cd /var/www/remique && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Tasks Executed
- `remi:send-due-reminders` runs every minute to query and dispatch due reminders via WhatsApp.
- `remi:backup --verify --keep=7` can be scheduled weekly in `routes/console.php`.

---

## 8. Credential Rotation Procedures

When rotating production credentials:
1. **Meta App Secret & Verify Token:**
   - Generate new random string for `WHATSAPP_WEBHOOK_VERIFY_TOKEN`.
   - Update in Meta App Dashboard > WhatsApp > Configuration > Callback URL.
   - Update `.env` and run `php artisan config:clear`.
2. **Meta WhatsApp Access Token:**
   - Generate new System User token in Meta Business Manager with `whatsapp_business_messaging` permissions.
   - Update `WHATSAPP_ACCESS_TOKEN` in `.env`.
   - Verify connectivity via Admin Console > WhatsApp Tools > "Test API Connectivity" or `php artisan remi:health-check`.
3. **NVIDIA NIM API Key:**
   - Generate new API key at build.nvidia.com.
   - Update `NVIDIA_NIM_API_KEY` in `.env`.
   - Verify using `php artisan remi:test-ai` or Admin Console > AI Playground.
4. **Application Key (`APP_KEY`):**
   - Note: Rotating `APP_KEY` invalidates existing encrypted cookies and active user sessions.
   - Run `php artisan key:generate`.
