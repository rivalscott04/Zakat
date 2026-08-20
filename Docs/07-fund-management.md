# PRD MODULE 07 — FUND MANAGEMENT

Project: ZETRA
Module: Fund Management
Module Code: FND
Version: 0.1.0
Status: Implemented (Fund balance, movement, allocation, reservation, transfer, adjustment, and reconciliation core complete; Distribution remains downstream)

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md
- 03-muzaki.md
- 04-zakat.md
- 05-zakat-calculator.md
- 06-collection.md

---

# PRD 07A — OVERVIEW

## 1. Purpose

Modul Fund Management bertanggung jawab untuk mengelola dana yang telah diterima dan divalidasi dari proses Collection.

Modul ini menjadi pusat pengelolaan saldo dana berdasarkan:

- Jenis Dana
- Sumber Dana
- Restriction
- Zakat Type
- Organization
- Fund Category
- Allocation
- Available Balance
- Reserved Balance
- Distributed Balance

Fund Management harus menjaga prinsip utama:

Dana harus dapat ditelusuri dari sumber penerimaan sampai kepada proses penyaluran.

Collection menghasilkan Fund Inflow Event.

Fund Management menerima event tersebut dan mencatat pergerakan dana ke dalam fund yang sesuai.

Modul ini tidak melakukan pembayaran.

Modul ini tidak menentukan kewajiban zakat.

Modul ini tidak melakukan penyaluran kepada Mustahik.

Penyaluran dikelola oleh modul Distribution.

---

## 2. Goals

Modul harus mampu:

1. Mencatat dana masuk dari Collection.
2. Memisahkan dana berdasarkan jenis dan klasifikasi.
3. Menjaga segregasi dana.
4. Mengelola saldo dana.
5. Mengelola fund allocation.
6. Mengelola restricted fund.
7. Mengelola unrestricted fund apabila diizinkan.
8. Mengelola reserve.
9. Mengelola dana yang telah dialokasikan.
10. Mengelola dana yang telah didistribusikan.
11. Mendukung multiple fund source.
12. Mendukung fund transfer dengan kontrol.
13. Menyediakan fund movement history.
14. Menyediakan fund balance.
15. Mencegah penggunaan dana melebihi saldo.
16. Mendukung fund reservation.
17. Mendukung reconciliation.
18. Mendukung audit trail.
19. Menjadi sumber dana untuk Distribution.
20. Menjadi dasar Fund Reporting.

Fund accounting dan pemisahan dana penting untuk transparansi serta menjaga penggunaan dana sesuai peruntukannya. Prinsip pemisahan dan pelacakan dana zakat juga sejalan dengan praktik tata kelola zakat yang menekankan transparansi dan akuntabilitas. :contentReference[oaicite:0]{index=0}

---

# PRD 07B — CORE PRINCIPLE

## 3. Fund Flow

Collection

↓

Payment Valid

↓

Collection Completed

↓

Fund Inflow Event

↓

Fund Classification

↓

Fund Balance Updated

↓

Available Fund

↓

Reserve / Allocation

↓

Distribution

↓

Fund Outflow

↓

Fund Balance Updated

---

## 4. Fund Segregation Principle

Dana tidak boleh dianggap sebagai satu saldo global tanpa klasifikasi.

Minimal sistem harus dapat membedakan:

ZAKAT

INFAQ

SEDEKAH

AMIL

OPERASIONAL

NON_HALAL

OTHER

Versi awal ZETRA dapat fokus pada:

ZAKAT

Namun arsitektur harus mendukung penambahan jenis dana lain.

Setiap Fund Movement harus memiliki:

fund_id

fund_type

source_reference

amount

direction

organization_id

Tidak boleh ada perpindahan dana antar fund tanpa:

authorization

reason

audit trail

approval apabila diperlukan.

---

# PRD 07C — FUND ENTITY

## 5. Entity

funds

Fields:

id

organization_id

fund_code

name

fund_type

category

restriction_type

status

currency

opening_balance

current_balance

available_balance

reserved_balance

allocated_balance

distributed_balance

created_at

updated_at

deleted_at

---

## 6. Fund Code

Format:

{FUND_TYPE}{SCOPE}{YEAR}

Contoh:

ZAKATGENERAL2026

