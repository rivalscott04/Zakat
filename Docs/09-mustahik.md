# PRD MODULE 09 — MUSTAHIK

Project: ZETRA
Module: Mustahik
Module Code: MSH
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md
- 04-zakat.md

Related Modules:

- 07-fund-management.md
- 08-accounting-ledger.md
- 10-distribution.md
- 11-program-management.md
- 12-reporting.md

---

# PRD 09A — OVERVIEW

## 1. Purpose

Modul Mustahik bertanggung jawab untuk mengelola data individu, keluarga, kelompok, atau entitas penerima manfaat yang memenuhi kriteria sebagai penerima zakat atau bantuan sosial lainnya.

Mustahik merupakan salah satu master entity utama dalam ZETRA.

Modul ini menjadi sumber data untuk:

- Distribution
- Program Management
- Eligibility Assessment
- Asnaf Classification
- Beneficiary Verification
- Distribution History
- Empowerment Program
- Reporting
- Transparency

Mustahik tidak langsung menerima dana hanya karena telah terdaftar.

Setiap proses penyaluran harus tetap melalui:

Eligibility

Assessment

Approval

Distribution

---

## 2. Goals

Modul harus mampu:

1. Mendaftarkan Mustahik.
2. Mendukung individu.
3. Mendukung household atau keluarga.
4. Mendukung kelompok penerima manfaat.
5. Mengelola profil Mustahik.
6. Mengelola identitas.
7. Mengelola alamat dan wilayah.
8. Mengelola data keluarga.
9. Mengelola Asnaf Classification.
10. Mengelola kondisi sosial ekonomi.
11. Mengelola assessment.
12. Mengelola eligibility.
13. Mengelola verification.
14. Mencegah duplicate beneficiary.
15. Menyimpan riwayat bantuan.
16. Menyimpan riwayat assessment.
17. Menyimpan status aktif atau tidak aktif.
18. Mendukung data privacy.
19. Mendukung document verification.
20. Menjadi sumber data Distribution.
21. Mendukung traceability penerima manfaat.
22. Menyediakan audit trail.

Pengelolaan data mustahik yang baik menjadi fondasi untuk proses distribusi, assessment, dan pelacakan riwayat bantuan. Sistem zakat modern umumnya memisahkan data mustahik, kategori penerima, dan proses penyaluran agar pengelolaan penerima manfaat dapat ditelusuri dengan lebih baik. :contentReference[oaicite:0]{index=0}

---

# PRD 09B — CORE PRINCIPLE

## 3. Mustahik Principle

Mustahik adalah beneficiary entity.

Mustahik dapat berupa:

INDIVIDUAL

HOUSEHOLD

GROUP

ORGANIZATION

Versi awal wajib mendukung:

INDIVIDUAL

HOUSEHOLD

Arsitektur harus mendukung tipe lain.

---

## 4. Separation Principle

Registration

tidak sama dengan:

Eligibility.

Eligibility

tidak sama dengan:

Approval.

Approval

tidak sama dengan:

Distribution.

Flow:

Register Mustahik

↓

Verification

↓

Assessment

↓

Eligibility Determination

↓

Approval

↓

Distribution

---

# PRD 09C — MUSTAHIK ENTITY

## 5. Entity

mustahiks

Fields:

id

organization_id

mustahik_number

mustahik_type

full_name

display_name

gender

birth_date

marital_status

phone

email

identity_type

identity_number

status

verification_status

eligibility_status

registered_at

registered_by

created_at

updated_at

deleted_at

---

## 6. Mustahik Number

Format:

MSH{YEAR}{SEQUENCE}

Contoh:

MSH2026000001

MSH2026000002

MSH2026000003

Rules:

- unique;
- immutable;
- human readable;
- tidak menggunakan dash;
- tidak digunakan kembali;
- bukan primary key.

Primary key menggunakan:

ULID.

---

# PRD 09D — MUSTAHIK TYPE

## 7. Initial Type

INDIVIDUAL

HOUSEHOLD

GROUP

ORGANIZATION

---

## 8. Individual

Mustahik merupakan satu orang.

Contoh:

Fakir

Miskin

Mualaf

Ibnu Sabil

dan kategori lain sesuai assessment.

---

## 9. Household

Mustahik mewakili satu rumah tangga.

Satu Household dapat memiliki:

Household Head

Family Members

Dependents

Income Data

Asset Data

Assessment Data

Distribution History

---

# PRD 09E — IDENTITY

## 10. Entity

mustahik_identities

Fields:

id

mustahik_id

identity_type

identity_number

identity_name

verification_status

verified_at

verified_by

created_at

