# PRD MODULE 04 — ZAKAT

Project: Zakat OS
Module: Zakat Management
Module Code: ZKT
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md
- 03-muzaki.md

---

# PRD 04A — OVERVIEW

## 1. Purpose

Modul Zakat Management bertanggung jawab untuk mengelola master domain zakat dalam sistem.

Modul ini menjadi sumber konfigurasi dan definisi untuk:

- Jenis Zakat
- Kategori Zakat
- Metode atau basis perhitungan
- Nisab
- Haul
- Kadar Zakat
- Satuan
- Kondisi atau parameter zakat
- Effective Date
- Status aktif
- Versi aturan

Modul ini tidak melakukan proses perhitungan nilai zakat secara langsung.

Perhitungan dilakukan oleh:

05-zakat-calculator.md

Modul ini menyediakan data dan aturan yang digunakan oleh Zakat Calculator.

---

## 2. Goals

Modul harus mampu:

1. Mengelola jenis zakat.
2. Mengelola kategori zakat.
3. Mengelola aturan dasar zakat.
4. Mengelola kadar zakat.
5. Mengelola nisab.
6. Mengelola haul.
7. Mengelola satuan perhitungan.
8. Mendukung aturan yang berubah berdasarkan waktu.
9. Menyimpan historical rule.
10. Mendukung multiple calculation basis.
11. Mendukung konfigurasi organisasi apabila diperlukan.
12. Menjaga agar aturan yang telah digunakan transaksi tidak berubah secara destruktif.
13. Menjadi sumber data untuk Zakat Calculator.
14. Menyediakan audit trail atas perubahan aturan.

---

# PRD 04B — CORE CONCEPT

## 3. Architecture

Struktur domain:

Zakat Category
↓
Zakat Type
↓
Zakat Rule
↓
Nisab
↓
Haul
↓
Rate
↓
Effective Period
↓
Calculation Engine

Contoh:

Zakat Mal
↓
Zakat Emas
↓
Rule Version 2026
↓
Nisab 85 Gram Emas
↓
Haul 1 Tahun Hijriah
↓
Rate 2.5%
↓
Effective 2026-01-01

---

## 4. Separation Principle

Modul Zakat hanya menyimpan definisi dan aturan.

Modul ini tidak menyimpan:

- Transaksi pembayaran
- Payment status
- Settlement
- Fund allocation
- Ledger entry
- Distribution

Pembagian responsibility:

03-muzaki.md

Mengelola pihak yang membayar zakat.

04-zakat.md

Mengelola definisi dan aturan zakat.

05-zakat-calculator.md

Menghitung kewajiban zakat berdasarkan aturan.

06-collection.md

Mengelola transaksi penghimpunan atau pembayaran.

---

# PRD 04C — ZAKAT CATEGORY

## 5. Entity

zakat_categories

Fields:

id

organization_id

code

name

description

status

sort_order

created_at

updated_at

deleted_at

---

## 6. Initial Category

Sistem minimal mendukung:

ZAKAT_FITRAH

ZAKAT_MAL

Kategori dapat dikembangkan sesuai kebutuhan sistem.

Contoh future category:

ZAKAT_PROFESSION

Namun secara default, klasifikasi tersebut dapat diperlakukan sebagai jenis atau subjenis di bawah struktur Zakat Mal sesuai konfigurasi dan kebijakan yang digunakan organisasi.

---

## 7. Category Code

Code harus:

- unique dalam organization scope atau global scope;
- uppercase;
- immutable setelah digunakan;
- tidak menggunakan sequence yang mudah berubah.

Contoh:

ZAKAT_FITRAH

ZAKAT_MAL

---

# PRD 04D — ZAKAT TYPE

## 8. Entity

zakat_types

Fields:

id

organization_id

zakat_category_id

code

name

description

calculation_method

status

sort_order

created_at

updated_at

deleted_at

---

## 9. Initial Zakat Types

Minimal sistem mendukung:

ZAKAT_FITRAH

ZAKAT_EMAS

ZAKAT_PERAK

ZAKAT_PENGHASILAN

ZAKAT_PERDAGANGAN

ZAKAT_PERTANIAN

ZAKAT_PETERNAKAN

ZAKAT_RIKAZ

Jenis lain dapat ditambahkan melalui konfigurasi.