ZAKATFITRAH2026

ZAKATMAL2026

ZAKATEMAS2026

AMILGENERAL2026

Code:

- unique;
- uppercase;
- tidak menggunakan dash;
- representatif;
- immutable setelah digunakan;
- bukan primary key.

Primary key menggunakan:

ULID.

---

# PRD 07D — FUND TYPE

## 7. Initial Fund Type

ZAKAT

INFAQ

SEDEKAH

AMIL

WAKAF

NON_HALAL

OTHER

---

## 8. Initial Scope

Versi awal sistem wajib mendukung:

ZAKAT

Arsitektur harus dapat mendukung:

INFAQ

SEDEKAH

AMIL

dan fund type lainnya tanpa perubahan besar pada struktur database.

---

# PRD 07E — FUND CATEGORY

## 9. Purpose

Fund Category digunakan untuk klasifikasi lebih detail.

Contoh:

Fund Type:

ZAKAT

Category:

ZAKATFITRAH

ZAKATMAL

ZAKATPENGHASILAN

ZAKATPERTANIAN

ZAKATPETERNAKAN

Fund Type:

ZAKAT

tidak selalu berarti seluruh dana dapat digunakan secara bebas.

Setiap fund harus tetap mempertahankan metadata sumber dan peruntukan.

---

## 10. Entity

fund_categories

Fields:

id

organization_id

fund_type

code

name

description

status

created_at

updated_at

deleted_at

---

# PRD 07F — FUND RESTRICTION

## 11. Restriction Type

Initial values:

RESTRICTED

UNRESTRICTED

DESIGNATED

TEMPORARILY_RESTRICTED

CUSTOM

---

## 12. Restricted Fund

Restricted Fund hanya dapat digunakan untuk tujuan tertentu.

Contoh:

Dana Zakat Fitrah.

Atau:

Dana yang secara eksplisit ditujukan untuk program tertentu.

Fund restriction harus dapat menentukan:

allowed_usage

allowed_program

allowed_category

restriction_reason

effective_period

---

## 13. Entity

fund_restrictions

Fields:

id

fund_id

restriction_type

restriction_code

description

rule_data

effective_from

effective_until

status

created_at

updated_at

---

# PRD 07G — FUND BALANCE

## 14. Balance Components

Setiap Fund memiliki:

Current Balance

Available Balance

Reserved Balance

Allocated Balance

Distributed Balance

---

## 15. Balance Formula

Current Balance:

Total Inflow

-

Total Outflow

Reserved Balance:

Dana yang telah dicadangkan.

Available Balance:

Current Balance

-

Reserved Balance

-

Allocated Balance

Allocated Balance:

Dana yang telah ditetapkan untuk suatu program tetapi belum sepenuhnya didistribusikan.

---

## 16. Balance Integrity

Balance tidak boleh menjadi satu-satunya source of truth.

Semua perubahan saldo harus berasal dari:

Fund Movement

atau:

Ledger Event.

Saldo dapat disimpan sebagai projection untuk performa.

Namun Fund Movement tetap menjadi historical record.

---

# PRD 07H — FUND MOVEMENT

## 17. Purpose

Setiap perubahan dana harus dicatat sebagai Fund Movement.

Fund Movement bersifat immutable.

Tidak boleh mengubah nominal movement yang telah diposting.

Koreksi dilakukan melalui reversing movement atau adjustment movement.

---

## 18. Entity

fund_movements

Fields:

id

organization_id

fund_id

movement_number

movement_type

direction

amount

currency

source_type

source_id

reference_number

description

status

effective_at

created_by

created_at

---

## 19. Movement Number

Format:

FND{YEAR}{SEQUENCE}

Contoh:

FND2026000001

FND2026000002

Rules:

- unique;
- immutable;
- human readable;
- tidak menggunakan dash.

---

# PRD 07I — MOVEMENT TYPE

## 20. Initial Movement Type

COLLECTION_INFLOW

ALLOCATION

RESERVATION

RESERVATION_RELEASE

DISTRIBUTION

TRANSFER_IN

TRANSFER_OUT

ADJUSTMENT

REVERSAL

REFUND

CORRECTION

OPENING_BALANCE

CLOSING_ADJUSTMENT

---

## 21. Direction