updated_at

---

## 11. Identity Type

Initial values:

NIK

PASSPORT

FAMILY_CARD

STUDENT_ID

LOCAL_ID

OTHER

---

## 12. Identity Rule

Identity Number dapat digunakan untuk duplicate detection.

Data sensitif tidak boleh ditampilkan secara penuh kepada seluruh role.

Contoh display:

3201********1234

Raw value hanya dapat diakses oleh user dengan permission tertentu.

---

# PRD 09F — DUPLICATE DETECTION

## 13. Purpose

Sistem harus mencegah satu Mustahik terdaftar berulang.

Duplicate detection dapat menggunakan:

- Identity Number
- Full Name
- Birth Date
- Phone Number
- Address
- Household Member
- Similarity Score

---

## 14. Duplicate Status

NO_MATCH

POSSIBLE_MATCH

CONFIRMED_DUPLICATE

MERGED

---

## 15. Duplicate Flow

Create Mustahik

↓

Duplicate Detection

↓

No Match

atau:

Possible Match

↓

Manual Review

↓

Create New

atau:

Merge Existing

---

## 16. Merge Rule

Mustahik yang telah memiliki:

Distribution History

Assessment

Verification

tidak boleh langsung dihapus.

Duplicate record dapat:

MERGED

Historical reference harus tetap dapat ditelusuri.

---

# PRD 09G — ADDRESS

## 17. Entity

mustahik_addresses

Fields:

id

mustahik_id

address_type

address_line

province_code

regency_code

district_code

village_code

postal_code

latitude

longitude

is_primary

created_at

updated_at

---

## 18. Address Type

HOME

TEMPORARY

WORK

OTHER

---

## 19. Geographic Data

Sistem harus mendukung wilayah administratif.

Minimal:

Province

Regency

District

Village

Data wilayah dapat menggunakan master data terpisah.

---

# PRD 09H — HOUSEHOLD

## 20. Entity

households

Fields:

id

organization_id

household_number

head_mustahik_id

address_id

total_members

total_dependents

status

created_at

updated_at

---

## 21. Household Member

Entity:

household_members

Fields:

id

household_id

mustahik_id

relationship

is_head

is_dependent

created_at

updated_at

---

## 22. Relationship

Initial values:

HEAD

SPOUSE

CHILD

PARENT

SIBLING

RELATIVE

OTHER

---

# PRD 09I — ASNAF CLASSIFICATION

## 23. Purpose

Asnaf Classification digunakan untuk mengelompokkan Mustahik sesuai kategori penerima zakat.

Initial classification:

FAKIR

MISKIN

AMIL

MUALAF

RIQAB

GHARIM

FISABILILLAH

IBNUSABIL

Sistem harus memungkinkan konfigurasi dan penyesuaian sesuai kebijakan syariah dan organisasi.

---

## 24. Entity

mustahik_asnaf

Fields:

id

mustahik_id

asnaf_code

primary_asnaf

assessment_id

status

effective_from

effective_until

assigned_by

created_at

updated_at

---

## 25. Primary Asnaf

Satu Mustahik dapat memiliki lebih dari satu classification record apabila diperlukan.

Namun hanya boleh memiliki:

satu primary_asnaf aktif

pada satu waktu.

---

## 26. Asnaf Assignment Rule

Asnaf tidak boleh hanya menjadi input bebas tanpa dasar.

Assignment harus memiliki:

assessment reference

reason

assigned_by

effective date

Audit trail.

---

# PRD 09J — SOCIOECONOMIC PROFILE

## 27. Purpose

Socioeconomic Profile digunakan untuk menyimpan kondisi Mustahik.

Data dapat digunakan untuk:

Assessment

Eligibility

Prioritization

Program Matching

Impact Measurement

---

## 28. Entity

mustahik_profiles

Fields:

id

mustahik_id

education_level

occupation

employment_status

monthly_income

monthly_expense

housing_status

house_condition

asset_summary

disability_status

health_condition_summary

notes

created_at

updated_at

---

## 29. Privacy Rule

Data sensitif harus memiliki access control.

Tidak semua field dapat diakses oleh:

Public

Basic User

Field Officer

Read-only User

Access harus mengikuti permission.

---

# PRD 09K — INCOME

## 30. Entity

mustahik_incomes

Fields:

id

mustahik_id

income_type

source

amount

currency

frequency

effective_from

effective_until

verification_status

created_at

updated_at

---

## 31. Income Type

EMPLOYMENT

BUSINESS

FARMING

FISHING

DONATION

PENSION

OTHER

---

# PRD 09L — ASSET

## 32. Entity

mustahik_assets

