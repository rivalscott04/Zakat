# PRD MODULE 08 — ACCOUNTING & LEDGER

Project: ZETRA
Module: Accounting & Ledger
Module Code: ACC
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md
- 03-muzaki.md
- 04-zakat.md
- 05-zakat-calculator.md
- 06-collection.md
- 07-fund-management.md

---

# PRD 08A — OVERVIEW

## 1. Purpose

Modul Accounting & Ledger bertanggung jawab untuk menyediakan pencatatan keuangan yang terstruktur, dapat ditelusuri, immutable, dan dapat direkonsiliasi terhadap seluruh transaksi dalam ZETRA.

Modul ini menjadi financial record layer.

Accounting & Ledger menerima financial event dari modul lain, termasuk:

- Collection
- Payment
- Fund Management
- Distribution
- Refund
- Adjustment
- Opening Balance
- Closing Adjustment

Modul ini harus memastikan bahwa setiap transaksi keuangan yang telah diposting memiliki jejak pencatatan yang konsisten.

Fund Management mengelola posisi dan penggunaan dana.

Accounting & Ledger mengelola pencatatan keuangan dan journal record.

Keduanya tidak boleh dianggap sebagai modul yang sama.

---

## 2. Goals

Modul harus mampu:

1. Mengelola Chart of Accounts.
2. Mengelola Account Classification.
3. Mengelola Journal Entry.
4. Mendukung Double Entry Accounting.
5. Menjaga keseimbangan Debit dan Credit.
6. Mengelola Journal Line.
7. Mengelola Accounting Period.
8. Mendukung Opening Balance.
9. Mendukung Adjustment.
10. Mendukung Reversal.
11. Mendukung Closing Process.
12. Mendukung Ledger.
13. Menyediakan General Ledger.
14. Menyediakan Trial Balance.
15. Menyediakan Account Balance.
16. Menyediakan Journal History.
17. Mendukung posting dari event sistem.
18. Mendukung manual journal dengan kontrol.
19. Mendukung audit trail.
20. Mencegah perubahan journal yang telah posted.
21. Mendukung reconciliation dengan Fund Management.
22. Menjadi dasar laporan keuangan.

---

# PRD 08B — ACCOUNTING PRINCIPLE

## 3. Double Entry Principle

Setiap transaksi yang diposting harus memiliki:

Total Debit

=

Total Credit

Journal tidak dapat diposting apabila:

Total Debit

!=

Total Credit

---

## 4. Immutable Ledger Principle

Journal yang telah:

POSTED

tidak dapat diedit.

Koreksi dilakukan melalui:

REVERSAL

atau:

ADJUSTMENT JOURNAL

Historical record tidak boleh dihapus.

---

## 5. Event Driven Accounting

Modul lain dapat menghasilkan Accounting Event.

Contoh:

Collection Completed

↓

Accounting Event

↓

Determine Accounting Rule

↓

Generate Journal Entry

↓

Validate Debit Credit

↓

POSTED

Accounting Rule dapat dikonfigurasi berdasarkan jenis event.

---

# PRD 08C — CHART OF ACCOUNTS

## 6. Purpose

Chart of Accounts atau COA digunakan untuk mendefinisikan seluruh akun yang digunakan dalam pencatatan keuangan.

---

## 7. Entity

chart_of_accounts

Fields:

id

organization_id

account_code

account_name

account_type

account_category

parent_id

normal_balance

is_postable

status

description

created_at

updated_at

deleted_at

---

## 8. Account Code

Contoh:

1000

1100

1110

1200

2000

3000

4000

5000

Code harus:

- unique dalam organization;
- immutable setelah digunakan;
- tidak menggunakan dash;
- dapat mendukung hierarchical account;
- tidak digunakan sebagai primary key.

Primary key menggunakan:

ULID.

---

# PRD 08D — ACCOUNT TYPE

## 9. Initial Account Type

ASSET

LIABILITY

EQUITY

REVENUE

EXPENSE

FUND

CONTROL

MEMORANDUM

---

## 10. Normal Balance

ASSET:

DEBIT

EXPENSE:

DEBIT

LIABILITY:

CREDIT

EQUITY:

CREDIT

REVENUE:

CREDIT

Fund account dapat dikonfigurasi sesuai accounting policy organisasi.

---

# PRD 08E — ACCOUNT HIERARCHY

## 11. Parent Account

