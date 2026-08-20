# PRD MODULE 11 — PROGRAM MANAGEMENT

Project: ZETRA
Module: Program Management
Module Code: PRG
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md
- 07-fund-management.md
- 09-mustahik.md
- 10-assessment.md

Related Modules:

- 12-distribution.md
- 13-monitoring-evaluation.md
- 14-reporting.md
- 08-accounting-ledger.md

---

# PRD 11A — OVERVIEW

## 1. Purpose

Modul Program Management bertanggung jawab untuk mengelola seluruh program penyaluran dan pemberdayaan dalam ZETRA.

Program menjadi wadah atau struktur utama untuk mengorganisasi kegiatan penyaluran dana kepada Mustahik.

Contoh program:

Zakat Pendidikan

Zakat Kesehatan

Zakat Ekonomi

Zakat Kemanusiaan

Zakat Dakwah

Zakat Sosial

Beasiswa

Modal Usaha

Bantuan Biaya Hidup

Bantuan Rumah Layak Huni

Bantuan Bencana

Program Management tidak melakukan pembayaran secara langsung.

Program Management bertanggung jawab terhadap:

- Perencanaan Program
- Target Program
- Budget Program
- Eligibility Criteria
- Beneficiary Enrollment
- Program Activity
- Program Lifecycle
- Program Monitoring
- Program Outcome

Actual fund movement dilakukan oleh:

Fund Management

Distribution

Accounting & Ledger

---

## 2. Goals

Modul harus mampu:

1. Membuat Program.
2. Mengelola kategori Program.
3. Mengelola periode Program.
4. Mengelola target Program.
5. Mengelola budget Program.
6. Menghubungkan Program dengan Fund.
7. Mengatur eligibility criteria.
8. Menghubungkan Program dengan Assessment.
9. Mengelola beneficiary enrollment.
10. Mengelola program activity.
11. Mengelola target beneficiary.
12. Mengelola kapasitas Program.
13. Mengelola status Program.
14. Mendukung Program multi-year.
15. Mendukung Program berulang.
16. Mendukung approval Program.
17. Mendukung monitoring Program.
18. Mendukung outcome.
19. Menyediakan audit trail.
20. Menjadi sumber data untuk Distribution.

---

# PRD 11B — CORE PRINCIPLE

## 3. Program Principle

Program bukan transaksi.

Program adalah struktur perencanaan dan pelaksanaan.

Contoh:

Program

↓

Zakat Pendidikan 2026

Program memiliki:

Budget

Target

Criteria

Beneficiary

Activities

Distribution

Outcome

---

## 4. Separation Principle

Program Management:

merencanakan dan mengelola program.

Distribution:

melakukan proses penyaluran.

Fund Management:

mengelola dana.

Accounting:

mencatat transaksi.

Program tidak boleh langsung mengubah saldo dana.

---

# PRD 11C — PROGRAM ENTITY

## 5. Entity

programs

Fields:

id

organization_id

program_code

name

short_name

description

category_id

program_type

start_date

end_date

target_beneficiary

status

visibility

created_by

created_at

updated_at

archived_at

---

## 6. Program Code

Format:

PRG{YEAR}{SEQUENCE}

Contoh:

PRG2026000001

PRG2026000002

PRG2026000003

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

# PRD 11D — PROGRAM CATEGORY

## 7. Entity

program_categories

Fields:

id

organization_id

category_code

name

description

parent_id

status

sort_order

created_at

updated_at

---

## 8. Initial Categories

EDUCATION

HEALTH

ECONOMIC

SOCIAL

HUMANITARIAN

DAKWAH

EMERGENCY

HOUSING

FOOD

OTHER

---

# PRD 11E — PROGRAM TYPE

## 9. Initial Types

ASSISTANCE

EMPOWERMENT

SCHOLARSHIP

EMERGENCY

DEVELOPMENT

SERVICE

CAMPAIGN

CUSTOM

---

## 10. Assistance

Program bantuan langsung.

Contoh:

Bantuan Biaya Hidup.

---

## 11. Empowerment

Program bertujuan meningkatkan kemandirian Mustahik.

Contoh:

Modal Usaha

Pelatihan

Peralatan Produksi

---

# PRD 11F — PROGRAM PERIOD

## 12. Purpose

Program dapat memiliki periode pelaksanaan.

Contoh:

Annual

Quarterly

Monthly

Custom

---

## 13. Entity

program_periods

Fields:

id

program_id

period_code

name

start_date

end_date

target_beneficiary

status

created_at

updated_at

---

## 14. Period Code

Contoh:

202601