IN

OUT

---

## 22. Example

Collection Completed:

movement_type:

COLLECTION_INFLOW

direction:

IN

Distribution:

movement_type:

DISTRIBUTION

direction:

OUT

---

# PRD 07J — FUND INFLOW

## 23. Source

Fund Inflow dapat berasal dari:

Collection

Import

Opening Balance

Adjustment

Transfer

External Integration

---

## 24. Collection Inflow

Flow:

Collection Completed

↓

Fund Inflow Event

↓

Determine Fund

↓

Validate Restriction

↓

Create Fund Movement

↓

Update Balance Projection

---

## 25. Fund Resolution

Fund ditentukan berdasarkan:

Organization

↓

Fund Type

↓

Fund Category

↓

Zakat Type

↓

Restriction

↓

Active Fund

Jika tidak ditemukan Fund yang sesuai:

FUND_NOT_FOUND

Collection tidak boleh dianggap selesai secara penuh pada layer Fund Management sebelum Fund Event berhasil diproses.

---

# PRD 07K — FUND ALLOCATION

## 26. Purpose

Allocation adalah proses penetapan sejumlah dana untuk tujuan tertentu.

Allocation belum berarti dana telah keluar.

Contoh:

Available Zakat Fund:

Rp100.000.000

↓

Allocate to Program A:

Rp30.000.000

↓

Allocated Balance:

Rp30.000.000

Available Balance:

Rp70.000.000

---

## 27. Entity

fund_allocations

Fields:

id

organization_id

allocation_number

fund_id

target_type

target_id

amount

currency

status

allocated_at

approved_by

approved_at

created_by

created_at

updated_at

---

## 28. Allocation Number

Format:

ALC{YEAR}{SEQUENCE}

Contoh:

ALC2026000001

ALC2026000002

---

## 29. Target Type

PROGRAM

DISTRIBUTION_PLAN

MUSTAHIK_GROUP

REGION

CAMPAIGN

CUSTOM

---

# PRD 07L — ALLOCATION STATUS

## 30. Status

DRAFT

PENDING_APPROVAL

APPROVED

ACTIVE

PARTIALLY_USED

FULLY_USED

EXPIRED

CANCELLED

---

## 31. Allocation Flow

DRAFT

↓

PENDING_APPROVAL

↓

APPROVED

↓

ACTIVE

↓

PARTIALLY_USED

↓

FULLY_USED

atau:

EXPIRED

atau:

CANCELLED

---

# PRD 07M — FUND RESERVATION

## 32. Purpose

Reservation digunakan untuk mengunci sebagian saldo.

Contoh:

Fund Available:

Rp100.000.000

Distribution Plan membutuhkan:

Rp40.000.000

↓

Reserve:

Rp40.000.000

↓

Available:

Rp60.000.000

Reserved:

Rp40.000.000

---

## 33. Entity

fund_reservations

Fields:

id

organization_id

reservation_number

fund_id

target_type

target_id

amount

currency

status

reserved_at

expires_at

released_at

reason

created_by

created_at

updated_at

---

## 34. Reservation Status

ACTIVE

PARTIALLY_USED

USED

RELEASED

EXPIRED

CANCELLED

---

# PRD 07N — FUND TRANSFER

## 35. Purpose

Fund Transfer digunakan untuk memindahkan dana antar fund apabila diizinkan.

Transfer antar fund merupakan proses sensitif.

Tidak semua fund dapat dipindahkan.

---

## 36. Entity

fund_transfers

Fields:

id

organization_id

transfer_number

source_fund_id

destination_fund_id

amount

currency

reason

status

requested_by

approved_by

transferred_at

created_at

updated_at

---

## 37. Transfer Rule

Transfer harus memeriksa:

- source fund tersedia;
- restriction source fund;
- destination fund valid;
- policy transfer;
- approval;
- sharia rule;
- organization rule.

Jika transfer tidak diizinkan:

FUND_TRANSFER_NOT_ALLOWED

---

## 38. Transfer Flow

Request

↓

Validation

↓

Approval

↓

Create TRANSFER_OUT

↓

Create TRANSFER_IN

↓

Update Both Fund Balances

---

# PRD 07O — FUND ADJUSTMENT

## 39. Purpose

