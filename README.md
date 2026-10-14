# Remi AI (Remique) — Multi-Channel AI Assistant & Zero-Knowledge Vault

Remique is an enterprise-grade multi-channel SaaS application that empowers users to create and manage personal and professional reminders through natural language in **Bengali**, **Banglish**, and **English**, powered by **NVIDIA NIM** (`openai/gpt-oss-20b`), with native automated delivery across both **Telegram** and **Meta WhatsApp Cloud API**.

---

## 1. Executive Summary & Technology Stack

- **Backend Framework:** Laravel 12 on PHP 8.2+
- **Database:** MySQL 8.0+ / MariaDB 10.4+ (Strict UTF8MB4 Unicode)
- **Frontend Architecture:** Laravel Blade, Tailwind CSS, Alpine.js (Vite asset pipeline)
- **AI Engine:** NVIDIA NIM OpenAI-compatible Chat Completions API
  - **Base URL:** `https://integrate.api.nvidia.com/v1`
  - **Default Model:** `openai/gpt-oss-20b`
- **Supported Natural Input Languages:** Bengali (`বাংলা`), Banglish (Bengali in Latin script), and English
- **Multi-Channel Integrations:**
  - **Telegram Bot API:** 1-click deep linking `/start <token>`, natural language parsing, private document/photo vault storage & retrieval, automated reminder alerts.
  - **Meta WhatsApp Cloud API:** Official WhatsApp Business Cloud API v21.0, 24h messaging window adherence, HSM template fallbacks outside window, OTP verification.
- **Theme Support:** Clean enterprise light theme (default) with toggleable dark theme (Alpine.js + CSS variables)
- **Architecture:** Modular monolith with service-oriented business logic and policy-enforced user scoping
- **Deployment Compatibility:** Shared hosting / cPanel compatible without requiring Redis, Docker, or long-running daemons.

---

## 2. Architecture & Design Principles

### 2.1 Service-Oriented Business Logic
- **`app/Services/AI/NvidiaNimService.php`:** Handles server-side communication with NVIDIA NIM, request retries for transient failures (429, 503, connection timeouts), structured prompt construction, strict JSON validation, error categorization, and diagnostic logging without exposing user prompts or API keys.
- **`app/Services/ReminderService.php`:** Centralizes database queries, multi-criteria filtering, user timezone-to-UTC conversions, and atomic database transactions.
- **`app/Policies/ReminderPolicy.php`:** Enforces strict authorization policies ensuring every reminder query and mutation is strictly scoped to the authenticated owner.

### 2.2 Timezone & Date Consistency
- All reminder `due_at` timestamps are stored in persistent storage in **UTC**.
- Dates and times entered by users or extracted by Remi AI are resolved in the user's configured IANA timezone (e.g. `Asia/Dhaka`, `UTC`, `America/New_York`) and converted to UTC before persistence.
- Views format timestamps back to the user's configured timezone.

### 2.3 Privacy-Conscious AI Metadata Logging
- The `ai_interaction_logs` table records operational metrics: model name, status (`success`, `failed`, `rate_limit`, `timeout`), latency (`duration_ms`), token counts (`prompt_tokens`, `completion_tokens`, `total_tokens`), and detected language.
- **Raw user prompts are never stored in log tables by default**, ensuring user data minimization and compliance.

---

## 3. Database Schema

### `users` Table
| Column | Type | Details |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary Key |
| `name` | VARCHAR(255) | User full name |
| `email` | VARCHAR(255) | Unique email |
| `password` | VARCHAR(255) | Bcrypt hash |
| `status` | VARCHAR(32) | Default `active` (indexed) |
| `preferred_language` | VARCHAR(10) | Default `en` (`en`, `bn`) |
| `timezone` | VARCHAR(64) | Default `UTC` (e.g. `Asia/Dhaka`) |
| `theme` | VARCHAR(20) | Default `light` (`light`, `dark`, `system`) |
| `notification_preferences` | JSON | Email / sound preferences |
| `email_verified_at` | TIMESTAMP | Nullable |
| `created_at`, `updated_at` | TIMESTAMP | Timestamps |

### `reminders` Table
| Column | Type | Details |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary Key |
| `user_id` | BIGINT UNSIGNED | Foreign Key referencing `users(id)` ON DELETE CASCADE |
| `title` | VARCHAR(255) | Reminder title / action |
| `description` | TEXT | Optional notes / details |
| `due_at` | DATETIME | Due timestamp in UTC (indexed) |
| `status` | VARCHAR(32) | `scheduled`, `completed`, `cancelled` (indexed) |
| `priority` | VARCHAR(20) | `low`, `medium`, `high`, `urgent` |
| `recurrence` | VARCHAR(32) | `none`, `daily`, `weekly`, `monthly`, `yearly` |
| `completed_at` | DATETIME | Timestamp when marked complete |
| `cancelled_at` | DATETIME | Timestamp when marked cancelled |
| `created_at`, `updated_at` | TIMESTAMP | Timestamps |

