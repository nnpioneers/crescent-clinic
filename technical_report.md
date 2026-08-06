# Clinic Management System - Technical Knowledge-Transfer Report

> **Target Audience:** AI Tutor / System Architecture Reviewer / Technical Interviewer  
> **Source Repository:** `Hospital_project_v1` (Crescent Clinic Management System)  
> **Repository Baseline:** PHP 8.x PDO + MySQL (Hostinger/Vercel Serverless hybrid), Vanilla JS frontend, Custom DB Session Handler, Supabase Cloud Storage fallback.

---

## 1. PROJECT OVERVIEW

### Problem Solved
The **Crescent Clinic Management System** automates and unifies the end-to-end outpatient workflow of a dual-doctor specialty clinic. It addresses queue congestion, patient tracking across male ("Gents") and female ("Lady") doctor consults, real-time waiting-room display on TV monitors, digitized prescription authoring, dual-inventory pharmacy synchronization (Pharmacy vs. Wholesale Agency), and comprehensive financial accounting (patient billing, direct retail sales, supplier agency purchases, staff salary disbursements, and medicine returns).

### Real Users
1. **Receptionist:** Registers new/returning patients, records vital signs, assigns tokens and doctors, manages patient queue.
2. **Doctors (Gents / Lady):** Dr. Mohamed Rasith Sir (Gents doctor) and Dr. Jannathul Basheera Mam (Lady doctor). Access live waiting queue, view past medical history, record complaints/diagnosis, and issue electronic prescriptions with automated dosages.
3. **Pharmacist:** Receives prescriptions, dispenses medicines (calculating tablet/strip conversions), conducts direct OTC counter sales, manages medicine stock and returns.
4. **Management / Admin:** Superuser access to financial analytics, staff creation/payroll, inventory procurement (Agency/Supplier module), system settings, master data, backup logs, and raw database overrides.
5. **TV Waiting Monitor:** Unauthenticated/Read-only display for patient queue broadcast in the waiting hall.

### Major Features / Modules
* **Patient & Queue Management:** Token generation with gender/doctor prefixes (`G-xxx`, `L-xxx`), vital sign logging (BP, SpO2, Pulse, Temp, Weight, Height), queue status tracking (`waiting`, `completed`, `cancelled`).
* **Clinical Consultation & E-Prescription:** Doctor diagnosis recording, e-prescription generation with custom tablet/syrup dosage instructions, diagnosis/prescription image upload.
* **Pharmacy & Inventory Management:** Batch-level stock tracking, auto-detect medicine categories (`TAB`, `CAP`, `SYP`, `INJ`, `OINT`), tablet-to-strip stock conversions, min-stock alerts, generic-to-brand mapping engine.
* **Wholesale Agency Procurement:** Supplier management, purchase invoice recording with CGST/SGST/HSN breakdown, purchase returns, stock transfers, and OCR bill scanning.
* **Billing & Financial Accounting:** Multi-payment mode handling (Cash, GPay, PhonePe, Bank Transfer, Split Payments), balance tracking with phone-based clear balance, return/refund processing, and executive financial reporting (`reports_api.php`).
* **Staff Payroll & Operations:** Staff profiles, attendance/salary disbursement logging (`staff_records`, `staff_payments`).

### Complete Technology Stack
* **Language & Runtime:** PHP 8.x (Hostinger / Apache / Vercel Serverless Compatible).
* **Database:** MySQL / MariaDB (Driver: PHP PDO with `utf8mb4`).
* **Frontend:** HTML5, Vanilla JavaScript (`live_script.js`, `agency.js`, `inventory.js`, `reports.js`), Bootstrap CSS framework, SVG icons.
* **Templating:** Custom PHP Template Parser (`app/Core/template_parser.php`).
* **PDF & Documents:** FPDF Library (`fpdf_lib/fpdf.php`).
* **Cloud Storage:** Supabase Storage REST API (`app/Services/supabase_storage.php`) for diagnosis photos, bill uploads, and backup PDFs.
* **Session Storage:** Database-backed PDO Session Handler (`DatabaseSessionHandler` in `app/Core/session_handler.php`).

### Architecture Diagram
```
                     +-----------------------------------+
                     |         Web Browser Clients       |
                     |  (Reception / Doctor / Pharmacy   |
                     |     / Management / TV Display)    |
                     +-----------------+-----------------+
                                       |
                               HTTP / HTTPS (REST APIs & HTML)
                                       |
                                       v
                    +-------------------------------------+
                    |     Web Server / Edge Router        |
                    | (router.php -> index.php / api.php) |
                    +------------------+------------------+
                                       |
            +--------------------------+--------------------------+
            |                          |                          |
            v                          v                          v
 +--------------------+    +--------------------+    +--------------------+
 |  Auth & CSRF       |    |  API & Business    |    | Template Engine    |
 |  (auth.php)        |    |  Logic             |    | (template_parser)  |
 +---------+----------+    | (api.php /         |    +---------+----------+
           |               |  reports_api.php)  |              |
           |               +---------+----------+              |
           v                         |                         v
 +--------------------+              |               +--------------------+
 | DB Session Handler |              |               | HTML Template Files|
 | (sessions table)   |              |               | (templates/*.html) |
 +---------+----------+              |               +--------------------+
           |                         |
           +-------------------+-----+
                               |
                               v
                     +-------------------+
                     | MySQL Database    |
                     | (u988163119_cresc)|
                     +---------+---------+
                               |
                        External Storage API
                               |
                               v
                     +-------------------+
                     | Supabase Cloud    |
                     | (Bucket Storage)  |
                     +-------------------+
```