---

## 10. Zakat Type Code

Code menggunakan format representatif dan tidak menggunakan dash.

Contoh:

ZAKATFITRAH

ZAKATEMAS

ZAKATPERAK

ZAKATPENGHASILAN

ZAKATPERDAGANGAN

ZAKATPERTANIAN

ZAKATPETERNAKAN

ZAKATRIKAZ

Code:

- unique;
- uppercase;
- immutable;
- tidak menggunakan angka urut sebagai identitas utama;
- digunakan untuk internal business reference;
- tidak digunakan sebagai primary key.

---

## 11. Calculation Method

Initial values:

FIXED

PERCENTAGE

NISAB_BASED

ASSET_BASED

INCOME_BASED

HARVEST_BASED

LIVESTOCK_BASED

CUSTOM

Nilai ini menentukan pendekatan umum.

Detail formula dikelola pada:

05-zakat-calculator.md

---

# PRD 04E — ZAKAT RULE

## 12. Purpose

Setiap Zakat Type dapat memiliki lebih dari satu aturan.

Hal ini diperlukan karena aturan atau parameter dapat berubah berdasarkan:

- Tahun
- Wilayah
- Organisasi
- Kebijakan
- Reference value
- Effective period

---

## 13. Entity

zakat_rules

Fields:

id

organization_id

zakat_type_id

rule_code

name

description

version

status

effective_from

effective_until

created_at

updated_at

deleted_at

---

## 14. Rule Code

Format:

{ZAKAT_TYPE_CODE}{YEAR}{VERSION}

Contoh:

ZAKATEMAS2026V1

ZAKATPENGHASILAN2026V1

ZAKATFITRAH2026V1

Rule Code:

- unique;
- immutable;
- human readable;
- tidak digunakan sebagai primary key.

---

## 15. Rule Status

DRAFT

ACTIVE

INACTIVE

EXPIRED

ARCHIVED

---

## 16. Rule Lifecycle

DRAFT

↓

REVIEW

↓

ACTIVE

↓

EXPIRED

atau:

ARCHIVED

Rule yang sudah digunakan untuk calculation snapshot tidak boleh dihapus.

---

# PRD 04F — ZAKAT RATE

## 17. Entity

zakat_rates

Fields:

id

zakat_rule_id

rate_type

rate_value

unit

effective_from

effective_until

created_at

updated_at

---

## 18. Rate Type

PERCENTAGE

FIXED_AMOUNT

FIXED_QUANTITY

CUSTOM

---

## 19. Rate Example

Zakat Emas:

rate_type:

PERCENTAGE

rate_value:

2.5

unit:

PERCENT

Zakat Fitrah:

rate_type:

FIXED_QUANTITY

rate_value:

2.5

unit:

KG

Nilai aktual dan metode konversi dapat dikonfigurasi sesuai rule yang berlaku.

---

# PRD 04G — NISAB MANAGEMENT

## 20. Purpose

Nisab adalah batas minimum harta atau nilai tertentu yang menjadi parameter kewajiban zakat untuk jenis zakat tertentu.

Sistem harus mendukung nisab yang:

- tetap;
- berdasarkan kuantitas;
- berdasarkan reference asset;
- berdasarkan nilai mata uang;
- dihitung dari external reference value.

---

## 21. Entity

zakat_nisabs

Fields:

id

zakat_rule_id

nisab_type

reference_type

reference_value

quantity

unit

currency

effective_from

effective_until

created_at

updated_at

---

## 22. Nisab Type

FIXED_AMOUNT

FIXED_QUANTITY

REFERENCE_ASSET

FORMULA_BASED

CUSTOM

---

## 23. Reference Type

GOLD

SILVER

CURRENCY

COMMODITY

CUSTOM

---

## 24. Example

Zakat Emas:

nisab_type:

REFERENCE_ASSET

reference_type:

GOLD

quantity:

85

unit:

GRAM

Nilai rupiah dari nisab tidak disimpan secara statis sebagai satu-satunya sumber kebenaran.

Nilai dapat diperoleh oleh Calculation Engine berdasarkan reference value yang berlaku.

---

# PRD 04H — HAUL MANAGEMENT

## 25. Purpose

Haul merupakan parameter waktu kepemilikan harta untuk jenis zakat tertentu.