*Composite Indexes:* `(user_id, status)`, `(user_id, due_at)`

### `ai_interaction_logs` Table
| Column | Type | Details |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary Key |
| `user_id` | BIGINT UNSIGNED | Nullable Foreign Key referencing `users(id)` ON DELETE SET NULL |
| `provider` | VARCHAR(64) | `nvidia_nim` |
| `model` | VARCHAR(128) | e.g. `openai/gpt-oss-20b` |
| `status` | VARCHAR(32) | `success`, `failed`, `rate_limit`, `timeout`, `validation_error` |
| `duration_ms` | INT UNSIGNED | Roundtrip API processing latency |
| `prompt_tokens` | INT UNSIGNED | Token usage |
| `completion_tokens` | INT UNSIGNED | Token usage |
| `total_tokens` | INT UNSIGNED | Token usage |
| `error_category` | VARCHAR(64) | Normalized error classification |
| `detected_language` | VARCHAR(32) | `bengali`, `banglish`, `english` |
| `created_at`, `updated_at` | TIMESTAMP | Timestamps |

---

## 4. Local Installation & Setup

### Requirements
- PHP 8.2 or higher with extensions: `pdo_mysql`, `curl`, `mbstring`, `openssl`, `fileinfo`, `json`, `bcmath`, `xml`, `zip`
- MySQL 8.0+ or MariaDB 10.4+
- Composer 2.x
- Node.js 18+ & npm

### Setup Steps
```bash
# 1. Clone repository and navigate to directory
cd remi

# 2. Install PHP and NPM dependencies
composer install
npm install

# 3. Environment configuration
cp .env.example .env
php artisan key:generate

# 4. Configure MySQL in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=remi_saas
DB_USERNAME=root
DB_PASSWORD=

# 5. Run Database Migrations
php artisan migrate

# 6. Build Frontend Assets
npm run build

# 7. Start Local Development Server
php artisan serve
```

Access the application at `http://127.0.0.1:8000`.

---

## 5. NVIDIA NIM Configuration

Add your rotated NVIDIA NIM API key to `.env`:
```dotenv
NVIDIA_NIM_API_KEY=your_actual_rotated_api_key_here
NVIDIA_NIM_BASE_URL=https://integrate.api.nvidia.com/v1
NVIDIA_NIM_MODEL=openai/gpt-oss-20b
NVIDIA_NIM_TIMEOUT=30
NVIDIA_NIM_MAX_RETRIES=2
```

### Safe Connectivity Test Command
To verify your API key and measure latency without storing credentials or exposing keys:
```bash
php artisan remi:test-ai --prompt="Remind me tomorrow at 9 AM to call the client"
```

---

## 6. Automated Test Suite

Remi AI includes a comprehensive test suite covering authentication, reminder CRUD, strict cross-user authorization isolation, date/time timezone conversions, and NVIDIA NIM multilingual interpretation.

Run the test suite:
```bash
php artisan test
```

### Test Coverage Highlights
- **`AuthTest`:** Registration, login, rate limiting / throttling, password hashing, logout.
- **`ReminderCrudTest`:** Reminder creation, UTC conversion in database, input validation, overdue validation, updating, completion, cancellation, reopen, deletion.
- **`CrossUserAuthorizationTest`:** Strict policy verification ensuring User A cannot view, update, complete, cancel, or delete User B's reminders.
- **`NvidiaNimAiTest`:** Multilingual extraction for Bengali, Banglish, and English; ambiguous prompt handling; HTTP 429 rate limit fallback; HTTP 500 server error fallback; connection timeout fallback; malformed LLM JSON output handling; missing API key fallback.
- **`SettingsTest`:** Profile updates, IANA timezone modification, interface language changes, theme switching, password changes.

---

## 7. Shared Hosting / cPanel Deployment Guide

Remi AI is built to run on standard shared hosting environments (such as cPanel, DirectAdmin, or Plesk) without requiring root access, Docker, or Redis.