### Entry Points & Core Configuration
* **`index.php` (Root):** Server entry point redirecting to `api/index.php`.
* **`router.php`:** Routing script for local PHP built-in server serving static files (`.png`, `.js`, `.css`) directly and proxying all other requests to `api/index.php`.
* **`api/index.php`:** Main HTTP router matching web pages (`/login`, `/receptionist`, `/doctor`, `/pharmacy`, `/management`, `/monitor`) and forwarding API calls to `api.php` or `reports_api.php`.
* **`api/api.php`:** Monolithic API router (~7,230 lines) handling 70+ endpoints for reception, doctor, pharmacy, agency, staff, and analytics.
* **`db.php`:** Database setup, connection pooling, PDO initialization, schema auto-migration (`init_db()`), token generator (`generate_token()`), and doctor lookup utilities.
* **`auth.php`:** Custom session configuration, CSRF token validation (`hash_equals`), and role-based access checks (`login_required()`).
* **`.env`:** Environment variables (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `SUPABASE_URL`, `SUPABASE_KEY`).

---

## 2. COMPLETE PATIENT WORKFLOW

```
[Receptionist Page] --Registers Patient--> (INSERT into patients table) 
       | 
       v
[Queue Status = 'waiting'] --Doctor Selects Token--> [Doctor Page]
       | 
       v
[Doctor Prescribes] --Creates Record--> (INSERT into prescriptions table, UPDATE patients status='completed')
       | 
       v
[Pharmacy Queue] --Pharmacist Dispenses--> (UPDATE prescriptions status='dispensed'/UPDATE stock in inventory)
       | 
       v
[Payment Recording] --Settles Bill--> (UPDATE prescriptions status='completed', paid_amount, balance_amount)
```

### Step 1: Patient Registration & Identification
* **User Action:** Receptionist enters Patient Name, Age, Gender, Phone Number, Address, Vital Signs (BP, Pulse, Temp, Weight, Height, SpO2), Chief Complaint, and selects a Doctor.
* **Role:** `receptionist` (or `management`).
* **Page/Component:** `templates/receptionist.html` -> JavaScript `registerPatient()`.
* **API File & Endpoint:** `api/api.php` -> `POST /api/register_patient`.
* **Functions Executed:** `enforce_api_auth(['receptionist'])`, `get_doctor_name()`, `generate_token()`, `db->prepare("INSERT INTO patients ...")`.
* **Database Operations:** 
  * Queries `users` table for doctor details (`token_prefix`, `doctor_type`).
  * `generate_token()` inspects existing tokens for the selected doctor today and generates the next token (e.g., `G-001` or `L-001`).
  * `INSERT INTO patients (name, age, gender, phone, address, doctor_id, doctor_type, doctor_name, complaint, bp, temp, pulse, weight, height, token, spo2, status, created_at) VALUES (...)`.
* **Validations:** Manual token duplicate check (if receptionist enters custom token number).
* **Next Step:** Patient is added to the active doctor's queue with `status = 'waiting'`.

### Step 2: Queue & Waiting Monitor
* **User Action:** Receptionist and TV Monitor display real-time queue.
* **API File & Endpoint:** `api/api.php` -> `GET /api/patients`.
* **Database Query:** `SELECT * FROM patients WHERE DATE(created_at) = CURDATE() ORDER BY id ASC`.
* **Status Filter:** Shows patients grouped by `status` (`waiting`, `completed`, `cancelled`) and `doctor_id`.

### Step 3: Doctor Consultation & E-Prescription Creation
* **User Action:** Doctor selects patient from waiting queue, reviews past medical history by phone number, enters diagnosis, selects medicines with dosages/frequencies/durations, consultation fee, scan fee, injection details, and optional photo upload.
* **Role:** `doctor` (or `management`).
* **Page/Component:** `templates/doctor.html` -> `savePrescription()`.
* **API File & Endpoint:** `api/api.php` -> `POST /api/prescribe`.
* **Functions Executed:** `enforce_api_auth(['doctor'])`, `upload_to_supabase()` (if diagnosis/prescription images attached), `ensure_synthesized_inventory()`.
* **Database Operations:**
  * `INSERT INTO prescriptions (patient_id, doctor_id, doctor_name, doctor_type, consultation_fee, scan_fee, diagnosis, medicines, injection_details, iv_details, injection_cost, iv_cost, upt_cost, scan_type, scan_notes, total_amount, status, cost_amount, diagnosis_photo, prescription_photo) VALUES (...)`.
  * `UPDATE patients SET status = 'completed', completed_at = NOW() WHERE id = ?`.
