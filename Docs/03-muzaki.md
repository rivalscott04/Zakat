# PRD MODULE 03 — MUZAKI MANAGEMENT

Project: ZETRA
Module: Muzaki Management
Module Code: MZK
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md

---

# PRD 03A — OVERVIEW

## 1. Purpose

Modul Muzaki Management bertanggung jawab untuk mengelola data pihak yang menunaikan zakat melalui organisasi.

Selain zakat, entity Muzaki dapat digunakan sebagai contributor untuk:

- Zakat
- Infak
- Sedekah
- Dana kontribusi lain yang didukung sistem

Modul ini menjadi sumber utama data contributor sebelum masuk ke proses:

Muzaki
↓
Zakat / Contribution
↓
Calculation
↓
Collection
↓
Fund Management
↓
Accounting
↓
Reporting
↓
Transparency

Modul ini hanya mengelola data pihak yang melakukan kontribusi.

Transaksi pembayaran dikelola oleh:

06-collection.md

---

## 2. Goals

Modul harus mampu:

1. Mendaftarkan Muzaki.
2. Mendukung Muzaki individu.
3. Mendukung Muzaki keluarga.
4. Mendukung Muzaki perusahaan.
5. Mendukung Muzaki organisasi.
6. Mendukung Muzaki institusi.
7. Mengelola identitas.
8. Mengelola kontak.
9. Mengelola alamat.
10. Mengelola data keluarga.
11. Mengelola preferensi komunikasi.
12. Mengelola preferensi privasi.
13. Mendeteksi data duplikat.
14. Melakukan merge data Muzaki.
15. Menampilkan ringkasan kontribusi.
16. Menampilkan riwayat kontribusi.
17. Mendukung tagging dan segmentasi.
18. Menjaga historical data.
19. Menerapkan organization isolation.
20. Melindungi data pribadi dan sensitif.

---

# PRD 03B — CORE CONCEPT

## 3. Muzaki Definition

Muzaki adalah pihak yang menunaikan zakat.

Dalam sistem, entity Muzaki dapat menjadi contributor untuk berbagai jenis dana yang didukung.

Jenis entitas:

- INDIVIDUAL
- FAMILY
- COMPANY
- ORGANIZATION
- INSTITUTION

---

## 4. Separation Principle

Data Muzaki harus dipisahkan dari data transaksi.

Contoh:

Muzaki:

Ahmad
MZK2026000001

dapat memiliki:

PAY2026000001
PAY2026000002
PAY2026000003

Perubahan profil Ahmad tidak boleh menghilangkan atau merusak historical transaction.

---

# PRD 03C — MUZAKI CORE ENTITY

## 5. Core Entity

Entity:

muzakis

Primary key:

ULID

Business Number:

MZK{YEAR}{SEQUENCE}

Contoh:

MZK2026000001
MZK2026000002
MZK2026000003

Rules:

- unique
- immutable
- human readable
- tidak menjadi primary key
- tidak boleh berubah
- tidak boleh digunakan kembali

---

## 6. Core Fields

Minimal:

id

organization_id

business_number

muzaki_type

status

display_name

registration_source

registered_at

created_at

updated_at

deleted_at

---

## 7. Muzaki Type

Initial values:

INDIVIDUAL
FAMILY
COMPANY
ORGANIZATION
INSTITUTION

---

## 8. Muzaki Status

Initial values:

LEAD
ACTIVE
INACTIVE
BLOCKED
ARCHIVED

### LEAD

Calon Muzaki yang belum menjadi contributor aktif.

### ACTIVE

Muzaki aktif.

### INACTIVE

Muzaki tidak aktif.

### BLOCKED

Muzaki dibatasi karena alasan tertentu.

### ARCHIVED

Muzaki tidak aktif tetapi historical data dipertahankan.

---

# PRD 03D — INDIVIDUAL PROFILE

## 9. Entity

muzaki_individual_profiles

Fields:

id

muzaki_id

full_name

title_prefix

title_suffix

gender

birth_date

nationality

occupation

education_level

created_at

updated_at

---

## 10. Individual Rules

Untuk type:

INDIVIDUAL

field berikut wajib:

full_name

Field berikut opsional:

- title_prefix
- title_suffix
- gender
- birth_date
- nationality
- occupation
- education_level

---

# PRD 03E — ORGANIZATION PROFILE

## 11. Entity

muzaki_organization_profiles

Fields:

id

muzaki_id

legal_name

registration_number

industry

representative_name

representative_position

created_at

updated_at

---

## 12. Organization Types

Organization profile dapat digunakan untuk:

COMPANY