202602

2026Q1

2026Q2

Program period harus dapat digunakan untuk:

Budget

Enrollment

Distribution

Reporting

---

# PRD 11G — PROGRAM BUDGET

## 15. Purpose

Program Budget digunakan untuk menentukan batas anggaran program.

Budget bukan actual fund movement.

Budget merupakan planning control.

---

## 16. Entity

program_budgets

Fields:

id

program_id

fund_id

period_id

budget_amount

currency

allocated_amount

committed_amount

disbursed_amount

remaining_amount

status

created_at

updated_at

---

## 17. Budget Formula

Budget Amount

-

Committed Amount

-

Disbursed Amount

=

Available Budget

---

## 18. Budget Status

DRAFT

ACTIVE

EXHAUSTED

SUSPENDED

CLOSED

---

## 19. Budget Rule

Program tidak boleh melakukan commitment melebihi:

Available Budget

kecuali terdapat permission khusus.

Budget harus dapat direkonsiliasi dengan:

Fund Management.

---

# PRD 11H — PROGRAM FUND

## 20. Purpose

Program dapat dihubungkan dengan satu atau lebih Fund.

Contoh:

Program Pendidikan

dapat menerima:

Zakat Fund

Infaq Fund

Sedekah Fund

Waqf Fund

sesuai konfigurasi organisasi.

---

## 21. Entity

program_funds

Fields:

id

program_id

fund_id

priority

status

created_at

updated_at

---

## 22. Fund Rule

Setiap Distribution yang berasal dari Program harus menentukan:

Fund Source.

Program hanya menentukan fund yang diperbolehkan.

Actual fund deduction dilakukan oleh:

Distribution Module.

---

# PRD 11I — PROGRAM ELIGIBILITY

## 23. Purpose

Program dapat memiliki eligibility criteria.

Criteria dapat menggunakan:

Mustahik Data

Asnaf

Assessment

Region

Age

Income

Household

Priority Score

Custom Criteria

---

## 24. Entity

program_eligibility_rules

Fields:

id

program_id

rule_code

rule_type

field

operator

value

weight

required

sort_order

status

created_at

updated_at

---

## 25. Rule Type

MUSTAHIK

ASSESSMENT

ASNAF

DEMOGRAPHIC

GEOGRAPHIC

SOCIOECONOMIC

CUSTOM

---

## 26. Operator

EQUALS

NOT_EQUALS

GREATER_THAN

LESS_THAN

GREATER_THAN_OR_EQUAL

LESS_THAN_OR_EQUAL

IN

NOT_IN

CONTAINS

EXISTS

---

# PRD 11J — ELIGIBILITY EVALUATION

## 27. Purpose

Sistem dapat mengevaluasi apakah Mustahik memenuhi Program Criteria.

Result:

ELIGIBLE

NOT_ELIGIBLE

PARTIALLY_ELIGIBLE

NEEDS_REVIEW

---

## 28. Evaluation Flow

Select Mustahik

↓

Load Program Criteria

↓

Load Mustahik Data

↓

Load Assessment

↓

Evaluate Rules

↓

Generate Result

↓

Manual Review jika diperlukan

↓

Enrollment

---

## 29. Rule

Eligibility Evaluation tidak otomatis melakukan enrollment.

User yang memiliki permission dapat:

Approve Enrollment

Reject Enrollment

Override Result

Override wajib memiliki:

Reason

Audit Trail

---

# PRD 11K — BENEFICIARY ENROLLMENT

## 30. Purpose

Beneficiary Enrollment menghubungkan Mustahik dengan Program.

---

## 31. Entity

program_enrollments

Fields:

id

program_id

mustahik_id

enrollment_number

eligibility_result

assessment_id

enrolled_at

enrolled_by

approved_by

approved_at

status

notes

created_at

updated_at

---

## 32. Enrollment Number

Format:

ENR{YEAR}{SEQUENCE}

Contoh:

ENR2026000001

ENR2026000002

---

## 33. Enrollment Status

PENDING

UNDER_REVIEW

APPROVED

ACTIVE

COMPLETED

SUSPENDED

REJECTED

WITHDRAWN

---

## 34. Enrollment Rule

Satu Mustahik tidak boleh memiliki lebih dari satu:

ACTIVE Enrollment

untuk Program yang sama dalam periode yang sama.

Kecuali Program mengizinkan multiple enrollment.

---

# PRD 11L — PROGRAM CAPACITY

## 35. Purpose

Program dapat memiliki batas penerima manfaat.

Field:

target_beneficiary

capacity_limit

active_enrollment_count