* **Validations:** Checks if patient ID exists. Ensures synthesized generic items exist in inventory if prescribed without brand.
* **Next Step:** Prescription is posted to the Pharmacy queue with `status = 'pending'`.

### Step 4: Pharmacy Dispensing & Inventory Deduction
* **User Action:** Pharmacist views pending prescriptions, adjusts medicine quantities (strip/tablet calculations), applies discounts, selects payment mode (Cash/GPay/PhonePe/Bank), and clicks "Dispense & Complete".
* **Role:** `pharmacist` (or `management`).
* **Page/Component:** `templates/pharmacy.html` -> `dispensePrescription()`.
* **API File & Endpoint:** `api/api.php` -> `POST /api/add_medicines`.
* **Functions Executed:** `enforce_api_auth(['pharmacist'])`, `normalize_medicine_name()`, `sync_stock_item()`.
* **Database Operations:**
  * Reads inventory table for stock verification and unit cost calculation (`purchase_price`).
  * Loops through prescription medicines: `UPDATE inventory SET stock = stock - ? WHERE name = ? AND batch_number = ?`.
  * `UPDATE prescriptions SET medicines = ?, total_amount = ?, cost_amount = ?, paid_amount = ?, balance_amount = ?, cash_amount = ?, gpay_amount = ?, phonepe_amount = ?, bank_amount = ?, upi_account = ?, account_id = ?, status = 'completed' WHERE id = ?`.
* **Validations:** Deducts exact individual tablet units from strip stocks (`stock = stock - qty`).
* **Next Step:** Visit billing is completed; print-ready invoice modal is rendered.

---

## 3. USER ROLES AND AUTHORIZATION

### Roles Matrix

| Role | Accessible Pages | Permitted Operations | Restricted Operations | Enforced In |
| :--- | :--- | :--- | :--- | :--- |
| **`receptionist`** | `/receptionist` | Register patients, view doctor queues, lookup phone history | Edit prescriptions, dispense drugs, manage staff, view financial analytics | `auth.php` & `enforce_api_auth(['receptionist'])` |
| **`doctor`** | `/doctor` | View patient queue, view phone history, submit e-prescriptions, upload diagnostic photos | Dispense medicines, alter prices, access financial reports, staff management | `auth.php` & `enforce_api_auth(['doctor'])` |
| **`pharmacist`** | `/pharmacy` | Dispense prescriptions, direct OTC sales, manage pharmacy inventory, process medicine returns | Register patients, consult patients, staff payroll, executive reports | `auth.php` & `enforce_api_auth(['pharmacist'])` |
| **`management`** | `/management`, `/control_access` | Unrestricted superuser access to all modules, financial analytics, staff creation/salary, agency procurement, master database overrides | None | Bypass logic in `auth.php` (`$_SESSION['role'] === 'management'`) |
| **`monitor`** | `/monitor` | Read-only view of patient token queue for waiting hall display | All write operations, patient details viewing | `login_required('monitor')` |

### Technical Authorization Mechanism
The application uses a **Role-Based Access Control (RBAC)** pattern enforced via session variables:
1. **Page-Level Protection:** `login_required($role)` in `auth.php`.
2. **API-Level Protection:** `enforce_api_auth($allowed_roles)` in `api/api.php` and explicit session checks in `api/reports_api.php`.
3. **Management Superuser Rule:** Management role (`management`) automatically bypasses all role checks across the entire system.

---

## 4. AUTHENTICATION AND SESSION MANAGEMENT

### Authentication Flow
1. **Login Request:** `POST /login` with `username` and `password`.
2. **User Lookup:** `SELECT * FROM users WHERE username = ?`.
3. **Password Verification:** Supports backward-compatible password hashes:
   * Checks if password string starts with `$2` (Bcrypt hash): Uses `password_verify($password, $user['password'])`.
   * Fallback for legacy plain-text passwords: Direct string comparison `$user['password'] === $password`.
4. **Account Status Check:** Rejects login if `$user['is_active'] == 0`.
5. **Session Creation & Regeneration:**
   ```php
   session_regenerate_id(true);
   $_SESSION['user_id'] = $user['id'];
   $_SESSION['username'] = $user['username'];
   $_SESSION['role'] = $user['role'];
   $_SESSION['doctor_type'] = $user['doctor_type'];
   $_SESSION['display_name'] = $user['display_name'];
   ```
6. **Session Storage:** Custom PDO session handler (`DatabaseSessionHandler` in `app/Core/session_handler.php`) persisting session data to the `sessions` database table (`id`, `data`, `expires_at`).
7. **CSRF Protection:** Every session initializes a 32-byte hex token (`$_SESSION['csrf_token']`). All state-changing HTTP requests (`POST`, `PUT`, `DELETE`) are validated via header `X-CSRF-Token` or POST parameter `csrf_token` using `hash_equals()`.

