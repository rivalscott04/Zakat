# PRD MODULE 05 — ZAKAT CALCULATOR

Project: ZETRA
Module: Zakat Calculator
Module Code: ZKC
Version: 0.1.0
Status: Implemented (calculation engine complete; Collection conversion boundary reserved for Module 06)

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md
- 03-muzaki.md
- 04-zakat.md

---

# PRD 05A — OVERVIEW

## 1. Purpose

Modul Zakat Calculator bertanggung jawab untuk melakukan perhitungan estimasi atau kewajiban zakat berdasarkan:

- Muzaki
- Zakat Type
- Zakat Rule
- Nisab
- Haul
- Rate
- Reference Value
- Parameter Input
- Calculation Date

Modul ini tidak mengelola master aturan zakat.

Master aturan dikelola pada:

04-zakat.md

Modul ini tidak mengelola transaksi pembayaran.

Transaksi penghimpunan dikelola pada:

06-collection.md

Zakat Calculator bertindak sebagai Calculation Engine yang menerima input, menentukan rule yang berlaku, melakukan validasi, menjalankan formula, dan menghasilkan calculation result yang dapat digunakan oleh modul Collection.

---

## 2. Goals

Modul harus mampu:

1. Menghitung berbagai jenis zakat.
2. Menentukan rule yang berlaku berdasarkan context.
3. Menggunakan nisab yang sesuai.
4. Menggunakan haul apabila diperlukan.
5. Menggunakan rate yang sesuai.
6. Menggunakan reference value yang berlaku.
7. Mendukung parameter dinamis.
8. Mendukung multiple calculation method.
9. Menyimpan calculation snapshot.
10. Menjamin hasil calculation lama tidak berubah.
11. Menampilkan breakdown calculation.
12. Menampilkan explanation atau reasoning.
13. Mendukung recalculation.
14. Mendukung manual adjustment dengan audit.
15. Mendukung draft calculation.
16. Mendukung calculation yang akan digunakan pada transaksi.
17. Menjaga formula calculation tetap berada di backend.
18. Mendukung versioning formula.
19. Mendukung audit trail.

---

# PRD 05B — CORE PRINCIPLE

## 3. Calculation Flow

Muzaki

↓

Select Zakat Type

↓

Input Calculation Data

↓

Resolve Active Rule

↓

Load Nisab

↓

Load Haul

↓

Load Rate

↓

Load Reference Value

↓

Validate Input

↓

Execute Formula

↓

Determine Eligibility

↓

Generate Calculation Result

↓

Create Snapshot

↓

Ready for Collection

---

## 4. Source of Truth

Calculation Engine menggunakan:

Zakat Type

↓

Zakat Rule

↓

Rule Version

↓

Calculation Parameter

↓

Reference Value

↓

Calculation Formula

↓

Calculation Snapshot

Hasil calculation yang telah disimpan tidak boleh dihitung ulang secara otomatis ketika:

- Harga emas berubah;
- Harga perak berubah;
- Rule baru diaktifkan;
- Nisab berubah;
- Rate berubah.

Historical result harus tetap menggunakan snapshot pada saat calculation dilakukan.

---

# PRD 05C — CALCULATION SESSION

## 5. Entity

zakat_calculations

Fields:

id

organization_id

muzaki_id

calculation_number

zakat_type_id

zakat_rule_id

rule_version

calculation_date

status

eligibility_status

gross_amount

deduction_amount

net_amount

nisab_amount

zakat_rate

zakat_amount

currency

result_data

created_by

created_at

updated_at

deleted_at

---

## 6. Calculation Number

Format:

ZKC{YEAR}{SEQUENCE}

Contoh:

ZKC2026000001

ZKC2026000002

ZKC2026000003

Rules:

- unique;
- immutable;
- human readable;
- tidak digunakan sebagai primary key;
- tidak boleh digunakan kembali.

Primary key menggunakan:

ULID

---

## 7. Calculation Status

Initial values:

DRAFT

CALCULATED

CONFIRMED

EXPIRED

CANCELLED

CONVERTED

---

## 8. Status Definition

### DRAFT

Calculation masih dalam proses input.

### CALCULATED

Calculation berhasil dilakukan.

### CONFIRMED

Calculation telah dikonfirmasi dan siap digunakan sebagai dasar transaksi.