COA harus mendukung parent account.

Contoh:

1000

ASSET

↓

1100

CURRENT ASSET

↓

1110

CASH AND CASH EQUIVALENT

↓

1111

BANK ACCOUNT

Parent account:

is_postable = false

Child account:

is_postable = true

---

# PRD 08F — ACCOUNTING PERIOD

## 12. Entity

accounting_periods

Fields:

id

organization_id

period_code

name

start_date

end_date

status

closed_at

closed_by

created_at

updated_at

---

## 13. Period Code

Format:

{YEAR}{MONTH}

Contoh:

202601

202602

202612

Tidak menggunakan dash.

---

## 14. Period Status

OPEN

LOCKED

CLOSED

---

## 15. Period Rule

Journal hanya dapat diposting ke period:

OPEN

Journal pada period:

LOCKED

memerlukan permission khusus.

Journal pada period:

CLOSED

tidak dapat diposting.

---

# PRD 08G — JOURNAL ENTRY

## 16. Entity

journal_entries

Fields:

id

organization_id

journal_number

journal_date

accounting_period_id

journal_type

source_type

source_id

reference_number

description

status

reversal_of_id

created_by

posted_by

posted_at

created_at

updated_at

---

## 17. Journal Number

Format:

JRN{YEAR}{SEQUENCE}

Contoh:

JRN2026000001

JRN2026000002

JRN2026000003

Rules:

- unique;
- immutable;
- human readable;
- tidak menggunakan dash;
- tidak digunakan kembali.

---

# PRD 08H — JOURNAL TYPE

## 18. Initial Journal Type

SYSTEM

MANUAL

ADJUSTMENT

REVERSAL

OPENING

CLOSING

TRANSFER

REFUND

---

## 19. System Journal

SYSTEM Journal dibuat otomatis berdasarkan event dari modul lain.

Contoh:

COLLECTION

PAYMENT

FUND

DISTRIBUTION

REFUND

System Journal tidak dapat diedit secara manual.

---

## 20. Manual Journal

Manual Journal dapat dibuat oleh user dengan permission.

Manual Journal wajib memiliki:

Journal Date

Description

Reference

Journal Lines

Reason apabila diperlukan.

---

# PRD 08I — JOURNAL LINE

## 21. Entity

journal_lines

Fields:

id

journal_entry_id

line_number

account_id

description

debit_amount

credit_amount

currency

created_at

---

## 22. Journal Line Rule

Setiap line hanya boleh memiliki salah satu:

debit_amount

atau:

credit_amount

Tidak boleh keduanya memiliki nilai lebih dari nol.

Tidak boleh keduanya bernilai nol.

---

## 23. Journal Validation

Total:

SUM(debit_amount)

harus sama dengan:

SUM(credit_amount)

Jika tidak sama:

JOURNAL_NOT_BALANCED

Journal tidak dapat diposting.

---

# PRD 08J — JOURNAL STATUS

## 24. Status

DRAFT

PENDING_APPROVAL

APPROVED

POSTED

REVERSED

CANCELLED

---

## 25. Status Flow

DRAFT

↓

PENDING_APPROVAL

↓

APPROVED

↓

POSTED

Jika dikoreksi:

POSTED

↓

Create Reversal

↓

REVERSED

---

# PRD 08K — ACCOUNTING EVENT

## 26. Purpose

Accounting Event digunakan sebagai abstraction layer antara business event dan Journal Entry.

Business Module tidak langsung membuat Journal Line.

Business Module menghasilkan:

Accounting Event.

Accounting Module memproses event berdasarkan rule.

---

## 27. Entity

accounting_events

Fields:

id

organization_id

event_type

source_type

source_id

reference_number

event_date

payload

status

processed_at

journal_entry_id

created_at

updated_at

---

## 28. Event Status

PENDING

PROCESSING

PROCESSED

FAILED

IGNORED

---

# PRD 08L — ACCOUNTING RULE

## 29. Purpose

Accounting Rule menentukan bagaimana event diterjemahkan menjadi Journal Entry.

---

## 30. Entity

accounting_rules

Fields:

id

organization_id

rule_code

name

event_type

debit_account_id

credit_account_id

condition_data

priority

status

effective_from

effective_until

created_at

updated_at

---

## 31. Rule Code

Contoh:

COLLECTIONRECEIVED

PAYMENTSETTLED

FUNDALLOCATION