ORGANIZATION

INSTITUTION

---

## 13. Representative

Entity:

muzaki_representatives

Fields:

id

muzaki_id

name

position

email

phone

is_primary

created_at

updated_at

Satu Muzaki organisasi dapat memiliki beberapa representative.

Maksimal satu representative aktif dapat ditandai sebagai primary untuk kebutuhan komunikasi utama.

---

# PRD 03F — IDENTITY MANAGEMENT

## 14. Entity

muzaki_identities

Fields:

id

muzaki_id

identity_type

identity_number_encrypted

identity_number_hash

issued_country

verification_status

verified_at

verified_by

created_at

updated_at

---

## 15. Identity Type

Initial values:

NIK
PASSPORT
TAX_ID
EMPLOYEE_ID
OTHER

---

## 16. Identity Security

Identity number harus:

- dienkripsi jika tergolong data sensitif;
- memiliki hash untuk duplicate detection apabila diperlukan;
- tidak disimpan plaintext pada log;
- tidak ditampilkan penuh pada response default;
- dibatasi berdasarkan permission.

Contoh display:

3271********1234

---

# PRD 03G — CONTACT MANAGEMENT

## 17. Entity

muzaki_contacts

Fields:

id

muzaki_id

contact_type

label

value_encrypted

is_primary

verification_status

verified_at

created_at

updated_at

deleted_at

---

## 18. Contact Type

Initial values:

EMAIL
PHONE
WHATSAPP
OTHER

---

## 19. Contact Verification

Status:

UNVERIFIED
PENDING
VERIFIED
INVALID

Verification dapat dilakukan menggunakan:

- Email verification
- OTP
- Manual verification

---

## 20. Contact Rules

Satu Muzaki dapat memiliki banyak contact.

Untuk setiap contact type, maksimal satu contact aktif dapat menjadi primary.

Contact yang sudah digunakan pada historical communication tidak boleh dihapus secara permanen.

---

# PRD 03H — ADDRESS MANAGEMENT

## 21. Entity

muzaki_addresses

Fields:

id

muzaki_id

address_type

label

address_line_1

address_line_2

country_id

province_id

city_id

district_id

village_id

postal_code

latitude

longitude

is_primary

created_at

updated_at

deleted_at

---

## 22. Address Type

Initial values:

HOME
WORK
OFFICE
OTHER

---

## 23. Address Rules

Satu Muzaki dapat memiliki banyak alamat.

Maksimal satu alamat aktif dapat menjadi primary.

Wilayah harus menggunakan reference yang ditetapkan pada:

03-master/reference layer

Jika reference wilayah kemudian ditempatkan pada modul lain, implementasi wajib tetap menggunakan satu sumber data yang konsisten.

---

# PRD 03I — FAMILY MANAGEMENT

## 24. Purpose

Muzaki dengan type:

FAMILY

dapat memiliki anggota keluarga.

---

## 25. Entity

muzaki_family_members

Fields:

id

muzaki_id

name

relationship

birth_date

gender

is_head

created_at

updated_at

---

## 26. Relationship Type

Initial values:

SELF
SPOUSE
CHILD
PARENT
SIBLING
OTHER

---

## 27. Family Rules

1. Satu family Muzaki dapat memiliki banyak family member.
2. Satu family dapat memiliki maksimal satu head.
3. Family member tidak wajib memiliki user account.
4. Family member tidak otomatis menjadi Muzaki terpisah.
5. Jika anggota keluarga kemudian didaftarkan sebagai Muzaki sendiri, historical relation harus tetap dapat dipertahankan.

---

# PRD 03J — REGISTRATION

## 28. Registration Source

Initial values:

MANUAL
WEB
MOBILE
IMPORT
API
EVENT
OTHER

Field:

registration_source

---

## 29. Registration Flow

Create Muzaki

↓

Select Type

↓

Input Basic Profile

↓

Duplicate Detection

↓

Create Business Number

↓

Create Profile

↓

Create Contact

↓

Create Address

↓

Set Preference

↓

Activate

---

## 30. Registration Rule

Business number dibuat hanya setelah basic validation berhasil.

Jika proses create gagal secara transactional:

- Muzaki tidak dibuat;
- business number tidak boleh digunakan kembali jika generator sudah mengalokasikannya;
- transaksi database harus rollback sesuai implementation.

---

# PRD 03K — DUPLICATE DETECTION

## 31. Purpose

Sistem harus mendeteksi kemungkinan data Muzaki duplikat.

Parameter yang dapat digunakan:

- Identity hash
- Email
- Phone
- Name similarity
- Registration number

