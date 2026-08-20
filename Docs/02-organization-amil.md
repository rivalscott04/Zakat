# PRD MODULE 02 — ORGANIZATION & AMIL

Project: Zakat OS
Module: Organization & Amil
Module Code: ORG
Version: 0.1.0
Status: Draft
Dependencies:

* PRD 00 — Core & Foundation
* PRD 01 — Authentication & Authorization

---

# PRD 02A — INTRO & ORGANIZATION MODEL

## 1. Purpose

Modul Organization & Amil bertanggung jawab untuk mengelola struktur organisasi yang menggunakan Zakat OS.

Modul ini menjadi foundation untuk:

* Data isolation
* Organization ownership
* User membership
* Unit kerja
* Cabang
* Amil identity
* Organizational context
* Operational structure

Setiap data operasional utama harus dapat dikaitkan dengan organisasi yang mengelolanya.

---

## 2. Core Principle

Struktur dasar:

```text
Platform
    ↓
Organization
    ↓
Organization Unit
    ↓
Members / Amil
```

Zakat OS harus mendukung penggunaan oleh:

* Lembaga pengelola zakat
* Organisasi zakat
* Unit pengumpul zakat
* Cabang
* Unit operasional
* Organisasi independen

Versi awal tidak boleh mengunci sistem hanya untuk satu bentuk organisasi.

---

## 3. Organization Model

Entity utama:

```text
organizations
```

Setiap organization merupakan tenant utama dalam sistem.

Contoh:

```text
Organization
├── Headquarters
├── Branch
├── Regional Unit
└── Operational Unit
```

Struktur organisasi harus fleksibel.

---

## 4. Organization Identity

Setiap organization menggunakan:

```text
ULID
```

sebagai primary key.

Business number:

```text
ORG{YEAR}{SEQUENCE}
```

Contoh:

```text
ORG2026000001
```

Organization code dapat digunakan sebagai short identifier.

Contoh:

```text
BAZNASNTB
LAZABC
ZAKATORG
```

Organization code:

* unique;
* uppercase;
* alphanumeric;
* immutable setelah digunakan;
* tidak digunakan sebagai primary key.

---

## 5. Organization Entity

Entity:

```text
organizations
```

Minimum fields:

```text
id
business_number
code
name
legal_name
organization_type
status

email
phone
website

logo_document_id

currency
timezone
locale

parent_id

created_at
updated_at
deleted_at
```

---

## 6. Organization Type

Initial type:

```text
platform
organization
branch
unit
upz
```

Definisi:

### platform

Level pengelola sistem.

### organization

Lembaga utama pengguna Zakat OS.

### branch

Cabang dari organisasi.

### unit

Unit operasional.

### upz

Unit Pengumpul Zakat atau unit dengan fungsi pengumpulan.

Type harus dapat dikembangkan melalui reference data apabila dibutuhkan.

---

## 7. Organization Status

Status:

```text
draft
active
inactive
suspended
archived
```

### draft

Organisasi sedang dalam proses setup.

### active

Organisasi aktif menggunakan sistem.

### inactive

Organisasi sementara tidak aktif.

### suspended

Akses organisasi dihentikan.

### archived

Organisasi tidak lagi aktif namun historical data tetap disimpan.

---

# PRD 02B — ORGANIZATION STRUCTURE

## 8. Hierarchical Structure

Organization dapat memiliki parent organization.

Contoh:

```text
Organization
│
├── Branch A
│   ├── Unit 1
│   └── Unit 2
│
└── Branch B
    ├── Unit 1
    └── Unit 2
```

Field:

```text
parent_id
```

Parent organization dapat bernilai:

```text
NULL
```

untuk root organization.

---

## 9. Hierarchy Rules

Sistem harus:

* mencegah circular parent relationship;
* mencegah organization menjadi parent dirinya sendiri;
* menjaga organization tree tetap valid;
* mencegah akses child organization ke parent data tanpa permission;
* mendukung data scope berdasarkan hierarchy apabila diperlukan.

Contoh circular relationship yang dilarang:

```text
Organization A
↓
Organization B
↓
Organization C
↓
Organization A
```

---

## 10. Organization Scope

User dapat memiliki akses pada:

```text
Single Organization
```