---

## 36. Capacity Rule

Jika:

Active Enrollment

>=

Capacity Limit

maka:

PROGRAM_FULL.

Enrollment baru masuk:

WAITLIST

jika fitur waitlist aktif.

---

# PRD 11M — WAITLIST

## 37. Entity

program_waitlists

Fields:

id

program_id

mustahik_id

assessment_id

priority_score

position

status

added_at

processed_at

created_at

updated_at

---

## 38. Waitlist Status

WAITING

OFFERED

ACCEPTED

EXPIRED

REMOVED

---

# PRD 11N — PROGRAM ACTIVITY

## 39. Purpose

Program dapat memiliki satu atau lebih aktivitas.

Contoh:

Program Modal Usaha

↓

Pelatihan

↓

Distribusi Modal

↓

Mentoring

↓

Monitoring

---

## 40. Entity

program_activities

Fields:

id

program_id

activity_code

name

description

activity_type

start_date

end_date

location

status

created_at

updated_at

---

## 41. Activity Type

TRAINING

DISTRIBUTION

MENTORING

VISIT

MONITORING

EVENT

WORKSHOP

OTHER

---

# PRD 11O — BENEFICIARY ACTIVITY

## 42. Purpose

Sistem dapat mencatat keterlibatan Mustahik dalam aktivitas.

Entity:

program_activity_participants

Fields:

id

activity_id

mustahik_id

enrollment_id

attendance_status

participation_status

notes

created_at

updated_at

---

## 43. Attendance Status

REGISTERED

ATTENDED

ABSENT

EXCUSED

---

# PRD 11P — PROGRAM TARGET

## 44. Entity

program_targets

Fields:

id

program_id

target_type

name

target_value

current_value

unit

period_id

created_at

updated_at

---

## 45. Target Type

BENEFICIARY

FINANCIAL

ACTIVITY

OUTPUT

OUTCOME

CUSTOM

---

## 46. Example

Target:

BENEFICIARY

Target Value:

1000

Current Value:

750

Progress:

75 Percent

---

# PRD 11Q — PROGRAM OUTPUT

## 47. Purpose

Program Output mengukur hasil langsung dari aktivitas.

Contoh:

Jumlah Mustahik menerima bantuan.

Jumlah peserta pelatihan.

Jumlah usaha menerima modal.

---

## 48. Entity

program_outputs

Fields:

id

program_id

output_code

name

target_value

actual_value

unit

period_id

status

created_at

updated_at

---

# PRD 11R — PROGRAM OUTCOME

## 49. Purpose

Program Outcome digunakan untuk mencatat dampak Program.

Contoh:

Pendapatan Mustahik meningkat.

Mustahik memperoleh pekerjaan.

Mustahik menjadi mandiri.

Mustahik keluar dari kategori penerima bantuan.

---

## 50. Entity

program_outcomes

Fields:

id

program_id

outcome_code

name

description

measurement_method

target_value

actual_value

unit

measurement_date

status

created_at

updated_at

---

# PRD 11S — PROGRAM STATUS

## 51. Status

DRAFT

PENDING_APPROVAL

ACTIVE

SUSPENDED

COMPLETED

CLOSED

ARCHIVED

CANCELLED

---

## 52. Status Flow

DRAFT

↓

PENDING_APPROVAL

↓

ACTIVE

↓

COMPLETED

↓

CLOSED

atau:

ACTIVE

↓

SUSPENDED

↓

ACTIVE

atau:

CANCELLED

---

# PRD 11T — PROGRAM APPROVAL

## 53. Purpose

Program dapat memerlukan approval sebelum aktif.

Approval dapat diterapkan pada:

Program

Budget

Major Budget Change

Program Closure

---

## 54. Approval Rule

Maker Checker dapat diaktifkan.

User yang membuat Program tidak boleh menyetujui Program sendiri apabila segregation of duties aktif.

---

# PRD 11U — PROGRAM BUDGET COMMITMENT

## 55. Purpose

Commitment digunakan untuk mencatat dana yang telah direncanakan untuk digunakan.

Contoh:

Program Budget:

Rp100.000.000

Beneficiary Approved:

Rp10.000.000

Maka:

Committed Amount:

Rp10.000.000

Actual Distribution belum terjadi.

---

## 56. Commitment Flow

Enrollment Approved

atau

Distribution Request Approved

↓

Check Available Budget

↓

Create Commitment

↓

Update Committed Amount

↓

Distribution

↓

Convert Commitment to Disbursed

---

# PRD 11V — PROGRAM DASHBOARD

## 57. Dashboard