---

## 5. DATABASE DESIGN

### Text-Based Entity-Relationship (ER) Diagram

```
 +------------------+           +----------------------+          +-------------------------+
 |     users        |           |      patients        |          |      prescriptions      |
 +------------------+           +----------------------+          +-------------------------+
 | id (PK)          |           | id (PK)              |1       * | id (PK)                 |
 | username         |           | patient_id           +----------+ patient_id (FK)         |
 | password         |           | name                 |          | doctor_id               |
 | role             |           | phone                |          | medicines (JSON text)   |
 | doctor_type      |           | doctor_id            |          | total_amount            |
 | is_active        |           | token                |          | status                  |
 +------------------+           | status               |          +-------------------------+
                                +----------------------+
                                                                               |
                                                                               | (Refers by Name/Batch)
                                                                               v
 +------------------+           +----------------------+          +-------------------------+
 | agency_suppliers |           | agency_purchases     |          |       inventory         |
 +------------------+           +----------------------+          +-------------------------+
 | id (PK)          |1         *| id (PK)              |          | id (PK)                 |
 | name             +-----------+ supplier_id (FK)     |          | name, batch_number (UQ) |
 | company_name     |           | invoice_number       |          | stock, selling_price    |
 +------------------+           +----------+-----------+          | generic_name, brand_name|
                                           |                      +-------------------------+
                                          1|
                                           |
                                           v *
                                +----------------------+
                                | agency_purchase_items|
                                +----------------------+
                                | id (PK)              |
                                | purchase_id (FK)     |
                                | item_id (FK)         |
                                | quantity, total      |
                                +----------------------+
```

### Table Definitions & Primary/Foreign Keys

1. **`users`:** Staff and system login accounts.
   * **PK:** `id` (INT AUTO_INCREMENT).
   * **Keys/Indexes:** `username` (UNIQUE).
2. **`patients`:** Daily clinic registration and queue status.
   * **PK:** `id` (INT AUTO_INCREMENT).
   * **Columns:** `patient_id` (VARCHAR string ID like `P-1001`), `name`, `phone`, `token`, `doctor_id`, `status` (`waiting`/`completed`/`cancelled`).
3. **`prescriptions`:** Clinical visits and pharmacy billing.
   * **PK:** `id` (INT AUTO_INCREMENT).
   * **FK:** `patient_id` -> `patients(id)` ON DELETE CASCADE.
   * **Columns:** `medicines` (JSON text array), `total_amount`, `cost_amount`, `paid_amount`, `balance_amount`, `status`.
4. **`inventory`:** Pharmacy stock and pricing.
   * **PK:** `id` (INT AUTO_INCREMENT).
   * **Keys/Indexes:** `uq_inventory_name_batch` UNIQUE (`name`, `batch_number`).
   * **Columns:** `stock`, `tablets_per_strip`, `purchase_price`, `selling_price`, `min_stock`.
5. **`direct_sales`:** Over-the-counter pharmacy sales without patient consultation.
   * **PK:** `id` (INT AUTO_INCREMENT).
6. **`agency_suppliers` / `agency_purchases` / `agency_purchase_items`:** Wholesale procurement tables for inventory stocking.
   * **FKs:** `agency_purchases.supplier_id` -> `agency_suppliers(id)`, `agency_purchase_items.purchase_id` -> `agency_purchases(id)`.

---

## 6. PATIENT MANAGEMENT

* **Registration:** Form input in `receptionist.html`. Handled by `POST /api/register_patient`.
* **Identification:** `phone` number serves as primary cross-visit lookup key. `GET /api/patients/lookup_by_phone` checks both `patients` and `direct_sales` tables.
* **Patient Unique ID:** Generated formatted ID stored in `patient_id` column.
* **Patient Update:** Handled by `POST /api/management/patient/update` (Management only).

---

## 7. VISIT MANAGEMENT

* A "Visit" in this system is created when a patient is registered at reception, inserting a record into `patients` and generating a daily doctor token (`G-xxx` or `L-xxx`).
* **Visit Lifecycle:** `waiting` -> `completed` (when Doctor prescribes) -> `dispensed`/`paid` (in Pharmacy).
* **Historical Visits:** `GET /api/patients/phone/{phone}` queries all past `patients` and `prescriptions` records for that phone number.

---

## 8. QUEUE MANAGEMENT

* **Queue Entry:** Inserted into `patients` with `status = 'waiting'`.
* **Token Prefix:** `G-` for Gents doctor (Dr. Mohamed Rasith Sir), `L-` for Lady doctor (Dr. Jannathul Basheera Mam).
* **Token Numbering:** `generate_token($doctor_id)` counts existing tokens for the given doctor on `CURDATE()` and formats as 3-digit zero-padded string (`G-001`, `G-002`).
* **Display Order:** Ordered by `id ASC` for today's date.
* **Concurrency:** Sequential token generation checks existing assigned numbers; manual token override allowed if custom token provided.