Fields:

id

mustahik_id

asset_type

description

estimated_value

ownership_status

created_at

updated_at

---

## 33. Asset Type

PROPERTY

VEHICLE

LAND

LIVESTOCK

BUSINESS

SAVINGS

OTHER

Asset data digunakan sebagai bagian dari assessment.

Sistem tidak boleh otomatis menentukan eligibility hanya berdasarkan satu field.

---

# PRD 09M — MUSTAHIK ASSESSMENT

## 34. Purpose

Assessment digunakan untuk mengevaluasi kondisi Mustahik.

Assessment dapat dilakukan oleh:

Amil

Field Officer

Surveyor

Authorized Assessor

---

## 35. Entity

mustahik_assessments

Fields:

id

organization_id

assessment_number

mustahik_id

assessment_type

assessment_date

assessor_id

score

result

recommendation

status

notes

created_at

updated_at

---

## 36. Assessment Number

Format:

ASM{YEAR}{SEQUENCE}

Contoh:

ASM2026000001

ASM2026000002

---

## 37. Assessment Type

INITIAL

REASSESSMENT

PROGRAM

EMERGENCY

VERIFICATION

FOLLOW_UP

---

# PRD 09N — ASSESSMENT QUESTIONNAIRE

## 38. Purpose

Assessment harus configurable.

Sistem tidak boleh hardcode seluruh pertanyaan assessment.

---

## 39. Entity

assessment_templates

Fields:

id

organization_id

template_code

name

description

mustahik_type

version

status

created_at

updated_at

---

## 40. Assessment Question

Entity:

assessment_questions

Fields:

id

template_id

question_code

question

question_type

required

weight

options

sort_order

created_at

updated_at

---

## 41. Question Type

TEXT

NUMBER

SELECT

MULTISELECT

BOOLEAN

DATE

DOCUMENT

PHOTO

SIGNATURE

LOCATION

---

# PRD 09O — ELIGIBILITY

## 42. Eligibility Status

Initial values:

UNKNOWN

UNDER_REVIEW

ELIGIBLE

NOT_ELIGIBLE

SUSPENDED

EXPIRED

---

## 43. Eligibility Rule

Eligibility dapat ditentukan berdasarkan:

Assessment

Asnaf

Organization Policy

Program Criteria

Verification

Manual Decision

Sistem harus menyimpan dasar keputusan.

---

## 44. Entity

mustahik_eligibilities

Fields:

id

mustahik_id

eligibility_type

status

reason

assessment_id

determined_by

determined_at

effective_from

effective_until

created_at

updated_at

---

# PRD 09P — VERIFICATION

## 45. Verification Status

NOT_VERIFIED

PENDING

VERIFIED

REJECTED

EXPIRED

---

## 46. Verification Scope

Verification dapat dilakukan terhadap:

Identity

Address

Income

Household

Asnaf

Document

Assessment

---

## 47. Entity

mustahik_verifications

Fields:

id

mustahik_id

verification_type

status

verified_by

verified_at

rejection_reason

notes

created_at

updated_at

---

# PRD 09Q — DOCUMENT MANAGEMENT

## 48. Entity

mustahik_documents

Fields:

id

mustahik_id

document_type

file_id

document_number

issued_at

expires_at

verification_status

uploaded_by

created_at

updated_at

---

## 49. Document Type

IDENTITY

FAMILY_CARD

INCOME_PROOF

ADDRESS_PROOF

ASSESSMENT

PHOTO

OTHER

Document file disimpan melalui:

Core File Management.

Modul Mustahik hanya menyimpan reference.

---

# PRD 09R — STATUS

## 50. Mustahik Status

ACTIVE

INACTIVE

SUSPENDED

DECEASED

MERGED

ARCHIVED

---

## 51. Status Rule

Mustahik dengan Distribution History tidak boleh dihapus secara permanen.

Gunakan:

INACTIVE

ARCHIVED

atau:

MERGED.

---

# PRD 09S — DISTRIBUTION HISTORY

## 52. Purpose

Modul Mustahik harus dapat menampilkan seluruh riwayat bantuan.

Source data berasal dari:

Distribution Module.

Mustahik Module tidak membuat Distribution Record.

---

## 53. History Data

Minimal:

Distribution Number

Program

Fund

Distribution Type

Amount

Date

Status

Verification

---

## 54. Mustahik Profile Timeline

Timeline menampilkan:

Registered

↓

Verified

↓

Assessment

↓

Eligibility

↓

Program Enrollment

↓

Distribution

↓

Follow Up

↓

Reassessment

---

# PRD 09T — PRIORITY SCORE