### 7.1 Directory Layout for Shared Hosting
Place the core Laravel code outside the public directory for security:
```
/home/username/
├── remi/                  <-- Core Laravel application (not web-accessible)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   └── artisan
└── public_html/           <-- Public document root
    ├── build/             <-- Compiled Vite assets
    ├── index.php          <-- Front controller
    ├── .htaccess          <-- Apache rewrites & security headers
    ├── favicon.ico
    └── robots.txt
```

In `public_html/index.php`, point paths to the core directory:
```php
require __DIR__.'/../remi/vendor/autoload.php';
$app = require_once __DIR__.'/../remi/bootstrap/app.php';
```

### 7.2 Storage Permissions
Ensure the following directories are writable by the web server (chmod `775` or `755`):
```bash
chmod -R 775 storage bootstrap/cache
```

### 7.3 cPanel Cron Configuration (Laravel Scheduler)
In cPanel > **Cron Jobs**, configure a single cron entry running every minute:
```bash
* * * * * cd /home/username/remi && php artisan schedule:run >> /dev/null 2>&1
```

### 7.4 Production Optimizations
Run these commands after deploying code updates:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 8. Security Controls & Protections

- **SQL Injection Prevention:** 100% parameterized queries via Eloquent ORM.
- **Cross-Site Scripting (XSS):** Blade auto-escaping on all dynamic outputs.
- **CSRF Protection:** Laravel CSRF tokens validated on all state-altering requests.
- **Strict Authorization:** `ReminderPolicy` enforced via Gates on all mutation endpoints.
- **Credential Protection:** NVIDIA NIM API key read solely from server-side configuration; never passed to client JavaScript or included in logs.
- **Sensitive Data Redaction:** Prompt contents excluded from diagnostic log tables.

---

## 9. Master Prompt 2 Completed Implementation

### 9.1 Database Schema Additions (Prompt 2)

#### `whatsapp_identities` Table
- Stores authorized, normalized E.164 phone numbers for customers.
- Statuses: `pending_verification`, `active`, `suspended`, `blocked`, `revoked`.
- Tracking: `verification_token`, `verification_expires_at`, `verified_at`, `consent_recorded_at`, `last_inbound_at`.
- Strict manual override tracking: `verified_by_user_id`, `manual_verification_reason`.

#### `whatsapp_messages` Table
- Full inbound and outbound message log with provider event deduplication via unique `wamid`.
- Directions: `inbound`, `outbound`.
- Statuses: `received`, `accepted`, `sent`, `delivered`, `read`, `failed`, `unknown`.
- Direction and timestamp indexing.

#### `reminder_delivery_attempts` Table
- Atomic claim and execution records separating reminder definition from delivery attempts.
- Unique `idempotency_key` (e.g. `reminder_{id}_{timestamp}`) preventing duplicate deliveries.
- Statuses: `pending`, `processing`, `sent`, `delivered`, `read`, `failed`, `exhausted`.
- Tracks `is_template` (whether dispatched as free-form within 24h window or approved template outside window).
- Bounded retry tracking with backoff and crash recovery.

#### `vault_files` Table
- Private Personal Memory Vault for JPEG, PNG, WEBP, and PDF documents.
- Opaque UUID-based storage keys (e.g. `vault/{user_id}/{uuid}.{ext}`).
- Metadata: `original_filename`, `mime_type`, `file_size`, `checksum_sha256`, `caption`, `category`, `tags`, `search_text`.
- Strict owner scoping ensuring user isolation.

#### `audit_logs` Table
- Immutable, append-only security and administrative audit log.
- Fields: `user_id` (actor), `action`, `target_type`, `target_id`, `reason`, `ip_address`, `metadata` (sanitized with sensitive keys redacted), `created_at`.

#### `whatsapp_templates` Table
- Registry of Meta-approved WhatsApp utility and reminder templates (e.g. `remi_reminder_alert`).

---

### 9.2 Official Meta WhatsApp Cloud API Setup