Cards:

Active Programs

Total Budget

Committed Budget

Disbursed Amount

Remaining Budget

Target Beneficiaries

Active Beneficiaries

Completed Programs

---

## 58. Program Performance

Dashboard dapat menampilkan:

Budget Utilization

Beneficiary Progress

Activity Progress

Output Progress

Outcome Progress

---

# PRD 11W — API SPECIFICATION

## 59. Programs

GET

/api/v1/programs

POST

/api/v1/programs

GET

/api/v1/programs/{id}

PATCH

/api/v1/programs/{id}

POST

/api/v1/programs/{id}/submit

POST

/api/v1/programs/{id}/approve

POST

/api/v1/programs/{id}/activate

POST

/api/v1/programs/{id}/suspend

POST

/api/v1/programs/{id}/complete

POST

/api/v1/programs/{id}/close

---

## 60. Program Budget

GET

/api/v1/programs/{id}/budgets

POST

/api/v1/programs/{id}/budgets

PATCH

/api/v1/program-budgets/{id}

---

## 61. Eligibility

POST

/api/v1/programs/{id}/evaluate-eligibility

GET

/api/v1/programs/{id}/eligible-mustahiks

---

## 62. Enrollment

GET

/api/v1/programs/{id}/enrollments

POST

/api/v1/programs/{id}/enrollments

POST

/api/v1/program-enrollments/{id}/approve

POST

/api/v1/program-enrollments/{id}/reject

POST

/api/v1/program-enrollments/{id}/withdraw

---

## 63. Activities

GET

/api/v1/programs/{id}/activities

POST

/api/v1/programs/{id}/activities

PATCH

/api/v1/program-activities/{id}

---

## 64. Targets

GET

/api/v1/programs/{id}/targets

POST

/api/v1/programs/{id}/targets

PATCH

/api/v1/program-targets/{id}

---

# PRD 11X — PERMISSIONS

## 65. Permission Codes

program.view

program.create

program.update

program.delete

program.submit

program.approve

program.activate

program.suspend

program.complete

program.close

program.category.manage

program.budget.view

program.budget.create

program.budget.update

program.budget.approve

program.eligibility.view

program.eligibility.manage

program.enrollment.view

program.enrollment.create

program.enrollment.approve

program.enrollment.reject

program.enrollment.withdraw

program.activity.view

program.activity.create

program.activity.update

program.activity.manage

program.target.view

program.target.manage

program.output.manage

program.outcome.manage

program.export

program.audit.view

---

# PRD 11Y — AUDIT EVENTS

## 66. Audit Events

Minimal:

program_created

program_updated

program_submitted

program_approved

program_activated

program_suspended

program_completed

program_closed

program_cancelled

program_archived

program_budget_created

program_budget_updated

program_budget_approved

program_fund_added

program_fund_removed

program_eligibility_evaluated

program_eligibility_overridden

program_enrollment_created

program_enrollment_approved

program_enrollment_rejected

program_enrollment_withdrawn

program_waitlist_added

program_activity_created

program_activity_updated

program_target_created

program_target_updated

program_output_updated

program_outcome_updated

---

# PRD 11Z — UI REQUIREMENTS

## 67. Program List

ZETRA DataTable.

Columns:

Program Code

Program Name

Category

Type

Period

Budget

Beneficiaries

Status

Actions

---

## 68. Program Detail

Header:

Program Code

Program Name

Category

Status

Period

Budget Summary

Beneficiary Progress

Tabs:

Overview

Budget

Funds

Eligibility

Beneficiaries

Waitlist

Activities

Targets

Outputs

Outcomes

Distribution

Timeline

Audit

---

## 69. Program Creation

Steps:

Step 1

Basic Information

↓

Step 2

Category and Type

↓

Step 3

Period

↓

Step 4

Funds

↓

Step 5

Budget

↓

Step 6

Eligibility Criteria

↓

Step 7

Targets

↓

Save Draft

atau:

Submit for Approval

---

## 70. Beneficiary Management

Features:

Search Mustahik

Eligibility Evaluation

Assessment Reference

Priority Score

Enrollment

Approval

Waitlist

Withdrawal

History

---

# PRD 11AA — BUSINESS RULES

## 71. General Rules