atau:

```text
Multiple Organizations
```

Contoh:

```text
User A
├── Organization A
└── Branch B
```

Akses tidak otomatis berarti akses penuh.

Permission tetap berlaku.

---

# PRD 02C — ORGANIZATION MEMBERSHIP

## 11. Membership

Relationship:

```text
User
↓
Organization Membership
↓
Organization
```

Entity:

```text
organization_members
```

Fields:

```text
id
organization_id
user_id
member_type
status
joined_at
left_at
created_at
updated_at
```

---

## 12. Member Type

Initial values:

```text
employee
amil
volunteer
auditor
external
```

Member type menjelaskan hubungan operasional dengan organisasi.

Member type bukan role.

Contoh:

```text
member_type = amil
role = FINANCE
```

---

## 13. Membership Status

Status:

```text
pending
active
inactive
terminated
```

User hanya dapat mengakses organization jika:

```text
membership.status = active
```

dan memiliki role/permission yang sesuai.

---

## 14. Multiple Membership

Satu user dapat menjadi anggota beberapa organization.

Contoh:

```text
User
├── Organization A
│   └── AMIL
│
└── Organization B
    └── AUDITOR
```

Role dan permission harus mengikuti context organization aktif.

---

## 15. Active Organization Context

Authenticated user memiliki active organization context.

Frontend dapat memilih organization apabila user memiliki akses lebih dari satu.

Flow:

```text
Login
↓
Get Available Organizations
↓
Select Active Organization
↓
Store Active Context
↓
All Requests Scoped
```

Backend tetap wajib memvalidasi membership.

Frontend tidak boleh menentukan organization scope secara bebas.

---

# PRD 02D — AMIL MANAGEMENT

## 16. Purpose of Amil Management

Modul Amil digunakan untuk mencatat personel yang memiliki peran operasional dalam pengelolaan zakat.

Amil dapat:

* menerima tugas;
* mengelola pengumpulan;
* melakukan verifikasi;
* melakukan pendataan;
* melakukan penyaluran;
* mengelola administrasi;
* menjalankan fungsi operasional lainnya.

Status sebagai Amil tidak otomatis memberikan permission sistem.

Authorization tetap ditentukan oleh Role & Permission.

---

## 17. Amil Entity

Entity:

```text
amils
```

Fields minimum:

```text
id
organization_id
user_id
business_number

name
employee_number

email
phone

status
joined_at
ended_at

created_at
updated_at
deleted_at
```

Business number format:

```text
AML{YEAR}{SEQUENCE}
```

Contoh:

```text
AML2026000001
```

---

## 18. Amil Identity Rule

Amil dapat memiliki user account atau belum memiliki user account.

Contoh:

```text
Amil
├── Linked User
│
└── No User Account
```

Hal ini memungkinkan organisasi mencatat personel tanpa memberikan akses sistem.

Jika amil memiliki user account:

```text
amils.user_id
```

diisi.

Jika belum:

```text
user_id = NULL
```

---

## 19. Amil Status

Status:

```text
active
inactive
suspended
ended
```

### active

Amil masih aktif dalam organisasi.

### inactive

Sementara tidak aktif.

### suspended

Status ditangguhkan.

### ended

Hubungan operasional telah berakhir.

Historical transaction tetap mempertahankan referensi terhadap Amil.

---

## 20. Amil Assignment

Amil dapat diberikan assignment.

Entity:

```text
amil_assignments
```

Contoh assignment:

```text
Collection Officer
Verifier
Finance Officer
Distribution Officer
Program Officer
Field Officer
```

Fields:

```text
id
amil_id
organization_id
assignment_type
started_at
ended_at
status
created_at
updated_at
```

Assignment bukan authorization.

Assignment menjelaskan tanggung jawab operasional.

---

# PRD 02E — ORGANIZATION PROFILE

## 21. Organization Profile

Organization dapat menyimpan:

```text
name
legal_name
description
logo
email
phone
website
```

Alamat dapat dikelola menggunakan entity terpisah.

---

## 22. Organization Address

Entity:

```text
organization_addresses
```

Fields:

```text
id
organization_id

label
address_line_1
address_line_2

country_code
province_code
city_code
district_code
village_code
postal_code

latitude
longitude

is_primary

created_at
updated_at
```