DISTRIBUTIONPAID

REFUNDCOMPLETED

Tidak menggunakan dash.

---

## 32. Example

Event:

COLLECTION_COMPLETED

Debit:

CASH_ACCOUNT

Credit:

ZAKAT_FUND_ACCOUNT

Jumlah:

Collection Amount

---

# PRD 08M — ACCOUNTING EVENT FLOW

## 33. Flow

Business Event

↓

Accounting Event Created

↓

Find Accounting Rule

↓

Validate Accounting Period

↓

Generate Journal Draft

↓

Validate Debit Credit

↓

Approval jika diperlukan

↓

Post Journal

↓

Update Ledger Projection

---

# PRD 08N — GENERAL LEDGER

## 34. Purpose

General Ledger adalah representasi seluruh aktivitas akun.

General Ledger dapat dibangun dari Journal Line yang telah:

POSTED

Ledger tidak boleh menerima transaksi langsung tanpa Journal Entry.

---

## 35. Ledger Projection

Entity optional:

general_ledger_entries

Fields:

id

organization_id

account_id

journal_entry_id

journal_line_id

journal_date

reference_number

description

debit_amount

credit_amount

running_balance

created_at

---

## 36. Ledger Rule

Ledger Projection dapat dibangun secara asynchronous.

Source of Truth:

Journal Entry

+

Journal Line

---

# PRD 08O — ACCOUNT BALANCE

## 37. Balance Calculation

Account Balance dihitung berdasarkan:

Opening Balance

+

Debit

-

Credit

atau mengikuti:

normal_balance

Formula akhir harus mempertimbangkan jenis akun.

---

## 38. Balance Snapshot

Entity optional:

account_balance_snapshots

Fields:

id

organization_id

account_id

accounting_period_id

opening_balance

debit_total

credit_total

closing_balance

calculated_at

---

# PRD 08P — OPENING BALANCE

## 39. Purpose

Opening Balance digunakan ketika organisasi mulai menggunakan ZETRA.

Opening Balance harus memiliki:

reference

effective_date

reason

approval

---

## 40. Opening Balance Flow

Create Opening Journal

↓

Validate

↓

Approve

↓

Post

↓

Create Opening Balance

---

# PRD 08Q — ADJUSTMENT JOURNAL

## 41. Purpose

Adjustment digunakan untuk koreksi pencatatan.

Adjustment tidak mengubah Journal sebelumnya.

Adjustment membuat Journal baru.

---

## 42. Adjustment Requirement

Wajib memiliki:

reason

reference

supporting_document apabila diperlukan.

Adjustment dapat membutuhkan:

maker

checker

approval.

---

# PRD 08R — REVERSAL

## 43. Purpose

Reversal digunakan untuk membalik Journal yang telah Posted.

---

## 44. Reversal Rule

Original Journal:

Debit A:

1000000

Credit B:

1000000

Reversal:

Debit B:

1000000

Credit A:

1000000

Reversal Journal harus menyimpan:

reversal_of_id

Original Journal tetap dipertahankan.

---

# PRD 08S — PERIOD CLOSING

## 45. Purpose

Period Closing digunakan untuk menutup periode akuntansi.

---

## 46. Closing Process

Validate:

All required journals posted

↓

Run Trial Balance

↓

Resolve Difference

↓

Create Closing Journal jika diperlukan

↓

Lock Period

↓

Close Period

---

## 47. Closed Period Rule

Period yang telah CLOSED:

- tidak dapat menerima journal baru;
- tidak dapat mengubah journal;
- koreksi dilakukan melalui periode berikutnya sesuai policy.

---

# PRD 08T — TRIAL BALANCE

## 48. Purpose

Trial Balance digunakan untuk memastikan keseimbangan saldo seluruh akun.

---

## 49. Trial Balance Data

Account Code

Account Name

Opening Balance

Debit

Credit

Closing Balance

---

## 50. Validation

Total Debit

harus sama dengan:

Total Credit

Jika tidak sama:

TRIAL_BALANCE_DIFFERENCE

---

# PRD 08U — FUND RECONCILIATION

## 51. Purpose

Accounting Ledger harus dapat direkonsiliasi dengan Fund Management.

Contoh:

Fund Management:

Zakat Fund Balance

Rp100.000.000

Accounting:

Zakat Fund Account

Rp100.000.000

Jika terdapat perbedaan:

RECONCILIATION_REQUIRED