Adjustment digunakan untuk koreksi.

Adjustment tidak boleh mengubah historical movement.

Adjustment harus membuat movement baru.

---

## 40. Entity

fund_adjustments

Fields:

id

organization_id

adjustment_number

fund_id

adjustment_type

amount

currency

reason

reference

status

approved_by

approved_at

created_by

created_at

updated_at

---

## 41. Adjustment Type

INCREASE

DECREASE

CORRECTION

REVERSAL

---

## 42. Adjustment Rule

Adjustment wajib memiliki:

reason

source reference apabila tersedia.

Adjustment dapat membutuhkan:

maker

checker

approval.

---

# PRD 07P — FUND RECONCILIATION

## 43. Purpose

Fund Balance harus dapat direkonsiliasi dengan:

Payment

Bank

Cash

Collection

Accounting

---

## 44. Entity

fund_reconciliations

Fields:

id

organization_id

reconciliation_number

fund_id

reconciliation_date

system_balance

external_balance

difference_amount

status

notes

created_by

reviewed_by

created_at

updated_at

---

## 45. Reconciliation Status

DRAFT

MATCHED

DIFFERENCE_FOUND

UNDER_REVIEW

RESOLVED

---

## 46. Reconciliation Rule

Jika:

system_balance

=

external_balance

maka:

MATCHED

Jika berbeda:

DIFFERENCE_FOUND

Perbedaan harus dapat ditindaklanjuti.

Tidak boleh langsung mengubah balance tanpa Adjustment.

---

# PRD 07Q — FUND AVAILABILITY CHECK

## 47. Purpose

Sebelum dana digunakan, sistem wajib melakukan availability check.

---

## 48. API

POST

/api/v1/funds/{id}/check-availability

Request:

amount

target_type

target_id

---

## 49. Response

available:

true

atau:

false

Dengan data:

current_balance

available_balance

reserved_balance

allocated_balance

requested_amount

---

# PRD 07R — FUND INTEGRITY

## 50. Negative Balance

Default:

Negative Balance tidak diizinkan.

Jika:

requested amount

>

available balance

maka:

INSUFFICIENT_FUND_BALANCE

---

## 51. Exception

Organization dapat mengaktifkan overdraft policy.

Namun untuk Restricted Fund:

default:

NOT_ALLOWED

Override membutuhkan:

special permission

reason

approval

audit.

---

# PRD 07S — DISTRIBUTION HANDOFF

## 52. Purpose

Fund Management menyediakan dana untuk Distribution.

Flow:

Distribution Request

↓

Determine Eligible Fund

↓

Check Restriction

↓

Check Available Balance

↓

Reserve Fund

↓

Distribution Approval

↓

Disbursement

↓

Create Fund Outflow

↓

Release Remaining Reservation

---

## 53. Fund Handoff Event

Event:

fund.reserved

fund.reservation.released

fund.allocated

fund.available

fund.insufficient

fund.distributed

---

# PRD 07T — FUND REPORTING

## 54. Fund Summary

Sistem harus menyediakan:

Opening Balance

Total Inflow

Total Allocation

Total Reserved

Total Distribution

Total Outflow

Adjustment

Closing Balance

---

## 55. Fund Activity Report

Filter:

Organization

Fund Type

Fund Category

Fund

Date Range

Region

Zakat Type

Movement Type

---

## 56. Transparency Principle

Setiap saldo harus dapat ditelusuri ke movement.

Contoh:

Current Fund Balance

↓

Fund Movement

↓

Collection Reference

↓

Payment Reference

↓

Muzaki Reference

Sebaliknya:

Distribution

↓

Fund Movement

↓

Fund Allocation

↓

Distribution Reference

Sistem dirancang untuk menghasilkan traceability dari penghimpunan hingga penggunaan dana. Pendekatan pengelolaan dana yang terdokumentasi dan dapat ditelusuri mendukung transparansi serta pengukuran efektivitas penyaluran. :contentReference[oaicite:1]{index=1}

---

# PRD 07U — API SPECIFICATION

## 57. Fund

GET

/api/v1/funds

POST

/api/v1/funds

GET

/api/v1/funds/{id}

PATCH

/api/v1/funds/{id}

---

## 58. Fund Balance

GET