---

## 32. Duplicate Detection Result

Initial result:

NO_MATCH

POSSIBLE_DUPLICATE

HIGH_CONFIDENCE_DUPLICATE

---

## 33. Duplicate Rule

Sistem tidak boleh otomatis menghapus data hanya karena dianggap duplicate.

User dapat memilih:

VIEW_CANDIDATE

KEEP_SEPARATE

REQUEST_MERGE

---

## 34. Duplicate Review

Entity:

muzaki_duplicate_reviews

Fields:

id

organization_id

source_muzaki_id

candidate_muzaki_id

match_score

match_reasons

status

reviewed_by

reviewed_at

created_at

updated_at

---

## 35. Duplicate Review Status

PENDING

CONFIRMED_DUPLICATE

NOT_DUPLICATE

IGNORED

---

# PRD 03L — MERGE MUZAKI

## 36. Purpose

Merge digunakan untuk menggabungkan dua record Muzaki yang terbukti merupakan entitas yang sama.

---

## 37. Merge Entity

muzaki_merge_logs

Fields:

id

organization_id

source_muzaki_id

target_muzaki_id

reason

source_snapshot

target_snapshot

merged_by

merged_at

created_at

---

## 38. Merge Rules

1. Source record tidak langsung dihapus.
2. Source record menjadi ARCHIVED atau MERGED.
3. Historical transaction tidak boleh hilang.
4. Audit trail wajib tersedia.
5. Merge membutuhkan permission khusus.
6. Merge tidak dapat dilakukan tanpa confirmation.
7. Merge harus transactional.
8. Merge harus idempotent.

---

## 39. Merge Strategy

Recommended:

Source Muzaki

↓

Reference redirected to Target

↓

Source = ARCHIVED

↓

Merge Log recorded

Namun implementasi akhir harus memastikan historical transaction tetap dapat ditelusuri ke identitas awal.

---

# PRD 03M — PRIVACY & COMMUNICATION

## 40. Preference Entity

muzaki_preferences

Fields:

id

muzaki_id

allow_email

allow_sms

allow_whatsapp

communication_preference

public_visibility

receipt_delivery_method

created_at

updated_at

---

## 41. Public Visibility

Initial values:

PUBLIC

ANONYMOUS

PRIVATE

### PUBLIC

Kontribusi dapat ditampilkan bersama identitas apabila fitur publikasi dan consent/policy organisasi mengizinkan.

### ANONYMOUS

Kontribusi dapat dipublikasikan tanpa identitas.

### PRIVATE

Identitas kontribusi tidak dipublikasikan pada public transparency.

---

## 42. Privacy Rules

Privacy preference tidak boleh menghapus:

- financial record;
- audit record;
- regulatory requirement;
- internal report.

Privacy preference hanya mengatur publikasi dan penggunaan data yang sesuai kebijakan.

---

# PRD 03N — TAG & SEGMENTATION

## 43. Tag Entity

muzaki_tags

Fields:

id

organization_id

code

name

description

status

created_at

updated_at

---

## 44. Tag Assignment

muzaki_tag_assignments

Fields:

id

muzaki_id

tag_id

assigned_by

assigned_at

---

## 45. Example Tags

Initial examples:

REGULAR
CORPORATE
VIP
NEW_MUZAKI
RECURRING
RAMADAN
HIGH_VALUE

Tags bukan pengganti status Muzaki.

---

# PRD 03O — NOTES

## 46. Notes

Entity:

muzaki_notes

Fields:

id

muzaki_id

note

visibility

created_by

created_at

updated_at

---

## 47. Note Visibility

Initial values:

INTERNAL

RESTRICTED

Restricted notes hanya dapat diakses oleh permission tertentu.

Sensitive information tidak boleh sembarangan dimasukkan ke Notes.

---

# PRD 03P — CONTRIBUTION SUMMARY

## 48. Purpose

Muzaki detail dapat menampilkan ringkasan kontribusi.

Contoh:

Total Contribution

Total Zakat

Total Infak

Total Sedekah

Contribution Count

Last Contribution Date

---

## 49. Source of Truth

Contribution summary bukan source of truth.

Source of truth:

Collection Transaction

dan:

Accounting / Ledger

Summary hanya merupakan projection atau aggregation.

---

## 50. Contribution History

Muzaki detail dapat menampilkan:

business_number

transaction_date

fund_type

zakat_type

amount

status

receipt_number

public_visibility

Data bersifat read-only pada modul Muzaki.

Perubahan transaksi dilakukan pada modul Collection.

---

# PRD 03Q — API SPECIFICATION