Tidak semua jenis zakat menggunakan haul.

---

## 26. Entity

zakat_hauls

Fields:

id

zakat_rule_id

haul_type

duration

duration_unit

calendar_type

created_at

updated_at

---

## 27. Haul Type

NOT_REQUIRED

FIXED_PERIOD

CUSTOM

---

## 28. Duration Unit

DAY

MONTH

YEAR

---

## 29. Calendar Type

HIJRI

GREGORIAN

CUSTOM

---

## 30. Example

Zakat Emas:

haul_type:

FIXED_PERIOD

duration:

1

duration_unit:

YEAR

calendar_type:

HIJRI

---

# PRD 04I — UNIT MANAGEMENT

## 31. Purpose

Setiap parameter zakat dapat menggunakan satuan yang berbeda.

Sistem harus menggunakan standard unit.

---

## 32. Initial Unit

GRAM

KG

LITER

IDR

USD

PERCENT

HEAD

ITEM

CUSTOM

---

## 33. Unit Rule

Unit harus memiliki:

code

name

symbol

dimension

conversion_rule

Jika satuan membutuhkan konversi, conversion rule harus jelas dan dapat diaudit.

---

# PRD 04J — REFERENCE VALUE

## 34. Purpose

Beberapa zakat membutuhkan nilai referensi.

Contoh:

- Harga emas
- Harga perak
- Harga komoditas
- Nilai beras atau makanan pokok
- Harga pasar tertentu

Reference value tidak boleh langsung ditimpa.

Historical value harus tetap tersedia.

---

## 35. Entity

zakat_reference_values

Fields:

id

organization_id

reference_code

reference_type

name

value

currency

unit

source

effective_at

expires_at

status

created_at

updated_at

---

## 36. Reference Code

Contoh:

GOLD24K

GOLD

SILVER

RICE

CUSTOM

---

## 37. Reference Source

MANUAL

OFFICIAL

MARKET

API

OTHER

Jika menggunakan external API:

- source harus dicatat;
- timestamp harus dicatat;
- fallback harus tersedia;
- calculation harus menggunakan snapshot;
- perubahan external value tidak boleh mengubah historical calculation.

---

# PRD 04K — ZAKAT FITRAH CONFIGURATION

## 38. Purpose

Zakat Fitrah memiliki karakteristik berbeda dari Zakat Mal.

Sistem harus mendukung:

- pembayaran dalam bentuk quantity;
- pembayaran dalam bentuk nominal;
- konfigurasi makanan pokok;
- konfigurasi wilayah;
- konfigurasi periode.

---

## 39. Entity

zakat_fitrah_configurations

Fields:

id

zakat_rule_id

staple_type

quantity

unit

cash_equivalent

currency

region_id

effective_from

effective_until

status

created_at

updated_at

---

## 40. Staple Type

Contoh:

RICE

WHEAT

DATES

OTHER

---

## 41. Regional Configuration

Nilai Zakat Fitrah dapat berbeda berdasarkan wilayah.

Contoh:

Organization

↓

Region

↓

Zakat Fitrah Rule

↓

Staple Quantity

↓

Cash Equivalent

Sistem harus memilih konfigurasi paling spesifik yang tersedia.

Priority:

Organization + Region

↓

Organization

↓

Global Default

---

# PRD 04L — ZAKAT AGRICULTURE CONFIGURATION

## 42. Purpose

Zakat Pertanian dapat memiliki parameter berdasarkan:

- hasil panen;
- jenis komoditas;
- metode pengairan;
- nisab;
- rate.

---

## 43. Entity

zakat_agriculture_configurations

Fields:

id

zakat_rule_id

commodity_type

irrigation_type

minimum_quantity

quantity_unit

rate

created_at

updated_at

---

## 44. Irrigation Type

NATURAL

ARTIFICIAL

MIXED

CUSTOM

Formula final tetap ditentukan oleh Zakat Calculator berdasarkan rule aktif.

---

# PRD 04M — ZAKAT LIVESTOCK CONFIGURATION

## 45. Purpose

Zakat Peternakan dapat menggunakan threshold berdasarkan jumlah ternak.

---

## 46. Entity

zakat_livestock_configurations

Fields:

id

zakat_rule_id

livestock_type

minimum_quantity