---

## 52. Reconciliation Flow

Get Fund Balance

↓

Get Accounting Balance

↓

Compare

↓

Matched

atau:

Difference Found

↓

Investigation

↓

Adjustment jika valid

---

# PRD 08V — JOURNAL APPROVAL

## 53. Maker Checker

Organization dapat mengaktifkan approval untuk:

Manual Journal

Adjustment Journal

Opening Balance

Closing Journal

High Value Journal

User yang membuat Journal tidak boleh menyetujui Journal sendiri apabila segregation of duties aktif.

---

# PRD 08W — API SPECIFICATION

## 54. Chart of Accounts

GET

/api/v1/accounting/accounts

POST

/api/v1/accounting/accounts

GET

/api/v1/accounting/accounts/{id}

PATCH

/api/v1/accounting/accounts/{id}

---

## 55. Journal Entries

GET

/api/v1/accounting/journals

POST

/api/v1/accounting/journals

GET

/api/v1/accounting/journals/{id}

POST

/api/v1/accounting/journals/{id}/submit

POST

/api/v1/accounting/journals/{id}/approve

POST

/api/v1/accounting/journals/{id}/post

POST

/api/v1/accounting/journals/{id}/reverse

---

## 56. General Ledger

GET

/api/v1/accounting/general-ledger

Filters:

account_id

date_from

date_to

organization_id

---

## 57. Trial Balance

GET

/api/v1/accounting/trial-balance

Parameters:

accounting_period_id

date_from

date_to

---

## 58. Accounting Period

GET

/api/v1/accounting/periods

POST

/api/v1/accounting/periods

POST

/api/v1/accounting/periods/{id}/lock

POST

/api/v1/accounting/periods/{id}/close

---

## 59. Accounting Events

GET

/api/v1/accounting/events

POST

/api/v1/accounting/events/{id}/retry

---

# PRD 08X — PERMISSIONS

## 60. Permission Codes

accounting.view

accounting.account.view

accounting.account.create

accounting.account.update

accounting.journal.view

accounting.journal.create

accounting.journal.submit

accounting.journal.approve

accounting.journal.post

accounting.journal.reverse

accounting.adjustment.create

accounting.adjustment.approve

accounting.period.view

accounting.period.create

accounting.period.lock

accounting.period.close

accounting.ledger.view

accounting.trial_balance.view

accounting.reconciliation.view

accounting.reconciliation.create

accounting.export

accounting.audit.view

---

# PRD 08Y — AUDIT EVENTS

## 61. Audit Events

Minimal:

account_created

account_updated

account_deactivated

journal_created

journal_updated

journal_submitted

journal_approved

journal_posted

journal_reversed

journal_cancelled

journal_adjusted

accounting_event_created

accounting_event_processed

accounting_event_failed

period_created

period_locked

period_closed

trial_balance_generated

reconciliation_started

reconciliation_difference_found

reconciliation_resolved

---

## 62. Audit Data

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

# PRD 08Z — UI REQUIREMENTS

## 63. Accounting Dashboard

Cards:

Total Assets

Total Liabilities

Total Fund Balance

Current Period Debit

Current Period Credit

Unposted Journals

Pending Approval

Reconciliation Difference

---

## 64. Chart of Accounts

ZETRA DataTable.

Columns:

Account Code

Account Name

Account Type

Parent Account

Normal Balance

Postable

Status

Actions

---

## 65. Journal List

Columns:

Journal Number

Journal Date

Journal Type

Reference

Description

Debit

Credit

Status

Created By

Actions

---

## 66. Journal Detail

Header:

Journal Number

Date

Status

Reference

Description

Lines:

Account

Description

Debit

Credit

Total Debit

Total Credit

---

## 67. General Ledger

Columns:

Date

Reference

Journal Number

Description

Debit

Credit

Running Balance

---

## 68. Trial Balance

Columns:

Account Code

Account Name

Opening

Debit

Credit

Closing

Footer:

Total Debit

Total Credit

Difference

---

# PRD 08AA — BUSINESS RULES

## 69. General Rules