### EXPIRED

Calculation sudah melewati masa berlaku apabila organization menerapkan expiration.

### CANCELLED

Calculation dibatalkan.

### CONVERTED

Calculation telah digunakan atau dikonversi menjadi transaksi Collection.

---

# PRD 05D — ELIGIBILITY

## 9. Eligibility Status

Initial values:

ELIGIBLE

NOT_ELIGIBLE

REVIEW_REQUIRED

INCOMPLETE

---

## 10. Eligibility Principle

Calculation Engine tidak hanya menghasilkan angka.

Engine harus menentukan apakah kondisi minimum zakat terpenuhi.

Contoh:

Net Asset

>=

Nisab

dan:

Haul terpenuhi

↓

ELIGIBLE

Jika:

Net Asset

<

Nisab

↓

NOT_ELIGIBLE

Jika parameter belum lengkap:

↓

INCOMPLETE

Jika membutuhkan validasi manusia:

↓

REVIEW_REQUIRED

---

# PRD 05E — CALCULATION INPUT

## 11. Entity

zakat_calculation_inputs

Fields:

id

calculation_id

parameter_code

value

normalized_value

unit

currency

source

created_at

updated_at

---

## 12. Input Source

Initial values:

MANUAL

IMPORT

API

SYSTEM

REFERENCE

CALCULATOR

---

## 13. Dynamic Parameter

Parameter diambil dari:

04-zakat.md

Contoh Zakat Penghasilan:

GROSS_INCOME

DEDUCTION

PERIOD

Contoh Zakat Emas:

GOLD_QUANTITY

OWNERSHIP_START_DATE

PURITY

Contoh Zakat Pertanian:

HARVEST_QUANTITY

IRRIGATION_TYPE

COMMODITY

Frontend harus mendapatkan parameter dari backend.

Frontend tidak boleh menghardcode seluruh form per jenis zakat.

---

# PRD 05F — CALCULATION SNAPSHOT

## 14. Purpose

Setiap calculation yang berhasil harus menyimpan snapshot.

Snapshot diperlukan untuk menjaga historical integrity.

---

## 15. Entity

zakat_calculation_snapshots

Fields:

id

calculation_id

zakat_type_snapshot

zakat_rule_snapshot

nisab_snapshot

haul_snapshot

rate_snapshot

reference_value_snapshot

parameter_snapshot

formula_snapshot

result_snapshot

created_at

---

## 16. Snapshot Rule

Snapshot dibuat ketika status berubah menjadi:

CALCULATED

atau:

CONFIRMED

Snapshot tidak boleh berubah setelah dibuat.

Jika calculation dilakukan ulang:

buat calculation baru

atau:

buat recalculation version baru.

Historical snapshot tetap dipertahankan.

---

# PRD 05G — FORMULA ENGINE

## 17. Purpose

Formula Engine bertanggung jawab menjalankan formula berdasarkan:

- Calculation Method
- Active Rule
- Parameter
- Reference Value

Formula tidak boleh berada di frontend.

Frontend hanya:

- mengirim input;
- menampilkan result;
- menampilkan breakdown.

Backend adalah source of truth.

---

## 18. Formula Type

Initial values:

FIXED

PERCENTAGE

NISAB_BASED

ASSET_BASED

INCOME_BASED

HARVEST_BASED

LIVESTOCK_BASED

CUSTOM

---

## 19. Formula Definition

Entity:

zakat_formula_definitions

Fields:

id

zakat_rule_id

formula_code

formula_version

formula_type

expression

input_schema

output_schema

status

created_at

updated_at

---

## 20. Formula Code

Format:

{ZAKAT_TYPE_CODE}{METHOD}

Contoh:

ZAKATEMASNISAB

ZAKATPENGHASILANPERCENTAGE

ZAKATPERTANIANHARVEST

ZAKATPETERNAKANLIVESTOCK

Code harus:

- unique dalam rule context;
- immutable setelah digunakan;
- tidak menjadi primary key.

---

## 21. Formula Security

Expression tidak boleh dieksekusi menggunakan:

- eval bebas;
- arbitrary code execution;
- user supplied script.

Formula Engine harus menggunakan:

- safe expression parser;
- predefined operator;
- whitelist function;
- typed input;
- validation layer.

---

# PRD 05H — STANDARD CALCULATION METHODS