---

## 9. DOCTOR / CONSULTATION MODULE

* Doctor dashboard (`templates/doctor.html`) displays current queue for logged-in doctor.
* **Medical Record Retrieval:** Selecting a patient triggers AJAX call to `GET /api/patients/phone/{phone}`, displaying prior diagnoses and prescriptions.
* **Data Entry:** Doctor inputs vitals, complaint, diagnosis notes, and selects medicines with custom frequencies (`1-0-1`, `1-1-1`, etc.) and duration in days.
* **Diagnostic Attachments:** Images uploaded via HTML form post to Supabase bucket `medical_records` via `upload_to_supabase()`. Signed URLs generated dynamically via `get_supabase_signed_url()`.

---

## 10. PRESCRIPTION SYSTEM

* **Data Model:** Saved in `prescriptions` table. `medicines` column stores a JSON array of prescribed items:
  ```json
  [
    {
      "name": "Paracetamol 500mg",
      "dosage": "1-0-1",
      "days": "5",
      "qty": 10,
      "price": 2.00,
      "batch_number": "B123"
    }
  ]
  ```
* **Medicine Selection:** Auto-completes from `inventory` and `generic_mappings`.
* **Category Auto-Detection:** `detect_medicine_category()` inspects item names for short codes (`TAB`, `CAP`, `SYP`, `INJ`, `OINT`, `DROP`, `CRM`).

---

## 11. PHARMACY AND INVENTORY

* **Dual Stock Systems:**
  1. `inventory`: Active pharmacy stock.
  2. `agency_items`: Wholesale procurement stock.
* **Stock Synchronization:** `sync_stock_item($conn, $item_name, $batch_number, $source)` maintains bidirectional consistency between `inventory` and `agency_items`.
* **Tablet-to-Strip Conversion:**
  * When receiving stock in strips, if `category` is Tablet (`TAB`) and `tablets_per_strip > 0`, pharmacy stock is converted to individual tablets: `stock = strip_qty * tablets_per_strip`.
* **Dispensing Stock Deduction:**
  * Executed in `POST /api/add_medicines`: `UPDATE inventory SET stock = stock - ? WHERE name = ? AND batch_number = ?`.
* **Negative Stock Prevention:** Checked in frontend and backend before committing deduction.

---

## 12. BILLING SYSTEM

* **Calculation Logic:**
  * `total_amount = consultation_fee + scan_fee + injection_cost + iv_cost + upt_cost + sum(medicine_qty * selling_price) - discount`.
  * Calculated on frontend for live preview, but **re-calculated and verified server-side** in `POST /api/add_medicines` and `POST /api/prescribe` before saving to database.
* **Payment Settlement:** Supports partial payments. Stores `paid_amount`, `balance_amount`, and split payment modes (`cash_amount`, `gpay_amount`, `phonepe_amount`, `bank_amount`).
* **Clear Balance API:** `POST /api/update_balance` allows clearing outstanding balance for a patient phone number across historical prescriptions.

---

## 13. STAFF MANAGEMENT

* **Staff Records Table:** `staff_records` (`id`, `name`, `phone`, `education`, `role`, `salary`, `status`, `last_salary_paid_date`).
* **Payroll Logging:** `POST /api/staff_records/pay_salary` logs payment into `staff_payments` table (`staff_id`, `payment_type`, `amount`, `payment_date`).
* **User Accounts:** Admin creates user login accounts in `users` table via `POST /api/management/user/save`.

---

## 14. APIs

### Important Endpoint Summary