Satu organization dapat memiliki beberapa alamat.

Hanya satu alamat utama:

```text
is_primary = true
```

---

## 23. Organization Contact

Entity:

```text
organization_contacts
```

Fields:

```text
id
organization_id
type
label
value
is_primary
created_at
updated_at
```

Contact type:

```text
email
phone
whatsapp
website
```

---

# PRD 02F — ORGANIZATION SETTINGS

## 24. Organization Configuration

Organization memiliki konfigurasi sendiri.

Contoh:

```text
currency
timezone
locale
date_format
number_format
```

Konfigurasi organization tidak boleh mengubah global system configuration.

Priority:

```text
System Default
↓
Organization Setting
↓
User Preference
```

---

## 25. Organization Branding

Organization dapat mengatur:

* logo;
* display name;
* primary color;
* document footer;
* receipt identity.

Branding tidak boleh mengubah system security.

---

# PRD 02G — ORGANIZATION ACCESS

## 26. Organization Switching

Jika user memiliki multiple membership, user dapat berpindah active organization.

Endpoint:

```text
GET /api/v1/organizations/available

POST /api/v1/auth/switch-organization
```

Request:

```json
{
  "organization_id": "01K..."
}
```

Backend wajib memastikan user memiliki membership aktif.

---

## 27. Organization Access Rules

User dapat mengakses data jika:

1. User authenticated.
2. Organization aktif.
3. Membership aktif.
4. User memiliki role/permission.
5. Resource berada dalam organization scope yang valid.

Semua kondisi wajib dipenuhi.

---

## 28. Suspended Organization

Jika organization:

```text
status = suspended
```

maka:

* user organization tidak dapat melakukan transaksi baru;
* administrator platform tetap dapat mengakses untuk investigasi;
* historical data tetap tersedia sesuai permission.

---

# PRD 02H — API SPECIFICATION

## 29. Organization Endpoints

```text
GET    /api/v1/organizations
POST   /api/v1/organizations

GET    /api/v1/organizations/{id}
PATCH  /api/v1/organizations/{id}

POST   /api/v1/organizations/{id}/activate
POST   /api/v1/organizations/{id}/deactivate
POST   /api/v1/organizations/{id}/suspend
```

---

## 30. Organization Hierarchy Endpoints

```text
GET /api/v1/organizations/{id}/children

POST /api/v1/organizations/{id}/children
```

Parent update:

```text
PATCH /api/v1/organizations/{id}
```

Field:

```text
parent_id
```

Hierarchy validation wajib dilakukan backend.

---

## 31. Membership Endpoints

```text
GET    /api/v1/organizations/{id}/members
POST   /api/v1/organizations/{id}/members

PATCH  /api/v1/organization-members/{id}

POST   /api/v1/organization-members/{id}/activate
POST   /api/v1/organization-members/{id}/deactivate
POST   /api/v1/organization-members/{id}/terminate
```

---

## 32. Amil Endpoints

```text
GET    /api/v1/amils
POST   /api/v1/amils

GET    /api/v1/amils/{id}
PATCH  /api/v1/amils/{id}

POST   /api/v1/amils/{id}/activate
POST   /api/v1/amils/{id}/deactivate
POST   /api/v1/amils/{id}/suspend
POST   /api/v1/amils/{id}/end
```

---

## 33. Amil Assignment Endpoints

```text
GET    /api/v1/amils/{id}/assignments

POST   /api/v1/amils/{id}/assignments

PATCH  /api/v1/amil-assignments/{id}

POST /api/v1/amil-assignments/{id}/end
```

---

# PRD 02I — VALIDATION & BUSINESS RULES

## 34. Organization Validation

Organization:

* name required;
* code unique;
* organization_type required;
* timezone valid;
* currency valid ISO 4217.

---

## 35. Organization Code Rules

Organization code:

* uppercase;
* alphanumeric;
* maximum 20 characters;
* unique;
* immutable setelah organization aktif.

Contoh valid:

```text
BAZNASNTB
LAZDOMPET
UPZMATARAM
```

---

## 36. Membership Rules