## 22. Percentage Calculation

Formula dasar:

Zakat Amount

=

Calculation Base

×

Rate

Contoh:

Calculation Base:

100000000

Rate:

2.5%

Result:

2500000

---

## 23. Nisab Based Calculation

Formula:

Net Asset

↓

Compare Nisab

Jika:

Net Asset < Nisab

↓

Not Eligible

Jika:

Net Asset >= Nisab

↓

Apply Rate

---

## 24. Asset Based Calculation

Input:

Total Asset

-

Eligible Deduction

=

Net Zakatable Asset

Kemudian:

Compare Nisab

↓

Apply Rate

---

## 25. Income Based Calculation

Input:

Gross Income

-

Eligible Deduction

=

Net Income

Kemudian sistem menentukan basis calculation sesuai rule.

Rule dapat menggunakan:

PER_PERIOD

MONTHLY

ANNUAL

CUSTOM

---

## 26. Harvest Based Calculation

Input:

Harvest Quantity

↓

Compare Minimum Quantity

↓

Determine Irrigation Type

↓

Determine Rate

↓

Calculate Obligation

Formula detail berasal dari active rule.

---

## 27. Livestock Based Calculation

Input:

Livestock Type

+

Quantity

↓

Find Matching Tier

↓

Determine Obligation

Jika tidak ada tier:

REVIEW_REQUIRED

atau:

NOT_ELIGIBLE

sesuai rule.

---

# PRD 05I — NISAB RESOLUTION

## 28. Nisab Resolution

Calculation Engine harus menentukan Nisab berdasarkan:

Zakat Type

↓

Active Rule

↓

Calculation Date

↓

Organization

↓

Region

↓

Reference Value

---

## 29. Example

Zakat Emas:

Nisab:

85 Gram Gold

Reference Value:

Gold Price per Gram

Nisab Amount:

85

×

Gold Price

Calculation harus menyimpan:

Gold Quantity

Gold Price

Nisab Quantity

Nisab Amount

Calculation Date

ke dalam snapshot.

---

# PRD 05J — HAUL VALIDATION

## 30. Haul Requirement

Jika Rule memiliki:

haul_type = NOT_REQUIRED

maka:

Haul Validation dilewati.

Jika:

haul_type = FIXED_PERIOD

maka sistem memvalidasi:

Ownership Start Date

atau:

Eligible Holding Period

---

## 31. Haul Result

HAUL_MET

HAUL_NOT_MET

HAUL_NOT_REQUIRED

HAUL_UNKNOWN

Jika data haul tidak cukup:

REVIEW_REQUIRED

atau:

INCOMPLETE

sesuai rule.

---

# PRD 05K — REFERENCE VALUE RESOLUTION

## 32. Purpose

Reference Value digunakan apabila calculation membutuhkan data eksternal atau nilai acuan.

Contoh:

- Harga emas;
- Harga perak;
- Harga beras;
- Harga komoditas.

---

## 33. Resolution Priority

Organization + Region

↓

Organization

↓

Global + Region

↓

Global Default

---

## 34. Calculation Date Rule

Reference value harus dipilih berdasarkan:

calculation_date

Sistem tidak boleh selalu menggunakan harga hari ini untuk historical calculation.

---

## 35. Missing Reference Value

Jika reference value tidak tersedia:

Calculation Status:

DRAFT

atau:

INCOMPLETE

Sistem harus memberikan error yang jelas.

Contoh:

REFERENCE_VALUE_NOT_FOUND

---

# PRD 05L — CALCULATION RESULT

## 36. Result Structure

Result minimal:

Calculation Number

Muzaki

Zakat Type

Rule

Calculation Date

Eligibility

Calculation Base

Deduction

Net Amount

Nisab

Haul Status

Rate

Zakat Amount

Currency

---

## 37. Result Data

result_data dapat menyimpan structured JSON.

Contoh konsep:

summary

breakdown

eligibility

inputs

reference_values

formula

warnings

recommendations

---

## 38. Warning

Calculation dapat menghasilkan warning.

Contoh:

REFERENCE_VALUE_OLD

HAUL_DATE_UNCERTAIN

MISSING_OPTIONAL_DATA

RULE_EXPIRING_SOON

Warnings tidak selalu membuat calculation gagal.

---