1. Semua Journal harus mengikuti double entry.
2. Total Debit harus sama dengan Total Credit.
3. Journal POSTED tidak dapat diedit.
4. Journal POSTED tidak dapat dihapus.
5. Koreksi menggunakan Reversal atau Adjustment.
6. Ledger hanya berasal dari Journal yang POSTED.
7. Business Module tidak membuat Journal Line secara langsung.
8. Business Module menghasilkan Accounting Event.
9. Accounting Rule menentukan Journal.
10. Account Code harus unik dalam Organization.
11. Parent Account tidak dapat menerima posting jika is_postable false.
12. Journal harus berada dalam Accounting Period yang valid.
13. Closed Period tidak dapat menerima Journal.
14. Manual Journal dapat membutuhkan approval.
15. Opening Balance harus memiliki approval.
16. Adjustment wajib memiliki reason.
17. Reversal wajib mereferensikan Journal asli.
18. Trial Balance harus seimbang.
19. Fund Balance harus dapat direkonsiliasi dengan Accounting.
20. Organization isolation wajib diterapkan.
21. Permission diperiksa di backend.
22. Semua aktivitas material harus diaudit.

---

# PRD 08AB — TESTING REQUIREMENTS

## 70. Unit Test

Minimal:

- Account Creation
- Account Code Validation
- Parent Account Validation
- Journal Creation
- Journal Balance Validation
- Debit Credit Equality
- Journal Posting
- Journal Immutability
- Reversal Journal
- Adjustment Journal
- Accounting Period Validation
- Closed Period Prevention
- Accounting Event Processing
- Accounting Rule Resolution
- Ledger Projection
- Account Balance
- Trial Balance
- Fund Reconciliation

---

## 71. Integration Test

Flow:

Collection Completed

↓

Fund Inflow

↓

Accounting Event

↓

Accounting Rule

↓

Journal Generated

↓

Journal Posted

↓

Ledger Updated

↓

Trial Balance Updated

↓

Fund Reconciliation

---

## 72. Security Test

Test:

- Cross organization journal access;
- Unauthorized posting;
- Journal modification after posted;
- Journal deletion after posted;
- Unbalanced journal;
- Posting to closed period;
- Duplicate accounting event;
- Duplicate journal generation;
- Unauthorized reversal;
- Audit bypass.

---

# PRD 08AC — ACCEPTANCE CRITERIA

- [ ] Chart of Accounts tersedia.
- [ ] Account hierarchy didukung.
- [ ] Account Type tersedia.
- [ ] Normal Balance tersedia.
- [ ] Accounting Period tersedia.
- [ ] Journal dapat dibuat.
- [ ] Journal mendukung multiple lines.
- [ ] Double Entry divalidasi.
- [ ] Journal dapat disetujui.
- [ ] Journal dapat diposting.
- [ ] Posted Journal immutable.
- [ ] Reversal tersedia.
- [ ] Adjustment tersedia.
- [ ] Accounting Event tersedia.
- [ ] Accounting Rule tersedia.
- [ ] General Ledger tersedia.
- [ ] Account Balance tersedia.
- [ ] Trial Balance tersedia.
- [ ] Opening Balance tersedia.
- [ ] Period Lock tersedia.
- [ ] Period Closing tersedia.
- [ ] Fund Reconciliation tersedia.
- [ ] Audit Trail tersedia.
- [ ] Organization isolation diterapkan.
- [ ] Permission diterapkan.
- [ ] Automated Test tersedia.

---

# PRD 08AD — DEFINITION OF DONE

Modul Accounting & Ledger dianggap selesai apabila:

1. Chart of Accounts dapat dikelola.
2. Account hierarchy berjalan.
3. Accounting Period dapat dibuat dan ditutup.
4. Journal dapat dibuat.
5. Journal Line dapat dibuat.
6. Double Entry selalu tervalidasi.
7. Journal yang Posted bersifat immutable.
8. Reversal dapat dilakukan.
9. Adjustment dapat dilakukan.
10. Accounting Event dapat diterima.
11. Accounting Rule dapat menghasilkan Journal.
12. General Ledger dapat ditampilkan.
13. Account Balance dapat dihitung.
14. Trial Balance dapat dihasilkan.
15. Opening Balance dapat dicatat.
16. Period Lock berjalan.
17. Closed Period tidak menerima transaksi baru.
18. Fund Balance dapat direkonsiliasi.
19. Audit Trail tersedia.
20. Organization isolation berjalan.
21. Permission berjalan.
22. Automated Test berhasil.
23. Modul siap menjadi sumber data untuk Reporting dan Financial Statement.

---

# END OF PRD MODULE 08 — ACCOUNTING & LEDGER