/api/v1/funds/{id}/balance

Response:

current_balance

available_balance

reserved_balance

allocated_balance

distributed_balance

---

## 59. Fund Movements

GET

/api/v1/funds/{id}/movements

Filters:

movement_type

direction

date_from

date_to

source_type

---

## 60. Create Allocation

POST

/api/v1/funds/{id}/allocations

Request:

target_type

target_id

amount

reason

---

## 61. Approve Allocation

POST

/api/v1/fund-allocations/{id}/approve

---

## 62. Create Reservation

POST

/api/v1/funds/{id}/reservations

Request:

target_type

target_id

amount

expires_at

reason

---

## 63. Release Reservation

POST

/api/v1/fund-reservations/{id}/release

Request:

reason

---

## 64. Fund Transfer

POST

/api/v1/fund-transfers

Request:

source_fund_id

destination_fund_id

amount

reason

---

## 65. Fund Adjustment

POST

/api/v1/fund-adjustments

Request:

fund_id

adjustment_type

amount

reason

reference

---

## 66. Reconciliation

POST

/api/v1/funds/{id}/reconciliations

---

# PRD 07V — PERMISSIONS

## 67. Permission Codes

fund.view

fund.create

fund.update

fund.balance.view

fund.movement.view

fund.allocation.create

fund.allocation.approve

fund.allocation.cancel

fund.reservation.create

fund.reservation.release

fund.transfer.create

fund.transfer.approve

fund.adjustment.create

fund.adjustment.approve

fund.reconciliation.create

fund.reconciliation.review

fund.report.view

fund.export

fund.audit.view

---

# PRD 07W — APPROVAL WORKFLOW

## 68. Maker Checker Principle

Organization dapat mengaktifkan Maker Checker.

Contoh:

User A

Create Allocation

↓

User B

Approve Allocation

User yang membuat transaksi tidak boleh menyetujui transaksi sendiri apabila segregation of duties diaktifkan.

---

## 69. Approval Required For

Minimal dapat dikonfigurasi untuk:

Fund Allocation

Fund Transfer

Fund Adjustment

Large Reservation

Manual Opening Balance

Balance Correction

---

# PRD 07X — AUDIT EVENTS

## 70. Audit Events

Minimal:

fund_created

fund_updated

fund_opened

fund_closed

fund_inflow_created

fund_outflow_created

fund_balance_updated

fund_allocation_created

fund_allocation_approved

fund_allocation_cancelled

fund_reserved

fund_reservation_released

fund_transfer_requested

fund_transfer_approved

fund_transfer_completed

fund_adjustment_created

fund_adjustment_approved

fund_adjustment_posted

fund_reconciliation_created

fund_reconciliation_difference_found

fund_reconciliation_resolved

fund_insufficient_balance_detected

---

## 71. Audit Data

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

# PRD 07Y — UI REQUIREMENTS

## 72. Fund Dashboard

Cards:

Total Fund Balance

Available Balance

Reserved Balance

Allocated Balance

Distributed Amount

Pending Reconciliation

Recent Movement

---

## 73. Fund List

ZETRA DataTable.

Columns:

Fund Code

Fund Name

Fund Type

Category

Restriction

Current Balance

Available Balance

Status

Updated At

Actions

---

## 74. Fund Detail

Header:

Fund Name

Fund Code

Status

Current Balance

Available Balance

Tabs:

Overview

Movements

Allocations

Reservations

Transfers

Adjustments

Reconciliation

History

Audit

---

## 75. Fund Movement Timeline

Menampilkan:

Date

Movement Number

Movement Type

Direction

Amount

Source

Reference

Status

Running Balance

---

## 76. Allocation UI

Select:

Fund

↓

Target

↓

Amount

↓

Availability Check

↓

Reason

↓

Submit

↓

Approval

---

# PRD 07Z — BUSINESS RULES

## 77. General Rules

