# Remi AI — Complete AI Reminder SaaS Foundation (Phase 1)

Remi AI is an enterprise-grade multi-user SaaS application that empowers users to create and manage personal and professional reminders through natural language in **Bengali**, **Banglish**, and **English**, powered by **NVIDIA NIM** (`openai/gpt-oss-20b`).

---

## 1. Executive Summary & Technology Stack

- **Backend Framework:** Laravel 12 on PHP 8.2+
- **Database:** MySQL 8.0+ / MariaDB 10.4+ (Strict UTF8MB4 Unicode)
- **Frontend Architecture:** Laravel Blade, Tailwind CSS, Alpine.js (Vite asset pipeline)
- **AI Engine:** NVIDIA NIM OpenAI-compatible Chat Completions API
  - **Base URL:** `https://integrate.api.nvidia.com/v1`
  - **Default Model:** `openai/gpt-oss-20b`
- **Supported Natural Input Languages:** Bengali (`বাংলা`), Banglish (Bengali in Latin script), and English
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

## 9. Prompt 2 Handoff Checklist

The following modules are cleanly architected for implementation in **Prompt 2**:
- [ ] Official Meta WhatsApp Business Platform Cloud API integration.
- [ ] Customer WhatsApp number verification and opt-in consent workflow.
- [ ] Automated scheduled reminder delivery via WhatsApp and cron.
- [ ] Meta approved WhatsApp template management.
- [ ] Delivery retry engine, failure tracking, and delivery receipts webhook.
- [ ] SaaS subscription tiers, customer entitlements, and payment gateways.
- [ ] Complete Super Admin dashboard, tenant management, and system-wide usage metering.