# PRD 05M — CALCULATION BREAKDOWN

## 39. Purpose

Sistem harus menjelaskan bagaimana hasil calculation diperoleh.

Contoh:

Gross Asset

100000000

Eligible Deduction

10000000

Net Asset

90000000

Nisab

85000000

Eligibility

ELIGIBLE

Rate

2.5%

Zakat Amount

2250000

---

## 40. Explanation Principle

User harus dapat memahami:

1. Data apa yang digunakan.
2. Rule apa yang digunakan.
3. Nisab yang digunakan.
4. Reference value yang digunakan.
5. Formula yang digunakan.
6. Bagaimana result diperoleh.

Explanation tidak boleh hanya berupa:

Zakat Anda: Rp2.250.000

---

# PRD 05N — MANUAL ADJUSTMENT

## 41. Purpose

Organization dapat mengizinkan Amil melakukan adjustment.

Manual adjustment tidak boleh mengganti original calculation tanpa jejak.

---

## 42. Entity

zakat_calculation_adjustments

Fields:

id

calculation_id

adjustment_type

original_amount

adjustment_amount

final_amount

reason

approved_by

approved_at

created_by

created_at

---

## 43. Adjustment Type

INCREASE

DECREASE

OVERRIDE

---

## 44. Adjustment Rules

Manual adjustment:

- membutuhkan reason;
- wajib diaudit;
- tidak menghapus original calculation;
- dapat membutuhkan approval;
- tidak boleh mengubah snapshot original.

---

# PRD 05O — RECALCULATION

## 45. Purpose

User dapat melakukan recalculation.

Contoh:

- Input berubah;
- Reference value diperbarui;
- Calculation date berubah;
- Rule berubah.

---

## 46. Recalculation Strategy

Recalculation tidak boleh mengubah historical calculation secara langsung.

Flow:

Existing Calculation

↓

Create New Version

↓

Load New Context

↓

Calculate

↓

Create New Snapshot

---

## 47. Entity

zakat_calculation_versions

Fields:

id

calculation_id

version

parent_calculation_id

reason

created_by

created_at

---

## 48. Recalculation Rule

Jika calculation telah:

CONFIRMED

atau:

CONVERTED

maka recalculation menghasilkan record baru.

Calculation lama tetap dipertahankan.

---

# PRD 05P — CONVERSION TO COLLECTION

## 49. Purpose

Calculation yang telah dikonfirmasi dapat digunakan sebagai dasar transaksi Collection.

---

## 50. Conversion Rule

Calculation:

CONFIRMED

↓

Create Collection Transaction

↓

Collection Transaction Reference

↓

Calculation Status:

CONVERTED

---

## 51. Relationship

Collection Transaction harus menyimpan:

calculation_id

Calculation tidak wajib menghasilkan transaksi.

User dapat:

Calculate

↓

Save

↓

Review Later

atau:

Calculate

↓

Confirm

↓

Proceed to Payment

---

# PRD 05Q — CALCULATION EXPIRATION

## 52. Purpose

Organization dapat menentukan masa berlaku calculation.

Contoh:

Calculation berlaku selama:

1 Day

7 Days

30 Days

atau:

Custom

---

## 53. Expiration Rule

Jika:

Current Date > Valid Until

maka:

EXPIRED

Calculation expired tidak boleh digunakan langsung untuk transaksi.

User harus:

Recalculate

atau:

Override dengan permission khusus.

---

# PRD 05R — API SPECIFICATION

## 54. Create Calculation

POST /api/v1/zakat/calculations

Request:

muzaki_id

zakat_type_id

calculation_date

region_id

inputs

---

## 55. Draft Calculation

POST /api/v1/zakat/calculations/draft

Digunakan untuk menyimpan input sebelum calculation selesai.

---

## 56. Calculate

POST /api/v1/zakat/calculations/{id}/calculate

Backend melakukan:

Rule Resolution

↓

Input Validation

↓

Reference Resolution

↓

Formula Execution

↓

Eligibility Check

↓

Snapshot

↓

Result

---

## 57. Preview Calculation

POST /api/v1/zakat/calculations/preview

Preview dapat digunakan tanpa menyimpan calculation permanen.

Preview tetap harus menggunakan backend engine.

Preview tidak menjadi source of truth untuk transaksi.

---

## 58. Get Calculation