## 51. Muzaki Endpoints

GET /api/v1/muzakis

POST /api/v1/muzakis

GET /api/v1/muzakis/{id}

PATCH /api/v1/muzakis/{id}

POST /api/v1/muzakis/{id}/activate

POST /api/v1/muzakis/{id}/deactivate

POST /api/v1/muzakis/{id}/archive

---

## 52. Search

GET /api/v1/muzakis/search

Supported filters:

q

type

status

tag

email

phone

registration_source

created_from

created_to

---

## 53. Individual Profile

GET /api/v1/muzakis/{id}/individual-profile

PATCH /api/v1/muzakis/{id}/individual-profile

---

## 54. Organization Profile

GET /api/v1/muzakis/{id}/organization-profile

PATCH /api/v1/muzakis/{id}/organization-profile

---

## 55. Identity

GET /api/v1/muzakis/{id}/identities

POST /api/v1/muzakis/{id}/identities

PATCH /api/v1/muzaki-identities/{id}

POST /api/v1/muzaki-identities/{id}/verify

---

## 56. Contacts

GET /api/v1/muzakis/{id}/contacts

POST /api/v1/muzakis/{id}/contacts

PATCH /api/v1/muzaki-contacts/{id}

DELETE /api/v1/muzaki-contacts/{id}

---

## 57. Addresses

GET /api/v1/muzakis/{id}/addresses

POST /api/v1/muzakis/{id}/addresses

PATCH /api/v1/muzaki-addresses/{id}

DELETE /api/v1/muzaki-addresses/{id}

---

## 58. Family

GET /api/v1/muzakis/{id}/family-members

POST /api/v1/muzakis/{id}/family-members

PATCH /api/v1/muzaki-family-members/{id}

DELETE /api/v1/muzaki-family-members/{id}

---

## 59. Preferences

GET /api/v1/muzakis/{id}/preferences

PATCH /api/v1/muzakis/{id}/preferences

---

## 60. Duplicate Detection

POST /api/v1/muzakis/check-duplicate

---

## 61. Merge

POST /api/v1/muzakis/merge

Request:

source_muzaki_id

target_muzaki_id

reason

---

## 62. Contribution

GET /api/v1/muzakis/{id}/summary

GET /api/v1/muzakis/{id}/contributions

---

# PRD 03R — PERMISSIONS

## 63. Permission Codes

Initial permission:

muzaki.view

muzaki.create

muzaki.update

muzaki.activate

muzaki.deactivate

muzaki.archive

muzaki.view_sensitive

muzaki.verify_identity

muzaki.manage_contacts

muzaki.manage_addresses

muzaki.manage_family

muzaki.manage_preferences

muzaki.manage_tags

muzaki.manage_notes

muzaki.merge

muzaki.export

muzaki.view_audit

---

## 64. Sensitive Permission

Permission:

muzaki.view_sensitive

diperlukan untuk mengakses:

- Identity number penuh
- Sensitive contact
- Restricted notes
- Sensitive organization information

---

# PRD 03S — SECURITY

## 65. Data Classification

Data Muzaki diklasifikasikan:

PUBLIC

INTERNAL

CONFIDENTIAL

RESTRICTED

---

## 66. Confidential Data

Contoh:

- phone
- email
- address
- identity
- family data
- tax information

Akses harus mengikuti permission.

---

## 67. Logging Rule

Dilarang mencatat plaintext:

- NIK
- passport
- tax number
- password
- token
- encrypted secret
- full sensitive contact

---

## 68. API Security

Backend wajib memastikan:

Authenticated User

↓

Organization Membership

↓

Permission

↓

Muzaki Access

Frontend tidak boleh menjadi security boundary.

---

# PRD 03T — AUDIT

## 69. Audit Events

Minimal:

muzaki_created

muzaki_updated

muzaki_activated

muzaki_deactivated

muzaki_archived

muzaki_identity_added

muzaki_identity_updated

muzaki_identity_verified

muzaki_contact_added

muzaki_contact_updated

muzaki_contact_deleted

muzaki_address_added

muzaki_address_updated

muzaki_family_member_added

muzaki_family_member_updated

muzaki_family_member_deleted

muzaki_preferences_updated

muzaki_tag_added

muzaki_tag_removed

muzaki_note_created

muzaki_note_updated

muzaki_duplicate_detected

muzaki_duplicate_reviewed

muzaki_merged

muzaki_sensitive_data_viewed

---

## 70. Sensitive Data Audit

Akses data sensitif dapat dicatat.

Minimum:

actor_id

organization_id

action

entity_type

entity_id

reason

request_id