1. User tidak boleh memiliki membership duplikat pada organization yang sama.
2. Membership terminated tidak dapat digunakan untuk akses.
3. Membership inactive tidak dapat digunakan untuk akses.
4. User dapat memiliki multiple organization membership.
5. Role dapat berbeda pada setiap organization.

---

## 37. Amil Rules

1. Amil wajib terkait dengan organization.
2. Amil dapat memiliki atau tidak memiliki user account.
3. Satu user tidak boleh memiliki lebih dari satu active Amil record pada organization yang sama.
4. Amil ended tidak dapat menerima assignment baru.
5. Historical assignment tidak boleh dihapus secara permanen.
6. Status Amil tidak otomatis menentukan system permission.

---

## 38. Hierarchy Rules

1. Organization tidak boleh menjadi parent dirinya sendiri.
2. Circular relationship dilarang.
3. Parent organization harus berada dalam platform scope yang valid.
4. Organization archived tidak dapat menerima child baru.
5. Organization suspended tidak dapat membuat transaksi operasional baru.

---

# PRD 02J — AUDIT

## 39. Audit Events

Minimal:

```text
organization_created
organization_updated
organization_activated
organization_deactivated
organization_suspended

organization_member_added
organization_member_updated
organization_member_activated
organization_member_deactivated
organization_member_terminated

amil_created
amil_updated
amil_activated
amil_deactivated
amil_suspended
amil_ended

amil_assignment_created
amil_assignment_updated
amil_assignment_ended

organization_switched
```

---

## 40. Audit Requirements

Setiap perubahan harus mencatat:

```text
actor
action
entity
before
after
organization
request_id
ip_address
user_agent
timestamp
```

Data sensitif harus dimasking.

---

# PRD 02K — UI REQUIREMENTS

## 41. Organization Management

Halaman:

```text
Organization List
Organization Detail
Organization Form
Organization Settings
Organization Members
```

Menggunakan Velzon table, form, modal, dan card components.

---

## 42. Organization Detail

Menampilkan:

* Identity
* Status
* Parent Organization
* Child Organizations
* Members
* Amil
* Contact
* Address
* Settings
* Audit Summary

---

## 43. Organization Switcher

Jika user memiliki lebih dari satu organization:

```text
Header
↓
Organization Switcher
↓
Available Organizations
```

Switcher harus menampilkan:

* Organization name
* Organization code
* Current organization indicator

---

## 44. Amil Management Page

Menampilkan:

* Business Number
* Name
* Organization
* User Account Status
* Amil Status
* Active Assignment

Action:

* Create
* Edit
* Activate
* Deactivate
* Suspend
* End Assignment

---

# PRD 02L — ACCEPTANCE CRITERIA

* [ ] Organization dapat dibuat.
* [ ] Organization memiliki ULID.
* [ ] Organization memiliki business number.
* [ ] Organization code unique.
* [ ] Organization hierarchy berfungsi.
* [ ] Circular hierarchy dicegah.
* [ ] Multiple organization membership berfungsi.
* [ ] Active organization context berfungsi.
* [ ] Backend memvalidasi organization access.
* [ ] Organization switcher berfungsi.
* [ ] Amil dapat dibuat tanpa user account.
* [ ] Amil dapat dikaitkan dengan user.
* [ ] Amil assignment berfungsi.
* [ ] Organization suspended tidak dapat membuat transaksi baru.
* [ ] Audit trail tersedia.
* [ ] Historical Amil data tetap tersedia.
* [ ] Automated test tersedia.

---

# PRD 02M — DEFINITION OF DONE

Modul Organization & Amil dianggap selesai apabila:

1. Organization dapat dikelola.
2. Organization hierarchy valid.
3. Multi-organization membership berfungsi.
4. Organization context diterapkan pada API.
5. Data isolation berjalan.
6. Organization switching berjalan.
7. Amil dapat dikelola.
8. Amil dapat dikaitkan dengan user.
9. Assignment Amil dapat dikelola.
10. Status lifecycle berfungsi.
11. Audit trail tersedia.
12. Authorization telah diuji.
13. Automated test berhasil.
14. Modul siap digunakan oleh modul Muzaki, Collection, Distribution, Accounting, dan modul lainnya.

---

# END OF PRD MODULE 02 — ORGANIZATION & AMIL