GET /api/v1/zakat/calculations/{id}

---

## 59. List Calculations

GET /api/v1/zakat/calculations

Supported filters:

muzaki_id

zakat_type_id

status

eligibility_status

calculation_date_from

calculation_date_to

created_by

---

## 60. Confirm Calculation

POST /api/v1/zakat/calculations/{id}/confirm

---

## 61. Cancel Calculation

POST /api/v1/zakat/calculations/{id}/cancel

Request:

reason

---

## 62. Recalculate

POST /api/v1/zakat/calculations/{id}/recalculate

Request:

reason

new_calculation_date

updated_inputs

---

## 63. Manual Adjustment

POST /api/v1/zakat/calculations/{id}/adjustments

Request:

adjustment_type

adjustment_amount

reason

---

## 64. Convert to Collection

POST /api/v1/zakat/calculations/{id}/convert

Response:

collection_transaction_id

redirect_url

---

# PRD 05S — PERMISSIONS

## 65. Permission Codes

zakat.calculation.view

zakat.calculation.create

zakat.calculation.update

zakat.calculation.calculate

zakat.calculation.confirm

zakat.calculation.cancel

zakat.calculation.recalculate

zakat.calculation.adjust

zakat.calculation.override_expired

zakat.calculation.convert

zakat.calculation.view_breakdown

zakat.calculation.view_snapshot

zakat.calculation.view_audit

---

## 66. Approval

Organization dapat menerapkan approval untuk:

- Manual adjustment;
- Override;
- High value calculation;
- Calculation tertentu.

Permission tambahan:

zakat.calculation.approve

User yang membuat adjustment tidak dapat menyetujui adjustment sendiri jika segregation of duties aktif.

---

# PRD 05T — AUDIT EVENTS

## 67. Audit Events

Minimal:

zakat_calculation_created

zakat_calculation_updated

zakat_calculation_started

zakat_calculation_completed

zakat_calculation_failed

zakat_calculation_confirmed

zakat_calculation_cancelled

zakat_calculation_expired

zakat_calculation_recalculated

zakat_calculation_adjusted

zakat_calculation_converted

zakat_rule_resolved

zakat_reference_value_resolved

zakat_formula_executed

zakat_eligibility_checked

---

## 68. Audit Data

Audit minimal menyimpan:

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

Sensitive calculation input tidak boleh dicatat secara tidak aman.

---

# PRD 05U — ERROR HANDLING

## 69. Error Codes

CALCULATION_NOT_FOUND

CALCULATION_INVALID_STATUS

CALCULATION_EXPIRED

RULE_NOT_FOUND

RULE_CONFLICT

NISAB_NOT_FOUND

HAUL_VALIDATION_FAILED

REFERENCE_VALUE_NOT_FOUND

REFERENCE_VALUE_EXPIRED

INVALID_INPUT

MISSING_REQUIRED_PARAMETER

FORMULA_ERROR

FORMULA_VERSION_NOT_FOUND

CALCULATION_NOT_ELIGIBLE

ADJUSTMENT_NOT_ALLOWED

CONVERSION_NOT_ALLOWED

---

# PRD 05V — UI REQUIREMENTS

## 70. Calculator Entry

User memilih:

Muzaki

↓

Zakat Type

↓

Calculation Date

↓

Region

↓

Dynamic Input Form

↓

Calculate

---

## 71. Dynamic Input Form

Frontend mengambil schema dari backend.

Contoh response:

parameter_code

label

data_type

required

default_value

validation

options

Frontend merender form berdasarkan schema.

---

## 72. Calculator Result

Menampilkan:

Eligibility Status

Zakat Amount

Calculation Breakdown

Nisab

Haul

Rate

Reference Value

Rule Version

Warnings

Actions:

Save Draft

Confirm

Recalculate

Proceed to Payment

---

## 73. Calculation Detail

Header:

Calculation Number

Status

Muzaki

Zakat Type

Calculation Date

Tabs:

Result

Breakdown

Inputs

Rule Snapshot

Reference Snapshot

Adjustment

History

Audit

---

## 74. Recalculation UI

User memilih:

Reason

Calculation Date

Updated Input

↓

Preview New Result

↓

Confirm Recalculation

System membuat calculation version baru.

---

# PRD 05W — BUSINESS RULES

## 75. General Rules