created_at

Nilai sensitif tidak boleh disimpan plaintext dalam audit log.

---

# PRD 03U — UI REQUIREMENTS

## 71. Muzaki List

ZETRA DataTable.

Columns:

Business Number

Display Name

Type

Primary Contact

Status

Last Contribution

Registration Date

Actions

---

## 72. Muzaki Create Wizard

Step 1:

Select Muzaki Type

↓

Step 2:

Basic Profile

↓

Step 3:

Identity

↓

Step 4:

Contact

↓

Step 5:

Address

↓

Step 6:

Family / Organization Data

↓

Step 7:

Preferences

↓

Step 8:

Review

↓

Create

---

## 73. Duplicate Warning

Flow:

User fills data

↓

Check Duplicate

↓

Possible Duplicate

↓

Show Candidate

↓

User decides

KEEP SEPARATE

atau:

REQUEST MERGE

---

## 74. Muzaki Detail

Header:

Display Name

Business Number

Type

Status

Primary Contact

Tabs:

Overview

Profile

Identity

Contacts

Addresses

Family

Preferences

Tags

Contributions

Notes

Documents

Audit

---

## 75. Contribution Visibility Indicator

Muzaki detail harus menampilkan:

PUBLIC

ANONYMOUS

PRIVATE

untuk membantu operator memahami visibility preference.

---

# PRD 03V — BUSINESS RULES

## 76. General Rules

1. Setiap Muzaki wajib berada dalam organization scope.
2. Business Number tidak boleh berubah.
3. Primary key menggunakan ULID.
4. Sensitive data wajib diproteksi.
5. Muzaki yang memiliki historical transaction tidak boleh dihapus permanen.
6. Archived Muzaki tidak dapat digunakan untuk transaksi baru kecuali direaktivasi.
7. Historical transaction tetap dapat ditelusuri.
8. Duplicate detection tidak boleh otomatis menghapus data.
9. Merge membutuhkan permission khusus.
10. Merge wajib memiliki audit trail.
11. Privacy preference tidak boleh menghapus financial atau audit history.
12. Frontend filtering tidak boleh menjadi satu-satunya mekanisme security.

---

## 77. Organization Isolation

User hanya dapat mengakses Muzaki dalam organization scope yang valid.

Backend wajib memvalidasi:

user

→ membership

→ organization

→ permission

→ resource

---

# PRD 03W — ACCEPTANCE CRITERIA

- [ ] Muzaki Individual dapat dibuat.
- [ ] Muzaki Family dapat dibuat.
- [ ] Muzaki Company dapat dibuat.
- [ ] Muzaki Organization dapat dibuat.
- [ ] Muzaki Institution dapat dibuat.
- [ ] ULID digunakan sebagai primary key.
- [ ] Business Number MZK otomatis dibuat.
- [ ] Business Number immutable.
- [ ] Individual profile tersedia.
- [ ] Organization profile tersedia.
- [ ] Identity management tersedia.
- [ ] Sensitive identity dapat diamankan.
- [ ] Contact management tersedia.
- [ ] Address management tersedia.
- [ ] Family management tersedia.
- [ ] Communication preference tersedia.
- [ ] Privacy preference tersedia.
- [ ] Duplicate detection tersedia.
- [ ] Duplicate review tersedia.
- [ ] Merge workflow tersedia.
- [ ] Tagging tersedia.
- [ ] Notes tersedia.
- [ ] Contribution summary tersedia.
- [ ] Contribution history tersedia.
- [ ] Organization isolation diterapkan.
- [ ] Sensitive access menggunakan permission.
- [ ] Audit trail tersedia.
- [ ] Automated test tersedia.

---

# PRD 03X — DEFINITION OF DONE

Modul Muzaki Management dianggap selesai apabila:

1. Seluruh tipe Muzaki yang ditentukan dapat dibuat.
2. Business Number otomatis dan aman.
3. ULID digunakan sebagai primary identifier.
4. Profil individu dan organisasi tersedia.
5. Identity dapat dikelola secara aman.
6. Contact dan address tersedia.
7. Family data dapat dikelola.
8. Privacy dan communication preference tersedia.
9. Duplicate detection tersedia.
10. Merge workflow aman dan dapat diaudit.
11. Contribution summary tersedia.
12. Historical contribution dapat ditampilkan.
13. Sensitive data terlindungi.
14. Organization isolation berjalan.
15. Audit trail tersedia.
16. Automated test berhasil.
17. Modul siap menjadi dependency untuk modul Zakat.

---

# END OF PRD MODULE 03 — MUZAKI MANAGEMENT