## 55. Purpose

Organization dapat menggunakan Priority Score untuk membantu menentukan prioritas penerima manfaat.

Priority Score bukan pengganti keputusan manusia.

---

## 56. Score Source

Score dapat mempertimbangkan:

Income

Dependents

Housing Condition

Asset

Emergency Condition

Assessment Result

Distribution History

Time Since Last Assistance

Organization Policy

---

## 57. Rule

Priority Score harus dapat dijelaskan.

Sistem harus dapat menampilkan:

Score

Factors

Weight

Calculation Version

Tidak boleh menggunakan black box scoring tanpa traceability.

---

# PRD 09U — MUSTAHIK TAG

## 58. Entity

mustahik_tags

Fields:

id

mustahik_id

tag_id

created_at

---

## 59. Example Tags

EMERGENCY

ELDERLY

DISABILITY

ORPHAN

SINGLE_PARENT

DISASTER

POOR_HOUSING

BUSINESS_ASSISTANCE

EDUCATION

Tags digunakan untuk:

Filtering

Program Matching

Reporting

Prioritization

---

# PRD 09V — API SPECIFICATION

## 60. Mustahik

GET

/api/v1/mustahiks

POST

/api/v1/mustahiks

GET

/api/v1/mustahiks/{id}

PATCH

/api/v1/mustahiks/{id}

---

## 61. Search

GET

/api/v1/mustahiks/search

Parameters:

q

identity_number

phone

name

region

asnaf

status

---

## 62. Duplicate Check

POST

/api/v1/mustahiks/check-duplicate

Request:

full_name

birth_date

identity_number

phone

address

---

## 63. Assessment

GET

/api/v1/mustahiks/{id}/assessments

POST

/api/v1/mustahiks/{id}/assessments

GET

/api/v1/assessments/{id}

POST

/api/v1/assessments/{id}/submit

POST

/api/v1/assessments/{id}/approve

---

## 64. Eligibility

POST

/api/v1/mustahiks/{id}/eligibility

GET

/api/v1/mustahiks/{id}/eligibility

---

## 65. Verification

POST

/api/v1/mustahiks/{id}/verify

Request:

verification_type

status

notes

---

## 66. Distribution History

GET

/api/v1/mustahiks/{id}/distributions

---

# PRD 09W — PERMISSIONS

## 67. Permission Codes

mustahik.view

mustahik.create

mustahik.update

mustahik.delete

mustahik.merge

mustahik.identity.view

mustahik.identity.verify

mustahik.document.view

mustahik.document.upload

mustahik.document.verify

mustahik.assessment.view

mustahik.assessment.create

mustahik.assessment.submit

mustahik.assessment.approve

mustahik.eligibility.view

mustahik.eligibility.determine

mustahik.verification.perform

mustahik.household.view

mustahik.household.manage

mustahik.distribution_history.view

mustahik.export

mustahik.audit.view

---

# PRD 09X — AUDIT EVENTS

## 68. Audit Events

Minimal:

mustahik_created

mustahik_updated

mustahik_verified

mustahik_verification_rejected

mustahik_status_changed

mustahik_duplicate_detected

mustahik_merged

mustahik_assessment_created

mustahik_assessment_submitted

mustahik_assessment_approved

mustahik_eligibility_determined

mustahik_asnaf_assigned

mustahik_asnaf_changed

mustahik_document_uploaded

mustahik_document_verified

mustahik_household_created

mustahik_household_member_added

mustahik_household_member_removed

mustahik_archived

---

## 69. Audit Data

Audit menyimpan:

actor_id

organization_id

action

entity_type

entity_id

request_id

timestamp

reason

before

after

reference

---

# PRD 09Y — UI REQUIREMENTS

## 70. Mustahik Dashboard

Cards:

Total Mustahik

Active

Verified

Eligible

Under Review

Pending Assessment

Recent Distribution

Potential Duplicate

---

## 71. Mustahik List

ZETRA DataTable.

Columns:

Mustahik Number

Name

Type

Primary Asnaf

Region

Verification Status

Eligibility

Last Distribution

Status

Actions

---

## 72. Mustahik Detail

Header:

Photo

Full Name

Mustahik Number

Primary Asnaf

Verification Status

Eligibility Status

Priority Score

Tabs:

Overview

Identity

Household

Address

Socioeconomic

Asnaf

Assessment

Eligibility

Documents

Distribution History

Timeline

Audit

---

## 73. Create Mustahik

Steps:

Step 1

Basic Information

↓

Step 2

Identity

↓

Step 3

Address

↓

Step 4

Household

↓

Step 5

Socioeconomic

↓

