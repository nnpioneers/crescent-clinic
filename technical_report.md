# Crescent Clinic Management System — Complete Technical Knowledge-Transfer Report

> **Purpose**: This document is an exhaustive, code-level technical audit of the actual repository. It is designed so that another AI tutor can prepare the author for ANY software-developer interview question about this project. Every claim is traceable to a specific file, function, or line number.

---

## Table of Contents

1. [Project Overview & Purpose](#1-project-overview--purpose)
2. [Tech Stack & Dependencies](#2-tech-stack--dependencies)
3. [Architecture & Directory Layout](#3-architecture--directory-layout)
4. [Routing & Request Lifecycle](#4-routing--request-lifecycle)
5. [Database Design & Schema](#5-database-design--schema)
6. [Self-Healing Migration System](#6-self-healing-migration-system)
7. [Authentication & Session Management](#7-authentication--session-management)
8. [Authorization & Role-Based Access Control (RBAC)](#8-authorization--role-based-access-control-rbac)
9. [CSRF Protection](#9-csrf-protection)
10. [CORS Configuration](#10-cors-configuration)
11. [Complete API Endpoint Catalog](#11-complete-api-endpoint-catalog)
12. [Patient Registration & Token System](#12-patient-registration--token-system)
13. [Doctor Workflow & Prescription](#13-doctor-workflow--prescription)
14. [Pharmacy Dispensing & Billing](#14-pharmacy-dispensing--billing)
15. [Direct Medicine Sales](#15-direct-medicine-sales)
16. [Payment System & Split Payments](#16-payment-system--split-payments)
17. [Medicine Return & Refund Logic](#17-medicine-return--refund-logic)
18. [Dual-Table Inventory System (Pharmacy ↔ Agency)](#18-dual-table-inventory-system-pharmacy--agency)
19. [Stock Synchronization (`sync_stock_item`)](#19-stock-synchronization-sync_stock_item)
20. [Generic-Brand Medicine Mapping](#20-generic-brand-medicine-mapping)
21. [Agency/Supplier Management](#21-agencysupplier-management)
22. [OCR Invoice Scanning (Gemini AI)](#22-ocr-invoice-scanning-gemini-ai)
23. [PDF Generation (FPDF)](#23-pdf-generation-fpdf)
24. [WhatsApp Integration](#24-whatsapp-integration)
25. [Automated Backup System (Cron)](#25-automated-backup-system-cron)
26. [Supabase Cloud Storage](#26-supabase-cloud-storage)
27. [Management Dashboard & Analytics](#27-management-dashboard--analytics)
28. [Staff/HR Management](#28-staffhr-management)
29. [UPI Account Management](#29-upi-account-management)
30. [Monitor Module (TV Waiting Room)](#30-monitor-module-tv-waiting-room)
31. [Template Engine](#31-template-engine)
32. [Frontend Architecture](#32-frontend-architecture)
33. [Deployment & Infrastructure](#33-deployment--infrastructure)
34. [Error Handling & Resilience](#34-error-handling--resilience)
35. [Security Vulnerabilities & Honest Weaknesses](#35-security-vulnerabilities--honest-weaknesses)
36. [Codebase Metrics & File Map](#36-codebase-metrics--file-map)

---

## 1. Project Overview & Purpose

**Crescent Clinic and Scans** is a production web application built for a real medical clinic in India. It digitizes the complete patient journey from reception check-in through doctor consultation, pharmacy dispensing, billing, and daily reporting. It also manages the clinic's pharmaceutical supply chain, staff payroll, and automated daily backups.

**Key facts from the code:**
- Clinic name: "Crescent Clinic and Scans" (hardcoded in `pdf_gen.php:28`, `report_pdf_gen.php:28`, WhatsApp message templates)
- Timezone: `Asia/Kolkata` (IST, UTC+05:30) — set in `api.php:4` and `db.php:52`
- Two doctors: Dr. Mohamed Rasith (Gents, prefix "G") and Dr. Jannathul Basheera (Ladies, prefix "L") — seeded in `db.php:361-362`
- Five user roles: receptionist, doctor, pharmacist, management, monitor — seeded in `db.php:354-368`

---

## 2. Tech Stack & Dependencies

| Layer | Technology | Evidence |
|-------|-----------|----------|
| **Backend Language** | PHP ≥ 8.1 | `composer.json:6` |
| **Database** | MySQL / MariaDB | PDO with `mysql:` DSN in `db.php:47` |
| **Frontend** | Vanilla HTML + CSS + JavaScript | `templates/*.html`, `static/js/*.js`, `static/css/style.css` |
| **PDF Library** | FPDF 1.86 | `fpdf_lib/fpdf186/fpdf.php` |
| **Cloud Storage** | Supabase Storage | `app/Services/supabase_storage.php` |
| **AI/OCR** | Google Gemini API | `api.php:5078-5234` |
| **WhatsApp** | Meta Business API / Custom Gateway / Mock | `app/Services/whatsapp_service.php` |
| **Deployment** | Hostinger shared hosting (primary), Vercel (secondary) | `vercel.json`, `.htaccess`, `index.php` comment "Hostinger Entry Point" |
| **PHP Extensions** | `pdo`, `pdo_mysql`, `curl`, `gd` | `composer.json:8-10` |

**No frameworks are used.** No Laravel, no Symfony, no React, no jQuery. The entire application is handcrafted with native PHP, vanilla JavaScript, and raw SQL through PDO.

---

## 3. Architecture & Directory Layout

```
Hospital_project_v1/
├── index.php                    # Production entry point (requires api/index.php)
├── router.php                   # Local dev server router (PHP built-in server)
├── db.php                       # Database connection, schema, migrations, helpers
├── auth.php                     # Session init, CSRF protection, login_required()
├── api/
│   ├── index.php                # Web route controller (login, role pages, /control_access)
│   ├── api.php                  # ALL REST API endpoints (~7,232 lines)
│   └── reports_api.php          # Financial reporting endpoints (~1,058 lines)
├── app/
│   ├── Core/
│   │   ├── session_handler.php  # DatabaseSessionHandler (MySQL-backed sessions)
│   │   └── template_parser.php  # Jinja2-style template engine
│   ├── Helpers/
│   │   └── db_helper.php        # resolve_upi_account() helper
│   └── Services/
│       ├── cron_backup.php      # Automated daily backup orchestrator
│       ├── pdf_gen.php          # Prescription PDF generator (PrescriptionPDF class)
│       ├── report_pdf_gen.php   # Master daily report PDF generator
│       ├── supabase_storage.php # Supabase upload/download/signed-URL functions
│       └── whatsapp_service.php # WhatsApp message sender (3 providers)
├── templates/
│   ├── login.html               # Login page
│   ├── receptionist.html        # Receptionist dashboard
│   ├── doctor.html              # Doctor consultation view
│   ├── pharmacy.html            # Pharmacy dispensing view
│   ├── management.html          # Full management dashboard (~5,528 lines)
│   ├── monitor.html             # TV waiting room display
│   └── portfolio.html           # Developer portfolio page
├── static/
│   ├── css/style.css            # Global stylesheet (~2,320 lines)
│   ├── js/
│   │   ├── script_app_v2.js     # Main JS for reception/doctor/pharmacy (~4,244 lines)
│   │   ├── agency.js            # Agency management JS (~2,769 lines)
│   │   ├── inventory.js         # Inventory page JS (~413 lines)
│   │   └── reports.js           # Financial reports JS (~1,662 lines)
│   └── images/                  # Static image assets
├── fpdf_lib/fpdf186/            # Third-party FPDF library
├── .env                         # Environment variables (DB creds, API keys)
├── .htaccess                    # Apache rewrite rules
├── vercel.json                  # Vercel serverless deployment config
├── composer.json                # PHP dependency declaration
└── start_server.bat             # Windows local dev server launcher
```

**This is a monolithic architecture.** There is no microservice decomposition. A single PHP process handles everything from authentication to PDF generation to AI-powered OCR.

---

## 4. Routing & Request Lifecycle

The system has a **dual-routing architecture** to support both local development and production hosting:

### Production (Hostinger)
```
Browser → .htaccess → index.php → api/index.php
```
- `.htaccess` rewrites `/api/*` → `api/api.php`, `/reports/*` → `api/reports_api.php`, everything else → `index.php`
- `index.php` (root, 6 lines) simply requires `api/index.php`
- `api/index.php` handles web page routes (`/login`, `/receptionist`, `/doctor`, `/pharmacy`, `/management`, `/monitor`, `/portfolio`, `/control_access`, `/logout`)

### Local Development
```
Browser → PHP built-in server → router.php → index.php → api/index.php
```
- `start_server.bat` launches `php -S 0.0.0.0:8005 router.php`
- `router.php` serves static files (CSS, JS, images) directly, then falls through to `index.php`

### Vercel (Secondary Deployment)
```
Browser → vercel.json routes → api/*.php (serverless functions)
```
- `vercel.json` maps `/api/*` → `api/api.php`, `/reports/*` → `api/reports_api.php`, `/*` → `api/index.php`
- Uses `vercel-php@0.9.0` runtime
- Includes a cron job: `/api/cron/backup` at `30 15 * * *` (3:30 PM UTC = 9:00 PM IST)

### API Routing Pattern (Inside `api/api.php`)
The API router is a **procedural if-chain**, not a class-based router. Each endpoint is a top-level `if` block:

```php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($uri === '/api/doctors' && $method === 'GET') { ... }
if ($uri === '/api/register_patient' && $method === 'POST') { ... }
// ... ~90 more endpoints ...
json_response(['error' => 'API Endpoint not found'], 404); // Fallback at line 7231
```

For dynamic routes (e.g., `/api/patient/123`), `preg_match()` is used:
```php
if (preg_match('/^\/api\/patient\/(\d+)$/', $uri, $matches)) { ... }
```

**Interview insight**: There is NO early `exit` after matching — every request falls through ALL preceding `if` blocks until a match is found. This means a request to the last endpoint at line 7176 must pass through ~90 non-matching `if` conditions. This is O(n) routing, not O(1) like frameworks with hash-map routers.

---

## 5. Database Design & Schema

The database contains **21 tables** (all InnoDB, utf8mb4). Here is the complete schema created in `db.php:200-417`:

### Core Clinical Tables
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `users` | All system users (doctors, staff, admin) | `id`, `username`, `password`, `role`, `doctor_type`, `display_name`, `token_prefix`, `is_active`, `admin_security_password` |
| `patients` | Patient visit records (one row per visit) | `id`, `name`, `age`, `gender`, `phone`, `doctor_id`, `token`, `status` (waiting→prescribed→completed), `patient_id` (permanent CCS ID), `spo2` |
| `prescriptions` | Doctor prescriptions + pharmacy billing | `patient_id` (FK), `medicines` (JSON), `consultation_fee`, `scan_fee`, `total_amount`, `cost_amount`, `cash_amount`, `gpay_amount`, `phonepe_amount`, `balance_amount`, `discount_percent`, `status` (pending→dispensed), `diagnosis_photo`, `prescription_photo` |
| `sessions` | MySQL-backed PHP session storage | `id`, `data`, `expires_at` |
| `system_settings` | Key-value config store | `setting_key`, `setting_value` |

### Sales & Finance Tables
| Table | Purpose |
|-------|---------|
| `direct_sales` | Walk-in medicine sales (without doctor consultation) |
| `medicine_returns` | Return/refund log for both prescriptions and direct sales |
| `upi_accounts` | Registered UPI/bank accounts for payment tracking |

### Inventory Tables (Dual System)
| Table | Purpose |
|-------|---------|
| `inventory` | **Pharmacy-side** stock (unit = tablets) |
| `agency_items` | **Agency-side** stock (unit = strips/boxes) |
| `generic_mappings` | Generic↔Brand name relationship table |

### Agency/Supplier Tables
| Table | Purpose |
|-------|---------|
| `agency_suppliers` | Supplier master data (name, GST, contact, payment status) |
| `agency_categories` | Medicine category definitions |
| `agency_purchases` | Purchase order headers (supplier, invoice, totals) |
| `agency_purchase_items` | Line items within a purchase order |
| `agency_stock_adjustments` | Manual stock corrections |
| `agency_stock_transfers` | Stock movement between locations |
| `agency_ocr_documents` | Uploaded OCR scan records |
| `agency_purchase_returns` | Returns to suppliers |
| `agency_return_items` | Line items in supplier returns |
| `agency_audit_trail` | Audit log for agency operations |
| `agency_inventory_movements` | Stock movement tracking |

### HR Tables
| Table | Purpose |
|-------|---------|
| `staff_records` | Employee master data |
| `staff_payments` | Salary and advance payment history |

### Backup Tables
| Table | Purpose |
|-------|---------|
| `whatsapp_backup_logs` | Daily backup send status tracking |

### Key Relationships
- `prescriptions.patient_id` → `patients.id` (CASCADE delete)
- `agency_purchases.supplier_id` → `agency_suppliers.id` (SET NULL on delete)
- `agency_purchase_items.purchase_id` → `agency_purchases.id` (CASCADE)
- `agency_purchase_items.item_id` → `agency_items.id` (CASCADE)
- `staff_payments.staff_id` → `staff_records.id` (CASCADE)

### Notable Design Decisions
1. **Medicines stored as JSON**: `prescriptions.medicines` and `direct_sales.medicines` store the medicine list as a JSON text column, not as a separate relational table. This simplifies reads but makes querying individual medicines harder.
2. **No password hashing for login passwords**: Regular user passwords are stored in plaintext (`db.php:358-368`). Only `admin_security_password` has optional bcrypt hashing (`api.php:4104-4113`).
3. **Duplicate permanent ID system**: Each patient gets both an auto-increment `id` AND a permanent `patient_id` (format: "CCS1", "CCS2", etc.) assigned post-insert (`api.php:560-578`).

---

## 6. Self-Healing Migration System

One of the most distinctive architectural patterns is the **runtime schema migration** system. Instead of using a migration tool (like Laravel's artisan migrate), the system checks and modifies the database schema on every request.

### Where it happens:
1. **`db.php:67-174`** — Runs on first `get_db()` call per PHP process:
   - Checks if `users` table exists; if not, calls `init_db_schema()` to create all tables
   - Runs `SHOW COLUMNS FROM inventory` and adds missing columns (`generic_name`, `brand_name`, `agency_name`, `row_location`, `col_location`)
   - Creates `generic_mappings` table if it doesn't exist
   - Self-heals columns on `generic_mappings` (`expiry_date`, `min_stock`, `category`)
   - Syncs `supplier_id` between `inventory` and `agency_items`

2. **`auth.php:16-20`** — Cold-start failsafe:
   ```php
   try {
       $check = get_db()->query("SELECT 1 FROM agency_purchases LIMIT 1");
   } catch (Exception $e) {
       init_db();
   }
   ```

3. **`api.php:376-468`** — Runs on every API request:
   - Checks `users` table for missing columns (`details`, `photo_path`, `specialization`, `admin_security_password`, `token_prefix`, `is_active`, `doctor_registration_number`)
   - Creates `staff_records` and `medicine_returns` tables if missing
   - Checks `agency_purchases`, `agency_suppliers`, `direct_sales`, `prescriptions`, `inventory`, `agency_items`, `agency_purchase_items` for missing columns
   - Uses `SHOW COLUMNS FROM <table>` + conditional `ALTER TABLE ADD COLUMN`

### Why this pattern exists:
The clinic uses Hostinger shared hosting where the developer doesn't always have migration tool access. This approach guarantees the database schema stays correct even if a column is added in a code update but the DB hasn't been manually altered. It's a form of **defensive programming** for zero-downtime deployments.

### Performance implications:
Every single API request triggers multiple `SHOW COLUMNS FROM` queries across 7+ tables. On a production database with concurrent users, this adds measurable overhead. The `$migrated` flag in `db.php:80` prevents re-running within the same PHP process, but doesn't help across requests.

---

## 7. Authentication & Session Management

### Session Storage (MySQL-backed)
**File**: `app/Core/session_handler.php`

Sessions are stored in the `sessions` MySQL table, NOT in the default PHP file-based session storage. This is implemented via `DatabaseSessionHandler`, which implements PHP's `SessionHandlerInterface`:

```php
class DatabaseSessionHandler implements SessionHandlerInterface {
    public function read(string $id): string|false {
        $stmt->prepare("SELECT data FROM sessions WHERE id = ? AND expires_at > ?");
        // Returns serialized session data
    }
    public function write(string $id, string $data): bool {
        $stmt->prepare("REPLACE INTO sessions (id, data, expires_at) VALUES (?, ?, ?)");
        // Upserts session data
    }
    public function gc(int $max_lifetime): int|false {
        $stmt->prepare("DELETE FROM sessions WHERE expires_at <= ?");
        // Cleans expired sessions
    }
}
```

**Why**: This makes the application compatible with serverless environments (like Vercel) where the filesystem is ephemeral. Sessions survive across different server instances because they're stored in the shared database.

### Session Configuration (`auth.php:6-11`)
- `gc_maxlifetime`: 86400 seconds (24 hours)
- `cookie_lifetime`: 86400 seconds
- `cookie_httponly`: 1 (prevents JS access to cookie)
- `use_only_cookies`: 1 (prevents session ID in URL)
- `cookie_samesite`: Lax

### Login Flow (`api/index.php`)
1. User visits `/login` or `/` → login page rendered with role cards
2. User clicks a role card → JavaScript populates hidden form fields
3. Form POSTs to `/login` with `username` and `password`
4. Server validates against `users` table:
   ```php
   $stmt->prepare("SELECT * FROM users WHERE username=?");
   if ($row && $password === $row['password']) { // PLAINTEXT comparison
       $_SESSION['user_id'] = $row['id'];
       $_SESSION['role'] = $row['role'];
       $_SESSION['username'] = $row['username'];
       $_SESSION['display_name'] = $row['display_name'];
       // ... redirect based on role
   }
   ```
5. On success: redirects to role-specific dashboard (`/receptionist`, `/doctor`, `/pharmacy`, `/management`, `/monitor`)
6. On failure: re-renders login page with error

**Critical security note**: Login passwords are compared as **plaintext strings**. There is no `password_hash()` / `password_verify()`. This is a significant security weakness.

### Logout
The logout handler (`api/index.php`) includes protection against **browser prefetch destroying sessions**:
```php
$isPrefetch = (isset($_SERVER['HTTP_X_PURPOSE']) && ...) || 
              (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && ...);
if ($isPrefetch) { exit; } // Don't destroy session on prefetch
session_destroy();
header('Location: /login');
```

---

## 8. Authorization & Role-Based Access Control (RBAC)

### Five Roles
| Role | Access Level | Dashboard |
|------|-------------|-----------|
| `receptionist` | Patient registration, view today's patients, fetch patient history | `/receptionist` |
| `doctor` | View assigned patients, prescribe, set fees, upload diagnosis photos | `/doctor` |
| `pharmacist` | Dispense medicines, direct sales, inventory, agency management, OCR | `/pharmacy` |
| `management` | **Full access to everything** + user CRUD, analytics, staff mgmt | `/management` |
| `monitor` | Read-only view of today's waiting/prescribed patients | `/monitor` |

### Enforcement Mechanisms

**Page-level** (`auth.php:52-67`):
```php
function login_required($role = null) {
    if (!isset($_SESSION['user_id'])) { header('Location: /login'); exit; }
    if ($_SESSION['role'] === 'management') return; // Management bypasses ALL checks
    if ($role && $_SESSION['role'] !== $role) { header('Location: /login'); exit; }
}
```

**API-level** (`api.php:57-79`):
```php
function enforce_api_auth($allowed_roles = []) {
    if (!isset($_SESSION['user_id'])) { json_response([...], 401); }
    if ($_SESSION['role'] === 'management') return; // God mode
    if (!in_array($_SESSION['role'], $allowed_roles)) { json_response([...], 403); }
}
```

**Admin-only** (`api.php:81-92`):
```php
function enforce_admin() {
    if ($_SESSION['role'] !== 'management') { json_response([...], 403); }
}
```

### Management "God Mode"
The `management` role can access ANY endpoint regardless of the `$allowed_roles` array. This is implemented via early return in both `login_required()` and `enforce_api_auth()`.

### Control Access Feature
Management users can impersonate other roles via `/control_access?module=receptionist` (or `doctor`, `pharmacy`, `monitor`). This renders the other role's HTML template while keeping the management session, allowing supervisors to see exactly what staff sees.

For doctor impersonation, the system temporarily sets `$_SESSION['doctor_id']` and `$_SESSION['doctor_type']` to the selected doctor's values (`api/index.php`).

---

## 9. CSRF Protection

**File**: `auth.php:25-46`

A CSRF token is generated once per session:
```php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // 64-char hex string
}
```

Validation occurs for all state-changing HTTP methods (POST, PUT, PATCH, DELETE):
```php
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($uri !== '/login' && $uri !== '/') { // Exempt login
        $client_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($client_token) && isset($_POST['csrf_token'])) {
            $client_token = $_POST['csrf_token'];
        }
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $client_token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid or missing CSRF token']);
            exit;
        }
    }
}
```

The token is sent from the frontend via the `X-CSRF-Token` HTTP header, which is included in the JavaScript `api()` helper function.

---

## 10. CORS Configuration

**File**: `api.php:9-24`

CORS is restricted to specific clinic network IPs:
```php
$allowed_origins = [
    'http://192.168.1.5', 'https://192.168.1.5',
    'http://38.134.139.118', 'https://38.134.139.118',
    'http://192.168.1.5:8005'
];
```

Only requests from these origins get CORS headers. Other origins are silently denied. This is appropriate for a clinic LAN deployment.

---

## 11. Complete API Endpoint Catalog

The system has **~90 API endpoints** in `api/api.php`. Here is the complete catalog grouped by module:

### Receptionist Module
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/api/doctors` | receptionist | List active doctors |
| POST | `/api/register_patient` | receptionist | Register new patient visit |
| GET | `/api/fetch_patient/{query}` | receptionist, doctor, pharmacist | Find patient by phone or CCS ID |
| GET | `/api/patients` | receptionist, doctor, pharmacist, monitor | List today's patients (role-filtered) |
| GET | `/api/patient/{id}` | receptionist, doctor, pharmacist | Get single patient details |

### Doctor Module
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| POST | `/api/prescribe` | doctor | Submit prescription (with photo uploads) |
| POST | `/api/update_doctor_fee` | doctor | Update consultation fee after prescribing |
| GET | `/api/doctor_stats` | doctor | Today's stats for logged-in doctor |
| GET | `/api/treatment/search` | doctor | Autocomplete for injection/IV names |

### Pharmacy Module
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/api/pharmacy_stats` | pharmacist | Today's pharmacy statistics |
| POST | `/api/add_medicines` | (session) | Dispense medicines for prescription |
| POST | `/api/direct_pharmacy` | pharmacist | Quick pharmacy-only dispensing |
| POST | `/api/direct_sales/add` | pharmacist | Walk-in direct medicine sale |
| POST | `/api/direct_sales/pay_pending` | pharmacist | Pay pending balance on direct sale |
| GET | `/api/direct_sales/list` | pharmacist | List direct sales with filters |
| POST | `/api/direct_sales/delete` | pharmacist | Delete a direct sale |
| POST | `/api/direct_sales/update_customer` | pharmacist | Update customer info on sale |
| POST | `/api/return_medicines` | (session) | Process medicine return/refund |
| GET | `/api/returns_history` | (session) | Get return history for a sale |

### Inventory Module
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/api/inventory/search` | (session) | Search inventory with autocomplete |
| POST | `/api/inventory/add` | (session) | Add new inventory item |
| POST | `/api/inventory/update` | (session) | Update inventory item |
| DELETE | `/api/inventory/delete/{id}` | (session) | Delete inventory item |
| POST | `/api/inventory/bulk_tps` | (session) | Bulk update tablets-per-strip |
| POST | `/api/inventory/auto_create_brand` | (session) | Auto-create brand from generic |

### Generic Medicines Module
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/api/generics/search` | (session) | Search generics by name |
| GET | `/api/generics/list` | (session) | Full generic list with pagination |
| GET | `/api/generics/brands` | (session) | Get all brands for a generic |
| POST | `/api/generics/update-mapping` | (session) | Update generic-brand mapping |
| POST | `/api/generics/delete-generic` | (session) | Delete a generic entry |
| POST | `/api/generics/bulk-categorize` | (session) | Bulk update categories |
| POST | `/api/generics/delete-multiple` | (session) | Delete multiple generics |
| POST | `/api/generics/rename-generic` | (session) | Rename a generic medicine |
| POST | `/api/generics/delete-brand-mapping` | (session) | Remove single brand mapping |
| POST | `/api/generics/delete-brand-all-mappings` | (session) | Remove all mappings for a brand |
| POST | `/api/generics/import` | (session) | Bulk import generic mappings |
| POST | `/api/generics/add` | (session) | Add new generic medicine |

### Agency/Supplier Module
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/api/agencies/lookup` | (session) | Search agencies |
| GET | `/api/agency/dashboard` | (session) | Agency overview stats |
| GET | `/api/agency/categories` | (session) | List medicine categories |
| POST | `/api/agency/categories/add` | (session) | Add category |
| DELETE | `/api/agency/categories/delete/{id}` | (session) | Delete category |
| GET | `/api/agency/suppliers` | (session) | List suppliers |
| POST | `/api/agency/suppliers/add` | (session) | Add/update supplier |
| DELETE | `/api/agency/suppliers/delete/{id}` | (session) | Delete supplier |
| GET | `/api/agency/supplier/details/{id}` | (session) | Supplier detail with purchases |
| GET | `/api/agency/items` | (session) | List agency items |
| POST | `/api/agency/items/add` | (session) | Add agency item |
| DELETE | `/api/agency/items/delete/{id}` | (session) | Delete agency item |
| POST | `/api/agency/items/migrate-categories` | (session) | Auto-detect and assign categories |
| POST | `/api/agency/items/update-min-stock` | (session) | Update minimum stock levels |
| POST | `/api/agency/purchase/add` | (session) | Record new purchase from supplier |
| POST | `/api/agency/purchase/mark_paid/{id}` | (session) | Mark purchase as paid |
| DELETE | `/api/agency/purchase/delete/{id}` | (session) | Delete purchase |
| GET | `/api/agency/purchase/details/{id}` | (session) | Purchase detail view |
| POST | `/api/agency/stock/adjust` | (session) | Manual stock adjustment |
| POST | `/api/agency/stock/transfer` | (session) | Stock transfer between locations |
| POST | `/api/agency/ocr_scan` | pharmacist | AI-powered invoice scanning |
| GET | `/api/agency/reports` | pharmacist | Agency financial reports |
| POST | `/api/agency/returns/add` | (session) | Return items to supplier |
| GET | `/api/agency_medicine_list` | (session) | Full medicine name list |

### Management Module
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| POST | `/api/management/verify_security` | management | Verify admin security password |
| GET | `/api/management/users` | management | List all users |
| POST | `/api/management/user/save` | management | Create/update user |
| DELETE | `/api/management/user/delete/{id}` | management | Delete user |
| POST | `/api/management/patient/update` | management | Update patient details |
| DELETE | `/api/management/patient/delete/{id}` | management | Delete patient |
| POST | `/api/management/edit_record` | (session) | Edit prescription/consultation record |
| GET | `/api/management/analytics` | (session) | Full analytics dashboard data |

### Common Endpoints
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| POST | `/api/update_balance` | (session) | Update payment amounts on prescription |
| GET | `/api/scans/all` | (session) | List scan records |
| GET | `/api/patients/all` | (session) | All-time patient list |
| GET | `/api/patients/lookup_by_phone` | (session) | Find patient by phone |
| GET | `/api/patient_history/{phone}` | (session) | Full patient visit history |
| GET | `/api/patient_total_balance/{phone}` | (session) | Sum of pending balances |
| GET | `/api/clear_balances/{phone}` | (session) | Clear balances for a patient |
| GET | `/api/whatsapp_link/{presc_id}` | receptionist, pharmacist | Generate WhatsApp billing link |
| GET | `/api/whatsapp_link/direct/{sale_id}` | pharmacist | WhatsApp link for direct sale |
| GET | `/api/generate_pdf/{presc_id}` | doctor, pharmacist | Download prescription PDF |

### Staff & UPI
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/api/staff_records` | (session) | List staff |
| POST | `/api/staff_records/save` | (session) | Add/update staff |
| DELETE | `/api/staff_records/delete/{id}` | (session) | Delete staff |
| POST | `/api/staff_records/pay_salary` | (session) | Record salary payment |
| POST | `/api/staff_records/add_payment` | (session) | Record advance/bonus |
| GET | `/api/staff_records/history/{id}` | (session) | Staff payment history |
| DELETE | `/api/staff_records/delete_payment/{id}` | (session) | Delete a payment record |
| GET | `/api/upi_accounts` | (session) | List UPI accounts |
| POST | `/api/upi_accounts/add` | (session) | Add UPI account |
| POST | `/api/upi_accounts/update` | (session) | Update UPI account |
| POST | `/api/upi_accounts/toggle` | (session) | Toggle UPI account active/inactive |
| POST | `/api/upi_accounts/delete` | (session) | Delete UPI account |

### System
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/api/cron/backup` | (cron secret) | Trigger daily backup |

---

## 12. Patient Registration & Token System

**File**: `api.php:515-587`, `db.php:483-513`

### Token Generation Algorithm
Each doctor gets a unique prefix based on `doctor_type`:
- Gents doctors → `G` prefix
- Lady doctors → `L` prefix

Token format: `{PREFIX}-{NNN}` (e.g., `G-001`, `L-002`)

```php
function generate_token($doctor_id) {
    $prefix = /* from users.token_prefix */ strtoupper($prefix);
    $today = date('Y-m-d');
    // Get all tokens used today for this doctor
    $rows = /* SELECT token FROM patients WHERE doctor_id=? AND DATE(created_at)=today */;
    $used_numbers = []; // Extract numeric parts
    $next_num = 1;
    while (in_array($next_num, $used_numbers)) $next_num++;
    return sprintf("%s-%03d", $prefix, $next_num); // G-001, G-002, etc.
}
```

The algorithm fills gaps — if token G-002 is deleted, the next patient gets G-002 (not G-004).

### Permanent Patient ID (CCS ID)
After inserting a patient record, the system assigns a permanent clinic-wide ID:
```php
// Check if patient (same name + phone) already has a CCS ID from a prior visit
$stmt->prepare("SELECT patient_id FROM patients WHERE name=? AND phone=? AND id != ? AND patient_id IS NOT NULL");
if ($existing) {
    $ccs_id = $existing['patient_id']; // Reuse existing CCS ID
} else {
    // Generate new: find MAX numeric suffix, increment
    $ccs_id = "CCS" . ($max_num + 1); // CCS1, CCS2, CCS3...
}
$stmt->prepare("UPDATE patients SET patient_id = ? WHERE id = ?");
```

### Manual Token Override
Receptionists can manually assign a specific token number via `manual_token` field. The system validates it's not already in use today.

### Patient Lookup
The `fetch_patient` endpoint supports looking up patients by either CCS ID or phone number:
```php
$search_col = (strpos($query, 'CCS') === 0) ? "patient_id" : "phone";
```
It returns the most recent visit for each unique patient (name+phone combination) using a `MAX(id)` subquery.

---

## 13. Doctor Workflow & Prescription

**File**: `api.php:749-828`

### What the Doctor Does
1. **Views patient queue**: `GET /api/patients` filtered by `doctor_id` and today's date, ordered by token ASC
2. **Writes prescription**: `POST /api/prescribe` with:
   - `consultation_fee`, `scan_fee`, `scan_type`, `scan_notes`
   - `diagnosis`, `prescription_text`
   - `injection_details`, `iv_details`, `injection_cost`, `iv_cost`
   - `upt_card` (boolean for UPT pregnancy test)
   - Optional file uploads: `diagnosis_photo`, `prescription_photo`

### Prescription Insert/Update Logic
The endpoint uses upsert logic:
```php
$check = $conn->prepare("SELECT id FROM prescriptions WHERE patient_id = ?");
if ($existing) {
    // UPDATE existing prescription (preserves existing photos if new ones not uploaded)
} else {
    // INSERT new prescription
}
// Update patient status: waiting → prescribed
$conn->prepare("UPDATE patients SET status='prescribed' WHERE id=?");
```

### Photo Uploads
Diagnosis and prescription photos are uploaded to Supabase Storage in the `medical_records` bucket:
```php
$filename = uniqid('diag_') . '.' . $ext;
upload_to_supabase($_FILES['diagnosis_photo']['tmp_name'], 'medical_records', $filename, $mime_type);
$diag_photo_path = 'medical_records/' . $filename;
```

---

## 14. Pharmacy Dispensing & Billing

**File**: `api.php:928-1175` (the `add_medicines` / `direct_pharmacy` endpoint)

This is the **most complex single endpoint** in the system (~250 lines). It handles both prescription dispensing and direct pharmacy sales within one code path.

### Workflow
1. If called as `/api/direct_pharmacy`: creates a virtual patient + prescription record first
2. For each medicine in the list:
   - If `batch_id` is provided: lookup by inventory ID, deduct stock from that specific batch
   - If no `batch_id`: search by name, use FEFO (First Expiry First Out) via `ORDER BY expiry_date ASC LIMIT 1`
   - If medicine doesn't exist at all: auto-create it with `(Without Brand)` suffix
3. Calculate `cost_amount` using purchase price / tablets_per_strip
4. Handle injection, IV, and UPT deductions separately
5. Update prescription record with medicines JSON, payment info, and set `status='dispensed'`
6. Mark patient as `completed` with `completed_at` timestamp
7. All wrapped in a **database transaction** (`beginTransaction` / `commit` / `rollBack`)

### Auto-Price Healing
If an inventory item has MRP or selling_price of 0, the system auto-fills it from the sale:
```php
if ((float)($row['mrp'] ?? 0) <= 0 || (float)($row['selling_price'] ?? 0) <= 0) {
    $new_mrp = $unit_price_input * $tps_input;
    $conn->prepare("UPDATE inventory SET mrp = ?, selling_price = ?, tablets_per_strip = ? WHERE id=?")
         ->execute([$new_mrp, $new_mrp, $tps_input, $batch_id]);
}
```

### Injection/IV Stock Deduction
Injections (comma-separated names) and IV fluids are each deducted as single units from inventory:
```php
if ($injection_cost > 0 && $injection_details) {
    $injs = array_map('trim', explode(',', $injection_details));
    foreach ($injs as $inj) {
        $deduct_stock_by_name($inj, 'INJ', $injection_cost);
    }
}
```

---

## 15. Direct Medicine Sales

**File**: `api.php:1181-1397`

This is a parallel sales pathway for walk-in customers who don't see a doctor. The code is structurally similar to `add_medicines` but:
- Creates a `direct_sales` record instead of updating a prescription
- Tracks `payment_history` as JSON (array of payment events with timestamps)
- Sets `status` to `'pending'` if there's a balance, `'completed'` otherwise
- Supports pending payment clearing via `/api/direct_sales/pay_pending` with cumulative payment history

---

## 16. Payment System & Split Payments

The system supports **multi-mode payment splitting**:

| Payment Mode | Column |
|-------------|--------|
| Cash | `cash_amount` |
| GPay (Google Pay) | `gpay_amount` |
| PhonePe | `phonepe_amount` |
| Bank Transfer | `bank_amount` |

**Both `prescriptions` and `direct_sales` tables** have these columns. The `paid_amount` is the sum, and `balance_amount` is `grand_total - paid_amount`.

### Balance Tracking Across Visits
The system tracks pending balances from previous visits:
```php
function get_prev_balance_info($conn, $patient_id, $current_presc_id) {
    // Sum of balance_amount from older prescriptions for same patient_id
    // Plus prev_balance_cleared from current prescription
}
```
This enables the clinic to show "You have a pending balance of ₹X from your visit on DD/MM/YYYY" on WhatsApp receipts.

### UPI Account Selection
Payments can be associated with specific UPI accounts from the `upi_accounts` table. The `upi_account` field stores the account's `short_name`, and `account_id` stores its ID.

---

## 17. Medicine Return & Refund Logic

**File**: `api.php:5632-5838`

This is one of the most intricate features. It handles returns from both prescription sales and direct sales within a single transaction.

### Process:
1. Fetch original sale record (prescription or direct_sale)
2. Parse the medicines JSON
3. For each returned item:
   - Validate: returned qty ≤ (sold qty - already returned qty)
   - Calculate return amount at original unit price
   - Update JSON: `returned_qty`, `returned_amount` fields on each medicine object
   - **Re-stock**: `UPDATE inventory SET stock = stock + ? WHERE name = ?`
   - **Log**: Insert into `medicine_returns` table with full audit trail
4. Apply discount adjustment: `total_return_amount * (1 - discount_percent/100)`
5. Smart refund allocation:
   - If patient had outstanding balance: reduce balance first
   - Only refund actual cash if return exceeds remaining balance
6. Deduct refund from the specific payment mode column (cash, gpay, etc.)
7. Update original sale record with new totals

### Return Types
The system supports two return types:
- `Single Tablet`: quantity in individual tablets
- `Full Strip`: quantity converted to equivalent tablets via `equivalent_tablets` field

---

## 18. Dual-Table Inventory System (Pharmacy ↔ Agency)

This is one of the most architecturally significant design decisions.

### Two Separate Inventory Tables

| Table | Perspective | Unit | Used By |
|-------|------------|------|---------|
| `inventory` | Pharmacy counter | Tablets (individual units) | Dispensing, billing |
| `agency_items` | Supplier/warehouse | Strips/Boxes (wholesale units) | Purchasing, supplier management |

### Why Two Tables?
The pharmacy counts in individual tablets (e.g., "10 tablets of Paracetamol"), while the supplier sells in strips (e.g., "1 strip = 10 tablets"). The `tablets_per_strip` field in `inventory` handles this conversion.

### Unit Conversion
When syncing from agency to pharmacy:
```php
// Agency stock (strips) × tablets_per_strip = Pharmacy stock (tablets)
$pharmacy_stock = $agency_stock * $tablets_per_strip;
```

When syncing from pharmacy to agency:
```php
// Pharmacy stock (tablets) ÷ tablets_per_strip = Agency stock (strips)
$agency_stock = floor($pharmacy_stock / $tablets_per_strip);
```

---

## 19. Stock Synchronization (`sync_stock_item`)

**File**: `api.php:200-370`

The `sync_stock_item($conn, $item_name, $batch_number, $source)` function is the **bidirectional sync engine** between `inventory` and `agency_items`.

### How it works:
- **`$source = 'agency'`**: Agency-side change → update/create pharmacy `inventory` record. If agency item was deleted → delete from pharmacy too.
- **`$source = 'pharmacy'`**: Pharmacy-side change → update/create `agency_items` record. If pharmacy item was deleted → delete from agency too.

### Sync is called:
- After every medicine dispensed (`api.php:1010, 1041, 1058`)
- After every direct sale (`api.php:1237, 1263, 1280`)
- After every medicine return (`api.php:5755`)
- After agency purchase stock update
- After manual stock adjustment

### `ensure_synthesized_inventory` Function
**File**: `api.php:153-198`

When a pharmacy sale references a generic name like "Paracetamol (Without Brand)" that doesn't exist in `inventory`, this function:
1. Checks for an `(Unmapped Brand)` record with matching generic name
2. Renames it to the requested `(Without Brand)` name
3. Or creates a new placeholder record from scratch

This enables the pharmacy to sell medicines that haven't been formally entered through the agency purchase workflow.

---

## 20. Generic-Brand Medicine Mapping

**File**: `api.php:5960-7228` (the `sync_generic_mappings` function and generic endpoints)

### Three-Layer Model
```
Generic Name (e.g., "Paracetamol")
  └── Brand Name (e.g., "Dolo 650")
       └── Batch (e.g., "BATCH-A1") → specific stock, price, expiry
```

The `generic_mappings` table links generics to brands, enabling the pharmacy search to show "Paracetamol → Dolo 650, Crocin 500" etc.

### `sync_generic_mappings` Function
This runs in the background (throttled to once every 5 minutes via `static $last_sync`):
1. Auto-cleans duplicate records caused by race conditions
2. Normalizes null/empty batch numbers to `'manual_default'`
3. Syncs data from `agency_items` into `generic_mappings`
4. Updates stock, price, category information

### Medicine Name Normalization
```php
function normalize_medicine_name($name) {
    // Strip BOM characters
    // Strip "(Without Brand)", "(Sold Without Brand)"
    // Strip category tags like (INJ), (TAB), (CAP)
    return trim(strtolower($name));
}
```

### Category Auto-Detection
```php
function detect_medicine_category($item_name) {
    // Pattern-matches against: TAB, CAP, SYP, INJ, CRM, GEL, SPRAY, OINT, DROP, POW, LOT
    // Returns standardized category code
}
```

---

## 21. Agency/Supplier Management

### Supplier Master Data
Table `agency_suppliers` stores: name, company, phone, email, GST number, WhatsApp, DL number, payment type, total purchase/paid/pending amounts, city/state/pincode.

### Purchase Order Workflow
1. **OCR Scan** or manual entry creates purchase details
2. `POST /api/agency/purchase/add` creates:
   - `agency_purchases` header record (supplier, invoice#, date, totals, transport details)
   - `agency_purchase_items` line items (each with qty, rate, GST, batch, expiry)
   - Updates `agency_items` stock for each item
   - Updates supplier's `total_purchase` and `pending_balance`
3. `sync_stock_item` propagates changes to `inventory`

### Supplier Payment Tracking
Suppliers have their own financial tracking: `total_purchase`, `paid_amount`, `pending_balance`, `outstanding_balance` — tracked across multiple payment modes (cash, gpay, phonepe, bank).

### Audit Trail
The `agency_audit_trail` table logs all agency operations with:
- `user_id`, `action`, `table_name`, `record_id`
- `old_value`, `new_value`, `details`, `timestamp`

---

## 22. OCR Invoice Scanning (Gemini AI)

**File**: `api.php:4972-5234`

### Complete Pipeline:
1. **Image upload**: Bill photo uploaded via `$_FILES['bill_image']`
2. **Upload to Supabase**: Original image stored in `ocr_scans` bucket
3. **Image preprocessing** (using PHP GD library):
   - EXIF orientation correction (handles rotated phone photos)
   - Contrast enhancement (`IMG_FILTER_CONTRAST, -15`)
   - Brightness boost (`IMG_FILTER_BRIGHTNESS, 5`)
   - Sharpening via 3×3 convolution matrix
4. **Base64 encoding**: Preprocessed image converted to base64
5. **Gemini API call**: Sends to Google's Generative AI API with a detailed extraction prompt
6. **Model fallback chain**: Tries models in order: `gemini-2.5-flash` → `gemini-2.0-flash` → `gemini-flash-latest` → `gemini-2.5-pro` → `gemini-pro-latest`
7. **JSON parsing**: Extracts supplier info, line items (name, qty, price, batch, expiry, GST), and totals
8. **Error handling**: Accumulates errors from all models; returns detailed error history if all fail

### Prompt Engineering
The prompt is highly specific to medical invoices, instructing Gemini to:
- Read dot-matrix/thermal printer text carefully
- Only extract visible data, not calculate
- Use specific JSON structure with invoice header, items array, and totals

---

## 23. PDF Generation (FPDF)

### Prescription PDF (`app/Services/pdf_gen.php`)

**Class**: `PrescriptionPDF extends FPDF`

Generates a styled PDF with:
- Dark navy header (`#0f172a`) with clinic name
- Sky blue accent lines (`#38bdf8`)
- Patient info section (name, phone, age, gender, token, doctor, timestamps)
- Vitals section (BP, temp, pulse, weight, height)
- Diagnosis & prescription text
- Medicines table (styled with dark header row)
- Payment summary with conditional display (injection fee, IV fee, UPT fee, discount)
- Balance status: red for unpaid, green for fully paid

### Master Report PDF (`app/Services/report_pdf_gen.php`)

**Class**: `MasterReportPDF extends FPDF`

Generates a 4-section daily report:
1. **Executive Summary**: Revenue, expenses, profit, patient count, pending collections
2. **Financials & Payment Modes**: Cash, UPI, pending, cleared amounts
3. **Doctor Revenue Summary**: Per-doctor consultation counts and revenue
4. **Top 10 Selling Medicines**: Sorted by quantity sold

After generation, the PDF is uploaded to Supabase: `upload_buffer_to_supabase($pdf_buffer, 'backups', $filename, 'application/pdf')`

---

## 24. WhatsApp Integration

**File**: `app/Services/whatsapp_service.php`

### Three Provider Modes:

1. **Mock** (`provider = 'mock'`): Default mode. Saves PDF and log to `mock_whatsapp_outbox/` directory. Used for development.

2. **Meta** (`provider = 'meta'`): Uses Facebook/Meta Business API:
   - Step 1: Upload PDF to Meta's media endpoint
   - Step 2: Send document message with media ID + caption
   - Requires: `whatsapp_meta_token` and `whatsapp_meta_phone_id` in system_settings

3. **Custom** (`provider = 'custom'`): Sends base64-encoded PDF to a custom webhook URL. Used for third-party WhatsApp gateway services.

### WhatsApp Billing Links
The API generates pre-filled WhatsApp `wa.me` links with formatted billing messages:
```php
$msg = "🏥 *Crescent Clinic and Scans*\n"
     . "*Patient:* {$rec['name']}\n*Token:* {$rec['token']}\n"
     . "*Medicines:*\n" . /* itemized list */ . "\n"
     . "*Grand Total: ₹{$grand_total}*\n"
     . "*Total Paid: ₹{$paid_amount}*\n"
     . $status_text;  // ✅ Payment Completed / 🛑 Pending Amount
$link = "https://wa.me/$phone?text=" . urlencode($msg);
```

Indian phone numbers are auto-prefixed with `91` if they're 10 digits.

---

## 25. Automated Backup System (Cron)

**File**: `app/Services/cron_backup.php`

### Flow:
1. Triggered by `GET /api/cron/backup` (Vercel cron at 3:30 PM UTC / 9:00 PM IST)
2. Reads `whatsapp_backup_number` and `auto_backup_time` from `system_settings`
3. Checks `whatsapp_backup_logs` for today:
   - Already succeeded → skip
   - Failed ≥ 3 times → skip
   - Failed < 3 times AND > 15 minutes since last → retry
4. Generates master report PDF via `generate_master_report_pdf()`
5. Fetches daily stats (patients, revenue, expenses)
6. Sends PDF + summary via WhatsApp
7. Logs result in `whatsapp_backup_logs`

### Security
Protected by `CRON_SECRET` environment variable:
```php
if ($cron_secret && $authHeader !== "Bearer $cron_secret") {
    json_response(['error' => 'Unauthorized cron request'], 401);
}
```

### Instant Backup
`run_instant_backup_send($to)` allows management users to trigger an immediate backup to any WhatsApp number.

---

## 26. Supabase Cloud Storage

**File**: `app/Services/supabase_storage.php`

Three functions:

1. **`upload_to_supabase($file_path, $bucket, $object_key, $mime_type)`**: Uploads a file. Falls back to local storage if Supabase credentials aren't configured.

2. **`get_supabase_signed_url($bucket, $object_key, $expires_in)`**: Generates time-limited signed URLs for private file access. Falls back to `/static/uploads/...` path locally.

3. **`upload_buffer_to_supabase($buffer, $bucket, $object_key, $mime_type)`**: Uploads raw data (not from disk). Used for PDF uploads.

### Buckets Used:
- `medical_records` — diagnosis and prescription photos
- `profiles` — user profile photos
- `ocr_scans` — uploaded invoice images
- `backups` — generated report PDFs

### Fallback Behavior
When `SUPABASE_URL` or `SUPABASE_SERVICE_ROLE_KEY` are not set, all uploads fall back to local `uploads/` directory. This allows the system to work fully offline during development.

---

## 27. Management Dashboard & Analytics

**File**: `api.php:3269-3882` (`/api/management/analytics` endpoint)

This is a **massive analytics endpoint** (~600 lines) that computes:

### Per-Date/Range Analytics:
- Total patients, consultations, prescriptions dispensed
- Revenue breakdown: doctor fees, medicine revenue, scan fees, injection/IV/UPT fees
- Cost breakdown: medicine costs, direct sale costs
- Profit calculations: revenue − costs
- Payment mode breakdown: cash, UPI (GPay + PhonePe + bank)
- Pending vs collected amounts
- Discount totals
- Previous balance clearance amounts
- Medicine returns impact

### Doctor-Specific:
- Per-doctor patient count
- Per-doctor consultation revenue
- Per-doctor medicine revenue
- Split by Gents/Lady doctor type

### Top Medicines:
- Top 10 by quantity sold (parsed from JSON `medicines` column in both prescriptions and direct_sales)

### Expense Tracking:
- Total supplier purchases for the period
- Staff salary payments for the period

### Date Filters:
The endpoint accepts `date` and `end_date` query parameters, supporting:
- Single day view
- Date range view
- The SQL uses string interpolation for dates (potential injection risk)

---

## 28. Staff/HR Management

### Staff Records
- CRUD via `/api/staff_records`, `/api/staff_records/save`, `/api/staff_records/delete/{id}`
- Fields: name, phone, education, role, salary, status (Active/Inactive), last_salary_paid_date

### Salary Payments
- `POST /api/staff_records/pay_salary`: Records monthly salary payment
  - Creates `staff_payments` record with `payment_type = 'Salary'`
  - Updates `last_salary_paid_date` on staff record
- `POST /api/staff_records/add_payment`: Records advance/bonus payments
  - Creates `staff_payments` record with `payment_type = 'Advance'` or `'Bonus'`

---

## 29. UPI Account Management

The `upi_accounts` table manages registered payment accounts:
- `account_name`, `short_name` (unique), `bank_name`, `account_number`, `upi_id`
- `account_holder_name`, `ifsc_code`, `notes`
- `is_active` toggle

These accounts appear in payment dropdowns throughout the pharmacy interface, allowing staff to track which UPI account received each payment.

---

## 30. Monitor Module (TV Waiting Room)

**File**: `templates/monitor.html` (469 lines)

A read-only display intended for a TV in the clinic's waiting room. It shows:
- Today's patients grouped by doctor with token numbers
- Patient status: waiting vs prescribed (color-coded)
- Auto-refreshes via polling `GET /api/patients` (with monitor role filter)

The monitor view strips sensitive data — it only receives: `id`, `name`, `token`, `doctor_id`, `doctor_type`, `status`.

---

## 31. Template Engine

**File**: `app/Core/template_parser.php`

A custom, regex-based template parser that mimics Jinja2 syntax:

| Syntax | Purpose | Implementation |
|--------|---------|----------------|
| `{{ var }}` | Escaped output | `htmlspecialchars($data[$key])` |
| `{{{ var }}}` | Raw HTML output | No escaping |
| `{{ url_for('static', filename='path') }}` | Static file URL | Converts to `/static/path` |
| `{% if var %}...{% endif %}` | Conditionals | Simple truthy check |
| `{% include 'file' %}` | Template includes | Recursive `self::render()` |

**Limitation**: The conditional parser is non-nested. It uses a simple regex that cannot handle `{% if %}` blocks inside other `{% if %}` blocks. Also, no `{% else %}` support is implemented.

---

## 32. Frontend Architecture

### JavaScript Files
| File | Lines | Purpose |
|------|-------|---------|
| `static/js/script_app_v2.js` | 4,244 | Main app: reception, doctor, pharmacy UIs |
| `static/js/agency.js` | 2,769 | Agency management: suppliers, purchases, OCR |
| `static/js/inventory.js` | 413 | Inventory list and editing |
| `static/js/reports.js` | 1,662 | Financial dashboards and charts |
| `live_script.js` | 4,012 | Previous version (appears deprecated/backup) |

### Legacy/Backup Files
- `old_script_app.js` (190,745 bytes) — earlier version
- `temp_script_app.js` (192,867 bytes) — transitional version
- Both appear to be backup copies that are no longer actively loaded

### HTML Templates
| File | Lines | Purpose |
|------|-------|---------|
| `management.html` | 5,528 | The largest — contains the entire management dashboard with inline JS |
| `pharmacy.html` | 1,315 | Pharmacy dispensing, direct sales, returns |
| `receptionist.html` | 408 | Patient registration form |
| `doctor.html` | 289 | Patient queue and prescription form |
| `monitor.html` | 469 | TV display with auto-refresh |
| `login.html` | 92 | Role-based login cards |

### Frontend Communication Pattern
JavaScript uses a centralized `api()` function that:
1. Prepends the CSRF token as `X-CSRF-Token` header
2. Handles JSON parsing
3. Redirects to `/login` on 401 responses
4. Shows error toasts on failures

---

## 33. Deployment & Infrastructure

### Primary: Hostinger Shared Hosting
- Entry point: `index.php` → `api/index.php`
- URL rewriting via `.htaccess`
- Database: MySQL on Hostinger
- CI/CD: Git push → auto-deploy (mentioned in README)

### Secondary: Vercel Serverless
- Config: `vercel.json` with `vercel-php@0.9.0` runtime
- Routes map to same PHP files
- Cron job configured: `30 15 * * *`
- MySQL connection uses same remote DB credentials

### Local Development
- `start_server.bat`: Launches PHP built-in server on port 8005
- Uses `router.php` for static file serving
- XAMPP PHP path hardcoded: `C:\xampp\php\php.exe`

### Caching Strategy
- `.htaccess` disables caching for JS/CSS files (`max-age=0, no-cache, no-store`)
- API responses include `Cache-Control: no-store, no-cache` headers
- This ensures deployments take effect immediately without cache issues

---

## 34. Error Handling & Resilience

### Global Exception Handler (`api.php:44-50`)
```php
set_exception_handler(function($e) {
    json_response(['error' => 'Internal Server Error: ' . $e->getMessage()], 500);
});
```

### Global Error Handler (`api.php:52-55`)
```php
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
```

This converts PHP warnings/notices into exceptions, ensuring they're caught by the exception handler and returned as JSON.

### Database Connection Retry (`db.php:42-65`)
```php
$max_retries = 3;
while (true) {
    try {
        $conn = new PDO(...);
        break;
    } catch (PDOException $e) {
        if ($retry_count < $max_retries && /* connection timeout or too many connections */) {
            $retry_count++;
            usleep(500000); // 500ms delay
        } else {
            throw $e;
        }
    }
}
```

Retries on MySQL error codes 2002 (connection refused), 1040 (too many connections), and "Operation not permitted".

### Transaction Safety
Critical operations (dispensing, direct sales, returns) are wrapped in database transactions:
```php
$conn->beginTransaction();
try {
    // ... complex multi-table operations ...
    $conn->commit();
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    json_response(['error' => $e->getMessage()], 400);
}
```

### Error Suppression
- `error_reporting(0)` and `display_errors=0` are set in `api.php:2-3` (production safety)
- Many migration/self-healing blocks silently catch and ignore exceptions

---

## 35. Security Vulnerabilities & Honest Weaknesses

### Critical Issues

1. **Plaintext Password Storage**: User passwords are stored and compared as plaintext in the database. Only `admin_security_password` gets bcrypt hashing, and only when first verified (`api.php:4104-4113`). Login passwords in `db.php:358-368` are seeded as plain strings.

2. **SQL Injection Risk in Date Filters**: Several queries use string interpolation for dates:
   ```php
   $today = date('Y-m-d');
   $stmt = $conn->query("SELECT * FROM patients WHERE DATE(created_at) = '$today'");
   ```
   While `$today` comes from `date()` (not user input), some analytics queries interpolate user-supplied date parameters directly into SQL strings.

3. **Hardcoded Credentials**: The `.env` file contains actual production credentials (database passwords, Supabase keys, Gemini API keys) and is checked into git.

4. **No Rate Limiting**: No throttling on login attempts, API calls, or OCR requests.

5. **CORS Whitelist Contains Internal IPs**: The CORS configuration exposes internal network topology.

### Moderate Issues

6. **Massive Self-Healing Overhead**: Every API request runs 7+ `SHOW COLUMNS FROM` queries on startup.

7. **No Input Sanitization Beyond PDO**: While PDO prepared statements prevent SQL injection in most places, there's no application-level input validation (e.g., email format, phone number format, numeric ranges).

8. **Single-Point-of-Failure Architecture**: No load balancing, no health checks, no graceful degradation.

9. **No Automated Tests**: Zero unit tests, integration tests, or end-to-end tests exist in the repository.

10. **Stock Race Condition**: If two pharmacists dispense the same medicine simultaneously, both could read the same stock level and both decrement, potentially resulting in negative stock. There's no `SELECT ... FOR UPDATE` or optimistic locking.

### Minor Issues

11. **Large Monolithic Files**: `api.php` at 7,232 lines and `management.html` at 5,528 lines are difficult to maintain.

12. **No Logging Infrastructure**: Error handling catches exceptions but doesn't log them to a file or monitoring service.

13. **GD Extension Dependency**: OCR preprocessing requires the GD extension, which may not be available on all hosting environments.

---

## 36. Codebase Metrics & File Map

### Source Code Statistics

| File | Lines | Bytes | Description |
|------|-------|-------|-------------|
| `api/api.php` | 7,232 | 354,459 | All REST API endpoints |
| `templates/management.html` | 5,528 | 341,876 | Management dashboard |
| `static/js/script_app_v2.js` | 4,244 | 224,099 | Main frontend JS |
| `live_script.js` | 4,012 | 206,441 | Legacy JS (backup) |
| `static/js/agency.js` | 2,769 | 143,747 | Agency management JS |
| `static/css/style.css` | 2,320 | 49,411 | Global stylesheet |
| `static/js/reports.js` | 1,662 | 90,727 | Reports JS |
| `templates/pharmacy.html` | 1,315 | 88,402 | Pharmacy template |
| `api/reports_api.php` | 1,058 | 51,427 | Report API endpoints |
| `db.php` | 516 | 28,695 | Database config/schema |
| `templates/monitor.html` | 469 | 16,748 | TV monitor template |
| `static/js/inventory.js` | 413 | 22,822 | Inventory JS |
| `templates/receptionist.html` | 408 | 22,365 | Reception template |
| `app/Services/report_pdf_gen.php` | 298 | 12,367 | Report PDF generator |
| `templates/doctor.html` | 289 | 16,765 | Doctor template |
| `app/Services/pdf_gen.php` | 241 | 8,863 | Prescription PDF generator |
| `app/Services/cron_backup.php` | 213 | 9,388 | Cron backup service |
| `app/Services/whatsapp_service.php` | 155 | 5,930 | WhatsApp integration |
| `app/Services/supabase_storage.php` | 106 | 3,559 | Supabase storage |
| `templates/login.html` | 92 | 3,883 | Login page |
| `auth.php` | 72 | 2,128 | Auth/session/CSRF |
| `app/Core/template_parser.php` | 57 | 2,273 | Template engine |
| `app/Core/session_handler.php` | 52 | 1,632 | Session handler |
| `router.php` | 38 | 1,030 | Dev server router |

### Total Active Source Code
- **PHP Backend**: ~10,000+ lines
- **JavaScript Frontend**: ~9,000+ lines
- **HTML Templates**: ~8,000+ lines
- **CSS**: ~2,300 lines
- **Grand Total**: ~29,000+ lines of hand-written code

### Test Files (Development Utilities)
The repository contains ~20 `test_*.php` and `fix_*.php` scripts used during development. These are one-off debugging scripts, NOT automated test suites. Examples: `test_search.php`, `test_db.php`, `test_analytics.php`, `fix_brand_exec.php`, `clean_duplicates.php`.

---

## Quick-Reference: Key Functions to Know

| Function | File | What It Does |
|----------|------|-------------|
| `get_db()` | `db.php:27` | Singleton PDO connection with retry + auto-migration |
| `init_db()` | `db.php:194` | Creates all 21 tables + seeds default users |
| `generate_token()` | `db.php:483` | Generates daily token (G-001, L-002, etc.) |
| `enforce_api_auth()` | `api.php:57` | Role-based API guard (management bypasses) |
| `enforce_admin()` | `api.php:81` | Management-only guard |
| `sync_stock_item()` | `api.php:200` | Bidirectional inventory ↔ agency stock sync |
| `ensure_synthesized_inventory()` | `api.php:153` | Auto-creates pharmacy records from agency data |
| `normalize_medicine_name()` | `api.php:94` | Strips BOM, tags, brand suffixes from names |
| `detect_medicine_category()` | `api.php:106` | Auto-detects TAB/CAP/SYP/INJ from name |
| `get_mapped_generic_name()` | `api.php:133` | Resolves brand → generic name |
| `sync_generic_mappings()` | `api.php:5960` | Background generic ↔ brand mapping sync |
| `get_prev_balance_info()` | `api.php:470` | Cross-visit pending balance lookup |
| `generate_prescription_pdf()` | `pdf_gen.php:84` | Creates prescription PDF |
| `generate_master_report_pdf()` | `report_pdf_gen.php:53` | Creates daily report PDF |
| `send_whatsapp_pdf()` | `whatsapp_service.php:8` | Sends PDF via WhatsApp (3 providers) |
| `upload_to_supabase()` | `supabase_storage.php:3` | File upload with local fallback |
| `run_auto_backup_check()` | `cron_backup.php:11` | Daily backup orchestrator |
| `TemplateParser::render()` | `template_parser.php:8` | Jinja2-style template rendering |

---

*Report generated from actual source code inspection. No functionality has been assumed or fabricated.*