maximum_quantity

zakat_quantity

zakat_unit

description

created_at

updated_at

---

## 47. Livestock Type

CAMEL

CATTLE

BUFFALO

GOAT

SHEEP

CUSTOM

Configuration harus mendukung tier.

Contoh:

Range A

↓

Minimum 40

Maximum 120

↓

Obligation tertentu

Rule detail tidak di-hardcode pada frontend.

---

# PRD 04N — ELIGIBILITY PARAMETERS

## 48. Purpose

Beberapa zakat membutuhkan parameter tambahan untuk menentukan apakah suatu calculation dapat dilakukan.

Parameter dapat berupa:

- Kepemilikan
- Tanggal kepemilikan
- Jenis aset
- Pendapatan
- Hutang yang diperhitungkan
- Biaya tertentu
- Metode pembayaran

---

## 49. Entity

zakat_rule_parameters

Fields:

id

zakat_rule_id

parameter_code

name

description

data_type

is_required

default_value

validation_rules

sort_order

created_at

updated_at

---

## 50. Data Type

TEXT

NUMBER

DECIMAL

CURRENCY

DATE

BOOLEAN

SELECT

MULTI_SELECT

JSON

---

## 51. Parameter Example

Zakat Penghasilan:

GROSS_INCOME

NET_INCOME

DEDUCTION

PERIOD

PAYMENT_METHOD

Zakat Pertanian:

HARVEST_QUANTITY

IRRIGATION_TYPE

COMMODITY

Zakat Emas:

GOLD_QUANTITY

OWNERSHIP_START_DATE

PURITY

---

# PRD 04O — RULE VERSIONING

## 52. Purpose

Aturan zakat harus memiliki versioning.

Rule lama tidak boleh diubah secara destruktif setelah digunakan.

Contoh:

ZAKATEMAS2026V1

berlaku:

2026-01-01

sampai:

2026-12-31

Jika terdapat perubahan:

ZAKATEMAS2026V2

dibuat sebagai rule baru.

---

## 53. Version Rule

Rule yang sudah:

ACTIVE

dan telah digunakan oleh calculation atau transaction:

- tidak boleh dihapus;
- tidak boleh diubah secara langsung untuk parameter material;
- perubahan signifikan harus menghasilkan version baru.

---

## 54. Material Changes

Contoh perubahan material:

- Nisab
- Rate
- Haul
- Formula
- Reference source
- Parameter wajib
- Unit
- Regional configuration

Perubahan material wajib menghasilkan version baru.

---

# PRD 04P — RULE RESOLUTION

## 55. Purpose

Sistem harus dapat menentukan rule yang tepat berdasarkan context.

Input:

Zakat Type

Organization

Region

Calculation Date

---

## 56. Rule Resolution Priority

Priority:

1. Organization + Region + Active Period
2. Organization + Active Period
3. Global + Region + Active Period
4. Global Default + Active Period

Jika lebih dari satu rule valid pada level yang sama:

SYSTEM ERROR

Rule conflict harus diselesaikan sebelum digunakan.

---

## 57. Effective Date Rule

Rule aktif jika:

effective_from <= calculation_date

dan:

effective_until kosong

atau:

calculation_date <= effective_until

---

# PRD 04Q — API SPECIFICATION

## 58. Zakat Category

GET /api/v1/zakat/categories

POST /api/v1/zakat/categories

GET /api/v1/zakat/categories/{id}

PATCH /api/v1/zakat/categories/{id}

DELETE /api/v1/zakat/categories/{id}

---

## 59. Zakat Type

GET /api/v1/zakat/types

POST /api/v1/zakat/types

GET /api/v1/zakat/types/{id}

PATCH /api/v1/zakat/types/{id}

POST /api/v1/zakat/types/{id}/activate

POST /api/v1/zakat/types/{id}/deactivate

---

## 60. Zakat Rule

GET /api/v1/zakat/rules

POST /api/v1/zakat/rules

GET /api/v1/zakat/rules/{id}

PATCH /api/v1/zakat/rules/{id}

POST /api/v1/zakat/rules/{id}/activate

POST /api/v1/zakat/rules/{id}/expire

POST /api/v1/zakat/rules/{id}/archive

---

## 61. Nisab

GET /api/v1/zakat/rules/{id}/nisab