1. Semua perubahan dana harus menghasilkan Fund Movement.
2. Fund Movement bersifat immutable.
3. Historical movement tidak boleh dihapus.
4. Koreksi menggunakan Adjustment atau Reversal.
5. Fund harus dipisahkan berdasarkan klasifikasi.
6. Restricted Fund tidak boleh digunakan di luar restriction.
7. Negative Balance secara default tidak diizinkan.
8. Fund Allocation tidak langsung berarti Fund Outflow.
9. Reservation mengurangi Available Balance.
10. Distribution menghasilkan Fund Outflow.
11. Fund Transfer membutuhkan validasi.
12. Transfer antar restricted fund harus mengikuti policy.
13. Semua adjustment membutuhkan reason.
14. Adjustment dapat membutuhkan approval.
15. Fund Balance harus dapat direkonsiliasi.
16. Collection menjadi sumber utama Fund Inflow.
17. Distribution menjadi sumber utama Fund Outflow.
18. Organization isolation wajib diterapkan.
19. Permission diperiksa di backend.
20. Semua transaksi material harus diaudit.

---

# PRD 07AA — TESTING REQUIREMENTS

## 78. Unit Test

Minimal:

- Fund Creation
- Fund Code Generation
- Fund Inflow
- Fund Outflow
- Balance Calculation
- Available Balance Calculation
- Reservation
- Reservation Release
- Allocation
- Allocation Approval
- Fund Transfer
- Transfer Restriction
- Fund Adjustment
- Reversal
- Negative Balance Prevention
- Reconciliation

---

## 79. Integration Test

Flow:

Collection Completed

↓

Fund Inflow Event

↓

Create Fund Movement

↓

Update Fund Balance

↓

Create Allocation

↓

Reserve Fund

↓

Distribution

↓

Create Fund Outflow

↓

Update Balance

---

## 80. Security Test

Test:

- Cross organization fund access;
- Unauthorized fund transfer;
- Unauthorized adjustment;
- Negative balance attempt;
- Restricted fund misuse;
- Duplicate movement;
- Duplicate event processing;
- Double allocation;
- Double distribution;
- Audit bypass.

---

# PRD 07AB — ACCEPTANCE CRITERIA

- [ ] Fund dapat dibuat.
- [ ] Fund memiliki Fund Code.
- [ ] Fund Type dapat diklasifikasikan.
- [ ] Fund Category dapat digunakan.
- [ ] Fund Restriction dapat diterapkan.
- [ ] Fund Inflow dapat dicatat.
- [ ] Fund Outflow dapat dicatat.
- [ ] Fund Movement bersifat immutable.
- [ ] Current Balance tersedia.
- [ ] Available Balance tersedia.
- [ ] Reserved Balance tersedia.
- [ ] Allocation dapat dibuat.
- [ ] Allocation dapat disetujui.
- [ ] Reservation dapat dibuat.
- [ ] Reservation dapat dilepas.
- [ ] Fund Transfer tersedia.
- [ ] Fund Transfer memiliki approval.
- [ ] Fund Adjustment tersedia.
- [ ] Negative Balance dicegah.
- [ ] Reconciliation tersedia.
- [ ] Distribution dapat menggunakan Fund Reservation.
- [ ] Fund Reporting tersedia.
- [ ] Audit Trail tersedia.
- [ ] Organization isolation diterapkan.
- [ ] Permission diterapkan.
- [ ] Automated Test tersedia.

---

# PRD 07AC — DEFINITION OF DONE

Modul Fund Management dianggap selesai apabila:

1. Fund dapat dibuat dan diklasifikasikan.
2. Dana dapat dipisahkan berdasarkan Fund Type dan Category.
3. Fund Restriction dapat diterapkan.
4. Collection dapat menghasilkan Fund Inflow.
5. Fund Movement tercatat untuk setiap perubahan saldo.
6. Fund Balance dapat dihitung.
7. Available Balance dapat digunakan untuk validasi.
8. Fund Allocation dapat dilakukan.
9. Fund Reservation dapat dilakukan.
10. Fund Transfer dapat dikontrol.
11. Fund Adjustment tidak mengubah historical record.
12. Negative Balance dapat dicegah.
13. Fund dapat direkonsiliasi.
14. Distribution dapat menggunakan Fund yang valid.
15. Seluruh Fund Movement dapat ditelusuri.
16. Audit Trail tersedia.
17. Organization isolation berjalan.
18. Permission berjalan.
19. Automated Test berhasil.
20. Modul siap digunakan oleh Distribution Module.

---

# END OF PRD MODULE 07 — FUND MANAGEMENT