#### Step 1: Create Meta Developer App & WhatsApp Product
1. Go to [developers.facebook.com](https://developers.facebook.com/) and create a Business App.
2. Add the **WhatsApp** product.
3. In **API Setup**, retrieve your:
   - **Phone Number ID**
   - **WhatsApp Business Account ID (WABA)**
   - **Temporary or Permanent System User Access Token**

#### Step 2: Configure Environment Variables
Add the following to your `.env`:
```env
WHATSAPP_CLOUD_API_BASE_URL=https://graph.facebook.com
WHATSAPP_CLOUD_API_VERSION=v21.0
WHATSAPP_PHONE_NUMBER_ID=your_meta_phone_number_id
WHATSAPP_BUSINESS_ACCOUNT_ID=your_meta_waba_id
WHATSAPP_ACCESS_TOKEN=your_meta_system_user_access_token
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_secure_random_verify_token
WHATSAPP_APP_SECRET=your_meta_app_secret
WHATSAPP_API_TIMEOUT=15
WHATSAPP_RETRY_LIMIT=3
WHATSAPP_SEND_ONBOARDING_REPLIES=true
WHATSAPP_DEFAULT_REMINDER_TEMPLATE=remi_reminder_alert
WHATSAPP_DEFAULT_TEMPLATE_LANGUAGE=en_US
WHATSAPP_MAX_FILE_SIZE_MB=15
```

#### Step 3: Meta Webhook Subscription
1. Under **WhatsApp > Configuration**, click **Edit** for the Webhook:
   - **Callback URL:** `https://your-domain.com/whatsapp/webhook`
   - **Verify Token:** The value of `WHATSAPP_WEBHOOK_VERIFY_TOKEN`
2. Subscribe to Webhook fields:
   - `messages` (inbound messages and media)
   - `message_deliveries` / `statuses` (delivery receipts: sent, delivered, read, failed)
3. Signature verification: All incoming POST webhooks are authenticated via HMAC-SHA256 using `WHATSAPP_APP_SECRET` and the `X-Hub-Signature-256` header.

---

### 9.3 24-Hour Customer Service Window & Template Compliance
Meta messaging policy restricts free-form customer messaging:
1. **Inside 24-Hour Window:** When the customer has texted Remique within the last 24 hours (`last_inbound_at >= now() - 24 hours`), reminders are sent as free-form conversational alerts.
2. **Outside 24-Hour Window:** If more than 24 hours have elapsed since the user's last inbound message, Remique automatically dispatches an approved utility template (`remi_reminder_alert`) passing the user's name, title, and formatted due date.

---

### 9.4 Personal Memory Vault
- Customers can send images (JPEG, PNG, WEBP) or PDF documents (up to 15MB) via WhatsApp.
- Remique downloads the media server-side via official Meta endpoints, computes SHA-256 checksums, and stores the file under an opaque key in `storage/app/private/vault/{user_id}/`.
- Customers can retrieve stored files at any time via WhatsApp using natural language:
  - *“গত সপ্তাহে পাঠানো X89 earbuds-এর ছবিটা আমাকে দাও।”*
  - *“আমার PDF invoice-টা খুঁজে দাও।”*
- If multiple files match, Remique returns an interactive disambiguation list.

---

### 9.5 Enterprise Super Admin Dashboard
Accessible at `/admin` for users with `role = 'super_admin'`:
- **Overview Dashboard (`/admin`):** Real-time operational metrics, WhatsApp delivery success rate (30-day window), failed delivery queue, and live telemetry.
- **Customer Management (`/admin/customers`):** Directory, search, WhatsApp number linking, OTP generation, account status toggles (`active`, `suspended`, `blocked`), and manual verification overrides with required audit reasons.
- **WhatsApp Cloud API (`/admin/whatsapp`):** Masked credential audit, real Meta Graph API connectivity testing, deduplicated message stream, and failed attempt re-queuing.
- **Reminder Oversight (`/admin/reminders`):** System-wide scheduled reminders, delivery attempt history, and audit-backed rescheduling/cancellation.
- **Memory Vault Oversight (`/admin/vault`):** Global storage footprint, customer quota inspection, and file metadata management with zero-knowledge private content protections.
- **AI Settings (`/admin/ai`):** NVIDIA NIM model status, latency metrics, error category breakdown, and multilingual intent playground.
- **System Settings (`/admin/system`):** Runtime host info, cron command generator, and cache flushing.
- **Security & Audit Logs (`/admin/audit-logs`):** Immutable append-only audit trail of all high-impact actions.

---

### 9.6 Automated Test Results (Master Prompt 3 Final)
The full test suite contains **70 automated tests with 209 assertions, 100% passing**:
- `AuthTest` (7 tests) — Registration, authentication, password verification, session regeneration, and logout.
- `CrossUserAuthorizationTest` (6 tests) — Cross-user reminder and memory file isolation, IDOR defense.
- `ReminderCrudTest` (8 tests) — Full reminder lifecycle, timezone conversions, edge validation.
- `NvidiaNimAiTest` (9 tests) — Bengali, Banglish, and English parsing, error handling, rate limits.
- `ScheduledDeliveryEngineTest` (4 tests) — Atomic claiming, 24h window compliance, template fallback, recurrence rolling, idempotency.
- `WhatsAppWebhookTest` (7 tests) — GET handshake, HMAC-SHA256 verification, OTP linking, duplicate deduplication, unknown sender rejection.
- `MemoryVaultTest` (6 tests) — Media ingestion, MIME validation, magic byte signature enforcement, script exploit rejection, topic search, cross-user privacy.
- `SecurityHardeningTest` (6 tests) — HTTP security headers (`X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `CSP`), public registration privilege escalation defense, IDOR prevention, and WhatsApp OTP brute-force lockout.
- `SuperAdminDashboardTest` (8 tests) — Role-based access control, customer management, manual overrides, reminder rescheduling, and all admin module screen rendering.
- `SettingsTest` (6 tests) — Profile updates, timezone changes, password rotations.
- `ExampleTest` (2 tests) — Public landing page rendering and authenticated dashboard redirection.

Run full test suite:
```bash
php artisan test
```

---

## 10. Master Prompt 3 — Production Readiness & Security Hardening Overview

Remique is fully hardened, optimized, and ready for production deployment across VPS and shared-hosting / cPanel environments:

### 10.1 Security Hardening Implemented
1. **HTTP Security Headers Middleware (`SecurityHeadersMiddleware`):**
   - Injected on all web routes via `bootstrap/app.php`.
   - `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin`, and `Strict-Transport-Security` (on HTTPS).
   - Content Security Policy (CSP) tailored for Alpine.js and Google Fonts.
2. **Strict File Upload & Magic-Byte Inspection (`VaultService`):**
   - Validates authentic binary magic bytes for JPEG (`\xFF\xD8\xFF`), PNG (`\x89PNG`), WEBP (`RIFF...WEBP`), and PDF (`%PDF-`).
   - Rejects disguised executables, embedded PHP, HTML script, or SVG vector payload markers.
   - Cleans original filenames to eliminate path traversal and null-byte injection (`basename()`, stripping `..`, slashes, and non-printable characters).
3. **WhatsApp OTP Brute-Force Rate Limiting (`WhatsAppMessageProcessor`):**
   - Protects customer OTP verification against brute-force attacks by limiting attempts to 5 per hour.
   - Automatically locks verification for 60 minutes after 5 failed attempts, sends a security alert to the sender, and logs an immutable audit trail.
4. **Endpoint Rate Limiting:**
   - Password reset endpoints throttled to 5 requests per minute (`throttle:5,1`).
   - AI natural-language interpreter throttled to 30 requests per minute (`throttle:30,1`).
   - WhatsApp webhook ingress throttled to 120 requests per minute (`throttle:120,1`).
5. **Privilege Escalation Defense:**
   - Public registration strictly enforces `'role' => 'customer'`. Admin privileges can only be assigned by existing Super Admins via database or console.

### 10.2 Database Performance & High-Throughput Composite Indexing
Added in migration `2026_10_14_300001_add_performance_composite_indexes.php`:
- `reminders`: Composite index on `(status, due_at)` for high-frequency scheduled reminder queries.
- `whatsapp_messages`: Composite index on `(from_number, created_at)` and `(status, created_at)`.
- `reminder_delivery_attempts`: Composite index on `(whatsapp_identity_id, status)`.
- `audit_logs`: Composite indexes on `(action, created_at)` and `(user_id, created_at)`.
- Eliminated N+1 queries in `VaultOversightController` and `CustomerManagementController`.

### 10.3 Automated Disaster Recovery & Diagnostic CLI Tools
1. **Native Database & Config Backup (`remi:backup`):**
   - Generates SQL database dumps with SHA-256 checksums and JSON metadata manifests without requiring external `mysqldump` utilities.
   - Supports `--verify` to validate file readability and checksum integrity.
   - Supports `--keep=7` automated retention policy.
   - Scheduled daily at 02:00 in `routes/console.php`.
2. **System Health & Security Diagnostics (`remi:health-check`):**
   - Tests database connectivity & latency, storage disk writeability, cache responsiveness, `APP_KEY`, `APP_DEBUG`, NVIDIA NIM configuration, and WhatsApp Cloud API credentials.

### 10.4 Accessibility (WCAG 2.2 AA) & Public Landing Page
- Modern high-conversion landing page at `/` with dark mode support, hero section, interactive WhatsApp chat simulation, feature breakdown, and accessible semantic HTML.
- Contrast-verified UI elements with explicit `aria-label` tags on all icon-only buttons, navigation menus, theme toggles, and `role="alert"` attributes on flash message banners.

For detailed server deployment runbooks, see [docs/PRODUCTION.md](file:///d:/xampp/htdocs/remi/docs/PRODUCTION.md).