POST /api/v1/zakat/rules/{id}/nisab

PATCH /api/v1/zakat/nisabs/{id}

---

## 62. Haul

GET /api/v1/zakat/rules/{id}/haul

POST /api/v1/zakat/rules/{id}/haul

PATCH /api/v1/zakat/hauls/{id}

---

## 63. Rate

GET /api/v1/zakat/rules/{id}/rates

POST /api/v1/zakat/rules/{id}/rates

PATCH /api/v1/zakat/rates/{id}

---

## 64. Parameters

GET /api/v1/zakat/rules/{id}/parameters

POST /api/v1/zakat/rules/{id}/parameters

PATCH /api/v1/zakat/rule-parameters/{id}

DELETE /api/v1/zakat/rule-parameters/{id}

---

## 65. Reference Value

GET /api/v1/zakat/reference-values

POST /api/v1/zakat/reference-values

GET /api/v1/zakat/reference-values/{id}

PATCH /api/v1/zakat/reference-values/{id}

POST /api/v1/zakat/reference-values/{id}/activate

POST /api/v1/zakat/reference-values/{id}/expire

---

## 66. Rule Resolution

GET /api/v1/zakat/rules/resolve

Query:

zakat_type

organization_id

region_id

calculation_date

Response:

resolved_rule

nisab

haul

rate

parameters

reference_values

---

# PRD 04R — PERMISSIONS

## 67. Permission Codes

zakat.view

zakat.category.manage

zakat.type.create

zakat.type.update

zakat.type.activate

zakat.type.deactivate

zakat.rule.create

zakat.rule.update

zakat.rule.activate

zakat.rule.expire

zakat.rule.archive

zakat.nisab.manage

zakat.haul.manage

zakat.rate.manage

zakat.parameter.manage

zakat.reference_value.view

zakat.reference_value.manage

zakat.rule.resolve

zakat.view_audit

---

## 68. Approval Permission

Organization dapat mengaktifkan approval workflow untuk perubahan rule.

Contoh:

zakat.rule.create

dan:

zakat.rule.approve

dipisahkan.

User yang membuat rule tidak dapat menyetujui rule sendiri jika segregation of duties diaktifkan.

---

# PRD 04S — AUDIT

## 69. Audit Events

Minimal:

zakat_category_created

zakat_category_updated

zakat_type_created

zakat_type_updated

zakat_type_activated

zakat_type_deactivated

zakat_rule_created

zakat_rule_updated

zakat_rule_activated

zakat_rule_expired

zakat_rule_archived

zakat_nisab_created

zakat_nisab_updated

zakat_haul_created

zakat_haul_updated

zakat_rate_created

zakat_rate_updated

zakat_parameter_created

zakat_parameter_updated

zakat_reference_value_created

zakat_reference_value_updated

zakat_reference_value_activated

zakat_rule_resolved

---

## 70. Audit Rule

Audit harus menyimpan:

actor

organization

action

entity_type

entity_id

request_id

timestamp

before

after

Untuk perubahan rule material, audit harus dapat menunjukkan:

previous version

new version

reason

---

# PRD 04T — UI REQUIREMENTS

## 71. Zakat Type List

Menggunakan Velzon DataTable.

Columns:

Code

Name

Category

Calculation Method

Active Rule

Status

Updated At

Actions

---

## 72. Zakat Type Detail

Tabs:

Overview

Rules

Nisab

Haul

Rates

Parameters

Reference Values

History

Audit

---

## 73. Rule Creation Wizard

Step 1:

Basic Information

↓

Step 2:

Calculation Method

↓

Step 3:

Nisab

↓

Step 4:

Haul

↓

Step 5:

Rate

↓

Step 6:

Parameters

↓

Step 7:

Effective Period

↓

Step 8:

Review

↓

Create Draft

---

## 74. Rule Activation

Flow:

DRAFT

↓

Validation

↓

Conflict Check

↓

Optional Approval

↓

ACTIVE

Sistem harus memeriksa:

- duplicate active rule;
- overlapping effective period;
- required parameter;
- valid nisab;
- valid rate;
- valid calculation method.

---

## 75. Rule History UI

User dapat melihat:

Rule Code

Version

Status

Effective From

Effective Until

Created By

Created At

Change Reason