Duplicate Check

↓

Create

---

## 74. Assessment UI

Assessment harus mendukung:

Dynamic Questions

Conditional Questions

Document Upload

Photo

Location

Score Preview

Recommendation

Submit

---

# PRD 09Z — BUSINESS RULES

## 75. General Rules

1. Mustahik Number harus unik.
2. Mustahik tidak boleh dihapus jika memiliki transaksi.
3. Duplicate detection harus dijalankan saat pendaftaran.
4. Identity data harus dilindungi.
5. Satu Mustahik dapat memiliki beberapa identity record.
6. Household dapat memiliki banyak anggota.
7. Satu anggota hanya boleh memiliki satu active primary household.
8. Asnaf harus memiliki dasar assessment atau keputusan.
9. Eligibility tidak otomatis berarti Distribution.
10. Distribution History berasal dari Distribution Module.
11. Assessment harus memiliki assessor.
12. Assessment dapat menggunakan template version.
13. Eligibility harus memiliki reason.
14. Verification harus dicatat.
15. Sensitive data harus mengikuti permission.
16. Mustahik Merge tidak boleh menghilangkan historical trace.
17. Priority Score harus explainable.
18. Organization isolation wajib diterapkan.
19. Permission diperiksa di backend.
20. Semua perubahan penting harus diaudit.

---

# PRD 09AA — TESTING REQUIREMENTS

## 76. Unit Test

Minimal:

- Mustahik Creation
- Mustahik Number Generation
- Identity Validation
- Duplicate Detection
- Mustahik Merge
- Household Creation
- Household Member Validation
- Asnaf Assignment
- Primary Asnaf Validation
- Assessment Creation
- Assessment Submission
- Assessment Approval
- Eligibility Determination
- Verification
- Sensitive Data Masking
- Status Change
- Archive Validation

---

## 77. Integration Test

Flow:

Register Mustahik

↓

Duplicate Detection

↓

Verification

↓

Assessment

↓

Asnaf Assignment

↓

Eligibility Determination

↓

Distribution Module

↓

Distribution History

---

## 78. Security Test

Test:

- Cross organization access;
- Unauthorized identity access;
- Unauthorized document access;
- Duplicate registration;
- Invalid merge;
- Multiple primary household;
- Unauthorized eligibility determination;
- Sensitive data exposure;
- Audit bypass.

---

# PRD 09AB — ACCEPTANCE CRITERIA

- [ ] Mustahik dapat didaftarkan.
- [ ] Mustahik Number dibuat otomatis.
- [ ] Individual didukung.
- [ ] Household didukung.
- [ ] Identity dapat dikelola.
- [ ] Duplicate Detection tersedia.
- [ ] Duplicate Merge tersedia.
- [ ] Address dan wilayah tersedia.
- [ ] Household Member tersedia.
- [ ] Asnaf Classification tersedia.
- [ ] Socioeconomic Profile tersedia.
- [ ] Income Data tersedia.
- [ ] Asset Data tersedia.
- [ ] Assessment tersedia.
- [ ] Dynamic Assessment Template tersedia.
- [ ] Eligibility tersedia.
- [ ] Verification tersedia.
- [ ] Document Management tersedia.
- [ ] Distribution History dapat ditampilkan.
- [ ] Priority Score dapat digunakan.
- [ ] Sensitive Data dilindungi.
- [ ] Audit Trail tersedia.
- [ ] Organization isolation diterapkan.
- [ ] Permission diterapkan.
- [ ] Automated Test tersedia.

---

# PRD 09AC — DEFINITION OF DONE

Modul Mustahik dianggap selesai apabila:

1. Mustahik dapat didaftarkan.
2. Mustahik memiliki nomor unik.
3. Individual dan Household didukung.
4. Data identitas dapat dikelola.
5. Duplicate detection berjalan.
6. Duplicate record dapat ditinjau dan di-merge.
7. Data wilayah dan alamat tersedia.
8. Household dapat dikelola.
9. Asnaf dapat ditentukan.
10. Assessment dapat dilakukan.
11. Assessment menggunakan template configurable.
12. Eligibility dapat ditentukan.
13. Verification dapat dilakukan.
14. Dokumen dapat disimpan dan diverifikasi.
15. Riwayat distribusi dapat ditampilkan.
16. Priority Score dapat digunakan secara transparan.
17. Sensitive data dilindungi.
18. Audit Trail tersedia.
19. Organization isolation berjalan.
20. Permission berjalan.
21. Automated Test berhasil.
22. Modul siap digunakan oleh Distribution dan Program Management.

---

# END OF PRD MODULE 09 — MUSTAHIK