| Method | Endpoint | Purpose | Allowed Roles | DB Tables Affected |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/login` | User authentication & session init | Public | `users`, `sessions` |
| `GET` | `/api/doctors` | List active doctor profiles | Public / Auth | `users` |
| `POST` | `/api/register_patient` | Register patient & issue token | `receptionist` | `patients` |
| `GET` | `/api/patients` | Get live queue for today | Auth | `patients` |
| `POST` | `/api/prescribe` | Submit e-prescription & diagnosis | `doctor` | `prescriptions`, `patients`, `inventory` |
| `POST` | `/api/add_medicines` | Dispense medicines & settle bill | `pharmacist` | `prescriptions`, `inventory`, `agency_items` |
| `POST` | `/api/direct_sales/add` | OTC counter sales | `pharmacist` | `direct_sales`, `inventory` |
| `POST` | `/api/return_medicines` | Process customer medicine return | `pharmacist` | `medicine_returns`, `inventory`, `prescriptions` |
| `GET` | `/api/inventory/search` | Search medicine inventory | Auth | `inventory` |
| `GET` | `/reports_api` | Executive financial analytics | `management` | `patients`, `prescriptions`, `direct_sales`, `agency_purchases` |
| `POST` | `/api/management/user/save` | Create/edit staff login account | `management` | `users` |

---

## 15. FRONTEND ARCHITECTURE

* **Architecture:** Server-side rendered HTML templates populated via custom PHP `TemplateParser`, paired with single-page modular Vanilla JavaScript (`live_script.js`, `agency.js`, `inventory.js`, `reports.js`).
* **Styling:** Custom CSS (`static/css/style.css`) + Bootstrap framework for UI containers, cards, grid layouts, and modal dialogs.
* **Asynchronous Calls:** Standard `fetch()` API carrying credentials (`credentials: 'same-origin'`) and CSRF tokens (`X-CSRF-Token` header).
* **DOM Updates:** Dynamic table updates, queue rendering, and modal popups managed in Vanilla JS without heavy external frameworks (React/Vue).

---

## 16. SECURITY IMPLEMENTATION

### Implemented Security Controls
1. **Password Hashing:** Bcrypt hashing (`password_hash($pw, PASSWORD_BCRYPT)`) with fallback for legacy accounts.
2. **SQL Injection Protection:** Extensive use of PDO Prepared Statements (`$conn->prepare("...")->execute([...])`) across API endpoints.
3. **CSRF Protection:** Global CSRF verification in `auth.php` matching `X-CSRF-Token` or `POST['csrf_token']` via `hash_equals()`.
4. **Session Cookie Security:**
   * `session.cookie_httponly = 1`
   * `session.cookie_samesite = 'Lax'`
   * `session.use_only_cookies = 1`
5. **Session Regeneration:** `session_regenerate_id(true)` executed upon successful user login.

### Security Vulnerabilities / Weaknesses to Disclose
> [!WARNING]
> * **Raw String Concatenation in Financial Reports:** `api/reports_api.php` constructs date filter SQL clauses using raw string interpolation (`"DATE(created_at) = '$today'"`). While variables are calculated server-side from predefined options, custom date parameters must be carefully sanitized.
> * **Default Admin Security Passwords:** Database schema includes fallback hardcoded security PINs (`admin_security_password DEFAULT '123'`).
> * **Error Output Suppression:** `api/api.php` sets `error_reporting(0)`, but custom exception handlers return detailed stack trace info on certain endpoints if uncaught.

---

## 17. DATABASE QUERY SAFETY

* **Prepared Statements:** Standard PDO prepared queries are used for all CRUD operations:
  ```php
  $stmt = $conn->prepare("SELECT * FROM patients WHERE phone = ?");
  $stmt->execute([$phone]);
  ```
* **Raw SQL Exception:** Minor instances in `reports_api.php` where date range strings are constructed dynamically before passing to PDO `$conn->query()`.

---

## 18. TRANSACTIONS AND DATA CONSISTENCY

* **Database Transactions (`beginTransaction()` / `commit()` / `rollBack()`):** Used in multi-table stock deduction and pharmacy dispensing endpoints to ensure atomic execution.
* **Consistency Risk:** In case of non-transactional legacy queries, an unhandled exception during stock deduction could leave prescription records marked as completed while inventory counts remain unchanged.

---

## 19. CONCURRENCY

* **Current Control:** Database unique indexes (`uq_inventory_name_batch`, `uniq_brand_batch`) prevent duplicate record creation.
* **Race Condition Risk:** Simultaneous medicine dispensing by multiple pharmacists could lead to stock race conditions. Recommend implementing pessimistic locking (`SELECT ... FOR UPDATE`) or atomic SQL update statements (`stock = stock - ? WHERE stock >= ?`).

---

## 20. ERROR HANDLING

* **Global Exceptions:** `set_exception_handler()` in `api/api.php` catches uncaught exceptions and outputs structured JSON:
  ```json
  {
    "success": false,
    "error": "Internal Server Error: Message details"
  }
  ```
* **Database Errors:** PDO configured with `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`.

---

## 21. FILE STORAGE / DOCUMENTS

* **Storage Engine:** Dual approach — local static assets (`static/images`, `static/uploads`) and Supabase Cloud Storage REST API (`app/Services/supabase_storage.php`).
* **Upload Process:** Diagnostic photos and bill receipts are posted via form upload, saved locally or pushed to Supabase bucket (`medical_records`), returning signed temporary access URLs (`get_supabase_signed_url()`).

---

## 22. EXTERNAL SERVICES

* **Supabase Storage API:** Pushing and retrieving diagnostic files/backup PDFs via REST headers (`apiKey`, `Authorization`).
* **Mock WhatsApp Outbox:** `mock_whatsapp_outbox/` directory used for queuing simulated customer messaging receipts.

---

## 23. REPORTS AND ANALYTICS

* Executed via `api/reports_api.php` and `GET /api/management/analytics`.
* **Metrics Calculated:**
  * Total Patient Visits (Gents vs. Lady doctor breakdown).
  * Pharmacy Revenue vs. Medicine Purchase Cost = Net Pharmacy Profit.
  * Realized Income (accounting for unpaid balances vs. cash/UPI received).
  * Wholesale Agency Supplier Purchase Totals and Outstanding Balances.

---

## 24. BACKUP AND RECOVERY

* Backup API endpoint: `GET /api/cron/backup`.
* Generates database dump PDF or JSON export, logs attempt to `whatsapp_backup_logs`, and uploads backup artifact to cloud storage.

---

## 25. DEPLOYMENT ARCHITECTURE

* **Primary Hosting:** Hostinger Shared PHP Server (`.htaccess` rewriting to `index.php`).
* **Serverless Compatibility:** Prepared for Vercel deployment via `vercel.json` routing configuration and DB cold-start table initialization (`init_db()`).

---

## 26. PERFORMANCE

* **Optimizations Implemented:**
  * Buffered query mode enabled (`PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true`).
  * Unique indexes on inventory item names and batch numbers.
* **Potential Bottlenecks:** Large table scans on `patients` table when looking up history by phone without an explicit non-unique index on `phone`.

---

## 27. AI-ASSISTED DEVELOPMENT EVIDENCE

* Presence of multi-version script files (`live_script.js`, `old_script_app.js`, `temp_script_app.js`), inline self-healing migration snippets in `api.php`, and standalone migration patch scripts (`fix_add.php`, `fix_generics_search.php`) indicate iterative AI-assisted development and rapid feature expansion.

---

## 28. CODEBASE STRUCTURE & DIRECTORY MAP

```
Hospital_project_v1/
├── api/
│   ├── api.php             # Core monolithic REST API router (7200+ lines)
│   ├── index.php           # HTTP Page Router & Controller Dispatcher
│   └── reports_api.php     # Management Financial Reports API
├── app/
│   ├── Core/
│   │   ├── session_handler.php  # Custom PDO Session Storage Engine
│   │   └── template_parser.php  # Light PHP Template Engine
│   └── Services/
│       └── supabase_storage.php # Supabase Bucket Storage API Client
├── templates/              # HTML Frontend Templates
│   ├── doctor.html         # Doctor E-Prescription Portal
│   ├── login.html          # Role Selection & Login Page
│   ├── management.html     # Admin Dashboard & Financial Portal
│   ├── monitor.html        # Waiting Hall TV Queue Display
│   ├── pharmacy.html       # Pharmacy Dispensing & Stock Portal
│   └── receptionist.html   # Reception Patient Registration Portal
├── static/
│   ├── css/style.css       # Unified Modern UI Design Stylesheet
│   └── js/                 # Modular Frontend JavaScript Libraries
├── auth.php                # Authentication & CSRF Validation Library
├── db.php                  # Database PDO Connection & Schema Migrations
├── router.php              # Local Development Server Router
└── .env                    # Environment Credentials
```

### Top 15 Most Important Files
1. `api/api.php`
2. `api/index.php`
3. `db.php`
4. `auth.php`
5. `api/reports_api.php`
6. `app/Core/session_handler.php`
7. `app/Services/supabase_storage.php`
8. `templates/receptionist.html`
9. `templates/doctor.html`
10. `templates/pharmacy.html`
11. `templates/management.html`
12. `templates/monitor.html`
13. `live_script.js`
14. `static/js/inventory.js`
15. `static/css/style.css`

---

## 29. DESIGN DECISIONS

1. **Monolithic API File (`api/api.php`):** Centralizes all endpoint routes in one file for single-file deployment simplicity, though refactoring into modular controllers would improve long-term maintainability.
2. **Database Session Handler:** Selected to prevent session loss across serverless container restarts or load-balanced nodes.
3. **JSON Storage in Text Column (`prescriptions.medicines`):** Avoids complex junction tables for line items, enabling flexible prescription schema changes.

---

## 30. SCALABILITY ANALYSIS

* **10x Growth:** Current architecture handles easily with MySQL index tuning on `patients(phone)` and `prescriptions(patient_id)`.
* **100x Growth / Multi-Branch:** Require splitting `api.php` into modular controllers, migrating session cache to Redis, implementing read-replicas for `reports_api.php`, and enforcing multi-tenant `clinic_id` columns across all tables.

---

## 31. TESTING

* **Automated Tests:** Lightweight custom script tests present in repository (`test_api.php`, `test_db.php`, `scripts/test_pdo.php`).
* **Missing Tests:** Automated PHPUnit integration test suite and Cypress/Selenium E2E browser tests.

---

## 32. GIT & CODE ORGANIZATION

* Single-repository structure with clear separation between API handlers (`api/`), business services (`app/Services/`), DB config (`db.php`), and UI templates (`templates/`).

---

## 33. MOST IMPORTANT TECHNICAL CHALLENGES

1. **Dual Inventory Stock Sync:** Keeping pharmacy retail inventory and wholesale agency inventory synchronized across batch updates.
2. **Tablet-to-Strip Stock Conversions:** Accurate math for fractional dispensing (individual tablets vs. full strips).
3. **Custom PDO Session Persistence:** Ensuring zero lock contention in custom session handlers.
4. **Token Generation Under High Reception Concurrency:** Preventing duplicate token collision across doctor queues.
5. **Real-time Waiting Display:** Low-latency queue polling for TV monitors without overloading server threads.
6. **Backward Password Migration:** Seamless verification for both legacy plaintext and modern Bcrypt passwords.
7. **Cross-Payment Realized Profit Accounting:** Calculating exact profit margin when bills are paid via partial split payments.
8. **Dynamic CSRF Protection on Stateless APIs:** Enforcing security headers across serverless environments.
9. **Generic-to-Brand Medicine Synthesizing:** Auto-creating placeholder inventory records when doctors prescribe generic names.
10. **Cloud PDF / Document Signing:** Securely serving medical images via signed Supabase URLs.

---

## 34. SAMPLE INTERVIEW QUESTIONS & DEFENSIVE ANSWERS

### A. Architecture & PHP
* **Q: How does routing work in this system?**  
  *A:* `api/index.php` acts as the front controller matching HTML pages and proxying `/api/*` URI paths directly into `api/api.php`.
* **Q: Why was a custom PDO session handler implemented?**  
  *A:* Standard PHP file sessions fail in serverless or multi-node hosting (like Vercel). Persisting session states to the `sessions` table guarantees state persistence across stateless HTTP invocations.

### B. Database & Security
* **Q: How are SQL injection vulnerabilities prevented?**  
  *A:* All core application queries use PDO prepared statements with parameterized input bindings (`$stmt->prepare()` / `$stmt->execute()`).
* **Q: How is CSRF handled?**  
  *A:* Session initialization generates a crypto-random 32-byte token. State-changing HTTP methods verify `HTTP_X_CSRF_TOKEN` using timing-safe `hash_equals()`.

---

## 35. INTERVIEW TRAPS / CLAIMS YOU MUST NOT MAKE

> [!CAUTION]
> 1. **Do NOT claim you used Laravel or Symfony:** The project is built using **Native PHP** with a custom PDO wrapper and custom template parser.
> 2. **Do NOT claim full REST compliance:** Endpoints use RPC-like patterns (e.g., `POST /api/register_patient`, `POST /api/add_medicines`) rather than pure RESTful resource URLs.
> 3. **Do NOT claim complete automated test coverage:** Testing is done via custom one-off test scripts (`test_api.php`), not PHPUnit or CI pipelines.
> 4. **Do NOT claim webSockets are used for the TV queue display:** Queue updates rely on periodic client-side AJAX polling.

---

## 36. FINAL PROJECT CHEAT SHEET

* **Problem:** End-to-end outpatient clinic automation (Queue, Consult, Pharmacy, Billing, Staff).
* **Tech Stack:** PHP 8.x, PDO, MySQL, Vanilla JavaScript, Bootstrap CSS, Supabase Storage, FPDF.
* **Roles:** Receptionist, Doctor (Gents/Lady), Pharmacist, Management, TV Monitor.
* **Authentication:** Session cookie with custom PDO `sessions` table handler + Bcrypt password hashing.
* **Authorization:** Role-Based Access Control (RBAC) enforced via `enforce_api_auth()`.
* **Queue Tokens:** Gender/Doctor prefixed sequential tokens (`G-001`, `L-001`).
* **Stock Conversion:** Automatic strip-to-tablet multiplication and unit stock deduction.
* **Deployment:** Hostinger Shared PHP / Vercel Serverless ready.

### 20 Critical Things to Remember
1. `api/api.php` is the central API dispatcher containing 70+ endpoints.
2. `db.php` manages PDO connection pooling and self-healing schema migrations.
3. `auth.php` enforces global CSRF protection via `hash_equals()`.
4. `sessions` table stores session data via `DatabaseSessionHandler`.
5. Doctor types are split into `Gents` (Dr. Mohamed Rasith Sir) and `Lady` (Dr. Jannathul Basheera Mam).
6. Patient queue status flows from `waiting` -> `completed` -> `dispensed`.
7. `prescriptions.medicines` stores prescribed line items as a JSON text blob.
8. Tablet stock is auto-converted from strips based on `tablets_per_strip`.
9. `sync_stock_item()` synchronizes `inventory` and `agency_items`.
10. `reports_api.php` handles financial analytics for the `management` role.
11. Management role (`management`) bypasses all role-permission checks.
12. `generate_token()` inspects today's patient records to generate daily tokens.
13. Diagnostic photos are stored in Supabase cloud buckets via REST API.
14. FPDF library generates billing receipts and backup reports.
15. Cross-visit lookup uses `phone` as the primary lookup handle.
16. Direct OTC sales are stored separately in the `direct_sales` table.
17. Staff salary disbursements are logged in `staff_payments`.
18. `router.php` serves local static assets during development.
19. Realized profit formula accounts for uncollected balance amounts.
20. Password verification handles both Bcrypt hashes and legacy plaintext entries.