Tidak boleh hanya menampilkan rule aktif.

Historical rule harus dapat diakses oleh user yang memiliki permission.

---

# PRD 04U — BUSINESS RULES

## 76. General Rules

1. Zakat Type harus memiliki code unik.
2. Code tidak boleh berubah setelah digunakan.
3. Rule menggunakan versioning.
4. Rule yang sudah digunakan tidak boleh dihapus permanen.
5. Perubahan material menghasilkan rule version baru.
6. Hanya satu rule dengan scope yang sama boleh menjadi aktif untuk context yang sama.
7. Effective period tidak boleh overlap pada scope yang sama.
8. Nisab dapat berbeda berdasarkan Zakat Type dan Rule Version.
9. Haul tidak wajib untuk semua Zakat Type.
10. Rate dapat berupa percentage, amount, atau quantity.
11. Reference value harus memiliki timestamp dan source.
12. Historical calculation harus menggunakan snapshot.
13. Zakat module tidak menyimpan transaction.
14. Calculation logic tidak di-hardcode di frontend.
15. Formula detail dikelola oleh Zakat Calculator.
16. Rule resolution dilakukan di backend.
17. Frontend tidak boleh menentukan rule final secara mandiri.

---

# PRD 04V — DATA INTEGRITY

## 77. Rule Conflict

Sistem harus menolak activation jika terdapat:

dua rule aktif

dengan:

- Zakat Type sama;
- Organization scope sama;
- Region scope sama;
- Effective period overlap.

---

## 78. Historical Integrity

Jika rule telah digunakan oleh:

Calculation

atau:

Collection Transaction

maka:

- rule tidak dapat dihapus;
- nisab tidak dapat diubah secara destruktif;
- rate tidak dapat diubah secara destruktif;
- historical record tetap menunjuk pada version yang digunakan.

---

# PRD 04W — ACCEPTANCE CRITERIA

- [ ] Zakat Category dapat dibuat.
- [ ] Zakat Type dapat dibuat.
- [ ] Zakat Type memiliki code immutable.
- [ ] Zakat Mal tersedia.
- [ ] Zakat Fitrah tersedia.
- [ ] Jenis Zakat dapat dikonfigurasi.
- [ ] Zakat Rule dapat dibuat.
- [ ] Rule memiliki version.
- [ ] Rule memiliki effective period.
- [ ] Nisab dapat dikonfigurasi.
- [ ] Haul dapat dikonfigurasi.
- [ ] Rate dapat dikonfigurasi.
- [ ] Parameter dinamis dapat dikonfigurasi.
- [ ] Reference value dapat disimpan.
- [ ] Source reference value dicatat.
- [ ] Zakat Fitrah configuration tersedia.
- [ ] Agriculture configuration tersedia.
- [ ] Livestock configuration tersedia.
- [ ] Rule resolution tersedia.
- [ ] Rule conflict dapat dideteksi.
- [ ] Overlapping rule tidak dapat diaktifkan.
- [ ] Historical rule tersedia.
- [ ] Rule yang sudah digunakan tidak dapat dihapus.
- [ ] Audit trail tersedia.
- [ ] Permission diterapkan.
- [ ] Automated test tersedia.

---

# PRD 04X — DEFINITION OF DONE

Modul Zakat dianggap selesai apabila:

1. Category Zakat dapat dikelola.
2. Type Zakat dapat dikelola.
3. Setiap Type memiliki code yang aman dan immutable.
4. Rule dapat dibuat dalam beberapa versi.
5. Nisab dapat dikonfigurasi.
6. Haul dapat dikonfigurasi.
7. Rate dapat dikonfigurasi.
8. Dynamic parameter dapat ditentukan.
9. Reference value dapat dikelola.
10. Effective period diterapkan.
11. Rule conflict dapat dicegah.
12. Historical rule dapat dipertahankan.
13. Zakat Fitrah dapat dikonfigurasi.
14. Zakat Pertanian dapat dikonfigurasi.
15. Zakat Peternakan dapat dikonfigurasi.
16. Rule dapat di-resolve berdasarkan context.
17. Audit trail tersedia.
18. Permission berjalan.
19. Automated test berhasil.
20. Modul siap digunakan oleh 05-zakat-calculator.md.

---

# END OF PRD MODULE 04 — ZAKAT