1. Semua calculation dilakukan di backend.
2. Frontend tidak menentukan final zakat amount.
3. Calculation wajib menggunakan rule aktif.
4. Rule harus ditentukan berdasarkan calculation context.
5. Reference value harus sesuai calculation date.
6. Historical calculation menggunakan snapshot.
7. Historical calculation tidak berubah otomatis.
8. Formula harus versioned.
9. Formula tidak boleh menggunakan arbitrary code execution.
10. Input wajib divalidasi.
11. Calculation number immutable.
12. Calculation confirmed tidak boleh diedit langsung.
13. Recalculation menghasilkan version baru.
14. Manual adjustment wajib memiliki reason.
15. Manual adjustment wajib diaudit.
16. Calculation expired tidak boleh langsung dikonversi.
17. Collection menggunakan confirmed calculation.
18. Organization isolation wajib diterapkan.
19. Permission diperiksa di backend.
20. Calculation result harus explainable.

---

# PRD 05X — TESTING REQUIREMENTS

## 76. Unit Test

Minimal test:

- Percentage calculation
- Nisab comparison
- Haul validation
- Asset calculation
- Income calculation
- Harvest calculation
- Livestock tier resolution
- Rule resolution
- Reference value resolution
- Formula validation
- Input validation
- Calculation expiration

---

## 77. Integration Test

Minimal:

Create Muzaki

↓

Create Calculation

↓

Resolve Rule

↓

Calculate

↓

Create Snapshot

↓

Confirm

↓

Convert to Collection

Pastikan calculation lama tidak berubah ketika:

- Rule baru dibuat;
- Reference value baru dibuat;
- Rate berubah.

---

## 78. Security Test

Test:

- Unauthorized calculation access;
- Cross organization access;
- Sensitive data exposure;
- Formula injection;
- Invalid adjustment;
- Unauthorized override;
- Duplicate conversion;
- Expired calculation conversion.

---

# PRD 05Y — ACCEPTANCE CRITERIA

- [ ] Calculation dapat dibuat.
- [ ] Draft calculation tersedia.
- [ ] Preview calculation tersedia.
- [ ] Dynamic parameter dapat dirender.
- [ ] Active rule dapat di-resolve.
- [ ] Nisab dapat di-resolve.
- [ ] Haul dapat divalidasi.
- [ ] Reference value dapat di-resolve.
- [ ] Percentage calculation tersedia.
- [ ] Nisab based calculation tersedia.
- [ ] Asset based calculation tersedia.
- [ ] Income based calculation tersedia.
- [ ] Harvest based calculation tersedia.
- [ ] Livestock calculation tersedia.
- [ ] Eligibility dapat ditentukan.
- [ ] Calculation breakdown tersedia.
- [ ] Explanation tersedia.
- [ ] Snapshot dibuat.
- [ ] Historical result tidak berubah.
- [ ] Recalculation membuat version baru.
- [ ] Manual adjustment tersedia.
- [ ] Adjustment diaudit.
- [ ] Calculation dapat dikonfirmasi.
- [ ] Calculation dapat expired.
- [ ] Calculation dapat dikonversi ke Collection.
- [ ] Organization isolation diterapkan.
- [ ] Permission diterapkan.
- [ ] Automated test tersedia.

---

# PRD 05Z — DEFINITION OF DONE

Modul Zakat Calculator dianggap selesai apabila:

1. User dapat membuat calculation.
2. Sistem dapat menentukan active rule.
3. Sistem dapat memvalidasi input.
4. Sistem dapat menentukan Nisab.
5. Sistem dapat memvalidasi Haul.
6. Sistem dapat mengambil Reference Value.
7. Formula dapat dijalankan secara aman.
8. Eligibility dapat ditentukan.
9. Zakat Amount dapat dihitung.
10. Calculation Breakdown tersedia.
11. Calculation Explanation tersedia.
12. Snapshot tersedia.
13. Historical result tidak berubah.
14. Recalculation menggunakan version baru.
15. Manual adjustment memiliki audit.
16. Confirmed calculation dapat dikonversi.
17. Calculation dapat digunakan oleh Collection Module.
18. Organization isolation berjalan.
19. Permission berjalan.
20. Automated test berhasil.

---

# END OF PRD MODULE 05 — ZAKAT CALCULATOR
