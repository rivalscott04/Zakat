Oke, gue copas ulang **clean version tanpa noise, tanpa penjelasan tambahan, langsung PRD siap pakai**.

---

# PRD MODULE 00 — CORE & FOUNDATION (FINAL ATOMIC SPLIT)

---

# PRD 00A — INTRO & PRINCIPLES

## 1. Project Info

Project: ZETRA
Module: Core & Foundation
Code: SYS
Version: 0.1.0
Status: Implemented (Backend)
Stack: React + TypeScript + Laravel 13 + PostgreSQL
Architecture: Modular Monolith / API First
License: AGPL-3.0

---

## 2. Purpose

Core & Foundation adalah lapisan paling dasar sistem.

Tidak mengandung business logic zakat.

Fungsi:

* Standarisasi sistem
* Identitas data
* Auditability
* Traceability
* API consistency
* Security baseline
* Financial integrity foundation

---

## 3. Core Principles

### 3.1 Transparency by Design

Data publik harus bisa dipublikasikan secara terstruktur.

### 3.2 Auditability by Design

Semua aksi harus bisa ditelusuri:

Actor → Action → Entity → Time → Context → Before → After

### 3.3 Privacy by Design

Data pribadi tidak boleh bocor dalam sistem transparansi.

### 3.4 Financial Integrity

Data keuangan tidak boleh diubah langsung setelah final.

Perbaikan hanya via:

* reversal
* adjustment
* correction

### 3.5 Sharia Rule Driven

Aturan zakat tidak hardcoded, harus berbasis rule/config system.

### 3.6 API First

Backend adalah source of truth.

Frontend tidak boleh:

* hitung zakat
* validasi transaksi
* menentukan status finansial

---

# PRD 00B — ARCHITECTURE & SCOPE

## 4. Scope

### In Scope

* ULID identity
* Business code system
* Numbering system
* Status lifecycle
* Money system
* Time system
* Multi-tenancy
* Audit system
* API standard
* Security baseline
* File metadata
* Event system
* Notification system
* Reference data

### Out of Scope

* Zakat calculation
* Payment gateway
* Distribution logic
* Accounting system
* Muzaki/Mustahik logic
* Bank reconciliation
* Social program logic
* Public reporting logic

---

## 5. Architecture

Frontend → Backend → Database

React + TS
↓
Laravel 13 API
↓
PostgreSQL

Supporting:

* Redis (cache + queue)
* S3/MinIO (storage)

---

## 6. Tech Stack

Backend:

* PHP 8.3+
* Laravel 13
* Sanctum
* Queue
* Events

Frontend:

* React
* TypeScript
* Vite

Infra:

* PostgreSQL
* Redis
* Docker
* Nginx

CI/CD:

* GitHub Actions

---

## 7. Repository Structure

zakat-os/
├── frontend/
├── backend/
├── docs/
│   ├── prd/
│   ├── architecture/
│   ├── api/
│   ├── database/
│   └── sharia/
├── docker/
├── scripts/
├── .github/
├── README.md
├── LICENSE
└── docker-compose.yml

---

# PRD 00C — DATA FOUNDATION

## 8. Entity Identity (ULID)

Primary Key: ULID

Contoh:
01K3ABC123XYZ4567890

Dipakai untuk:

* PK
* FK
* internal reference

---

## 9. Business Code

Format: AAA

Contoh:
ZML, ZFT, ZPG, MZK, MST, PRG, PAY

Rules:

* uppercase
* max 5 char
* immutable
* registered in registry

---

## 10. Code Registry

Fields:

* id
* code
* name
* entity_type
* module
* is_active
* timestamps

---

## 11. Business Number

Format:
{CODE}{YEAR}{SEQUENCE}

Contoh:
ZML2026000001

Rules:

* immutable
* unique
* never reused
* not primary key
* cancellation does NOT delete number

---

## 12. Money System

Type:
NUMERIC(20,2)

Dilarang:
FLOAT / DOUBLE

---

## 13. Currency

ISO 4217
Default: IDR

---

## 14. Time System

Storage: UTC
Default timezone: Asia/Makassar
Format: ISO 8601

---

## 15. Status Lifecycle

Entity:
draft / active / inactive / archived

Transaction:
draft → pending → verified → posted → completed → cancelled → reversed

---

# PRD 00D — API & SECURITY FOUNDATION

## 16. API Standard

Base URL:
/api/v1

---

## 17. Response Format

Success:
{
"data": {},
"meta": {}
}

Error:
{
"message": "",
"errors": {},
"code": "",
"request_id": ""
}

---

## 18. HTTP Status

200, 201, 400, 401, 403, 404, 409, 422, 500

---

## 19. Request ID

Wajib untuk tracing semua request.

---

## 20. Authentication

Laravel Sanctum

---

## 21. Authorization

RBAC

Format:
module.resource.action

---

## 22. Multi-Tenancy

Shared DB + organization_id

---

## 23. Data Isolation

Wajib enforced di backend.

---

## 24. Audit System

audit_logs:

* actor
* action
* entity
* before
* after
* ip
* user_agent

---

## 25. Security Baseline

* HTTPS
* CSRF protection
* Rate limiting
* Password hashing
* Secret masking
* Secure headers

---

## 26. File Upload

* ULID filename
* MIME validation
* size limit
* SHA-256 checksum

---

## 27. Privacy Rule

Dilarang expose:

* NIK
* rekening
* data pribadi sensitif

---

# PRD 00E — ENGINE & OPS RULES

## 28. Events

PaymentCreated
PaymentVerified
DistributionApproved

---

## 29. Queue

report, export, notification, import

---

## 30. Cache Rule

Dilarang cache data finansial sebagai source of truth

---

## 31. Testing

* Unit
* Feature
* Integration

---

## 32. Concurrency Control

* DB lock
* unique constraint
* idempotency key

---

## 33. Idempotency

Wajib untuk:

* payment
* transaction
* posting

---

## 34. Transaction Boundary

Semua financial operation harus atomic

---

## 35. Logging

request_id
user_id
org_id
context

---

## 36. Error Codes

VALIDATION_ERROR
UNAUTHORIZED
FORBIDDEN
NOT_FOUND
DUPLICATE_RESOURCE

---

## 37. Acceptance Criteria

* [x] ULID aktif
* [x] numbering jalan
* [x] audit jalan
* [x] API standard
* [x] money safe
* [x] multi-tenant safe
* [x] auth jalan
* [x] logging jalan

---

## 38. Definition of Done

* [x] test pass
* [x] migration clean
* [x] seeder ready
* [x] API usable
* [x] no secret leak
* [x] audit traceable
* [x] concurrency safe

---

## 39. Core System Principle

DB = truth
Backend = logic
Frontend = UI
Ledger = financial truth
Audit = history truth

---

# END OF PRD 00 (ATOMIC VERSION)