1. Program Code harus unik.
2. Program memiliki lifecycle.
3. Program DRAFT tidak dapat menerima Distribution.
4. Program ACTIVE dapat menerima Enrollment.
5. Program dapat memiliki satu atau lebih Fund.
6. Program Budget adalah planning record.
7. Program Budget tidak langsung mengurangi Fund Balance.
8. Actual Fund Movement dilakukan melalui Distribution.
9. Eligibility harus dapat dievaluasi.
10. Eligibility Result dapat di-override dengan reason.
11. Mustahik harus memiliki Enrollment sebelum menjadi beneficiary aktif.
12. Satu Mustahik tidak boleh duplicate enrollment dalam Program dan periode yang sama.
13. Capacity harus diperiksa sebelum enrollment.
14. Waitlist dapat digunakan jika Program penuh.
15. Program dapat memiliki banyak Activity.
16. Program dapat memiliki Target.
17. Program dapat memiliki Output.
18. Program dapat memiliki Outcome.
19. Program yang CLOSED tidak dapat menerima enrollment baru.
20. Budget tidak boleh dilampaui tanpa permission.
21. Commitment harus dapat ditelusuri.
22. Program harus memiliki audit trail.
23. Organization isolation wajib diterapkan.
24. Permission diperiksa di backend.

---

# PRD 11AB — TESTING REQUIREMENTS

## 72. Unit Test

Minimal:

- Program Creation
- Program Code Generation
- Program Status Transition
- Program Approval
- Budget Creation
- Budget Calculation
- Available Budget Calculation
- Fund Association
- Eligibility Rule Evaluation
- Eligibility Override
- Enrollment Creation
- Duplicate Enrollment Prevention
- Capacity Validation
- Waitlist
- Activity Creation
- Target Progress Calculation
- Output Update
- Outcome Update
- Program Closure

---

## 73. Integration Test

Flow:

Create Program

↓

Configure Fund

↓

Configure Budget

↓

Configure Eligibility

↓

Activate Program

↓

Evaluate Mustahik

↓

Enrollment

↓

Approval

↓

Budget Commitment

↓

Distribution Module

↓

Update Disbursed Amount

↓

Activity

↓

Monitoring

↓

Outcome

↓

Program Closure

---

## 74. Security Test

Test:

- Cross organization program access;
- Unauthorized program approval;
- Budget manipulation;
- Eligibility override without permission;
- Duplicate enrollment;
- Distribution into inactive program;
- Budget overrun;
- Closed program modification;
- Audit bypass.

---

# PRD 11AC — ACCEPTANCE CRITERIA

- [ ] Program dapat dibuat.
- [ ] Program Code otomatis dibuat.
- [ ] Program Category tersedia.
- [ ] Program Type tersedia.
- [ ] Program Period tersedia.
- [ ] Program Lifecycle tersedia.
- [ ] Program Approval tersedia.
- [ ] Program Fund tersedia.
- [ ] Program Budget tersedia.
- [ ] Budget Calculation tersedia.
- [ ] Eligibility Criteria tersedia.
- [ ] Eligibility Evaluation tersedia.
- [ ] Eligibility Override tersedia.
- [ ] Beneficiary Enrollment tersedia.
- [ ] Duplicate Enrollment dicegah.
- [ ] Capacity tersedia.
- [ ] Waitlist tersedia.
- [ ] Program Activity tersedia.
- [ ] Program Target tersedia.
- [ ] Program Output tersedia.
- [ ] Program Outcome tersedia.
- [ ] Budget Commitment tersedia.
- [ ] Distribution integration tersedia.
- [ ] Audit Trail tersedia.
- [ ] Organization isolation diterapkan.
- [ ] Permission diterapkan.
- [ ] Automated Test tersedia.

---

# PRD 11AD — DEFINITION OF DONE

Modul Program Management dianggap selesai apabila:

1. Program dapat dibuat dan dikelola.
2. Program memiliki kode unik.
3. Program Category tersedia.
4. Program Type tersedia.
5. Program Period tersedia.
6. Program dapat dihubungkan dengan Fund.
7. Program Budget dapat dikelola.
8. Available Budget dapat dihitung.
9. Eligibility Criteria dapat dikonfigurasi.
10. Mustahik dapat dievaluasi terhadap Program.
11. Beneficiary dapat melakukan enrollment.
12. Duplicate Enrollment dicegah.
13. Capacity dan Waitlist berjalan.
14. Program Activity dapat dikelola.
15. Target dapat dipantau.
16. Output dapat dicatat.
17. Outcome dapat dicatat.
18. Budget Commitment dapat ditelusuri.
19. Program dapat terintegrasi dengan Distribution.
20. Program Lifecycle berjalan.
21. Audit Trail tersedia.
22. Organization isolation berjalan.
23. Permission berjalan.
24. Automated Test berhasil.

---

# END OF PRD MODULE 11 — PROGRAM MANAGEMENT