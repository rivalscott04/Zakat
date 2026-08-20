# PRD MODULE 19 — REPORTING

Project: ZETRA
Module: Reporting
Module Code: RPT
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 02-organization-amil.md
- 03-master-data.md
- 04-zakat-collection.md
- 05-infaq-sedekah.md
- 06-donation.md
- 07-fund-management.md
- 08-accounting-ledger.md
- 09-muzakki-management.md
- 10-assessment.md
- 11-program-management.md
- 12-distribution.md
- 14-bank-reconciliation.md
- 15-document-management.md
- 16-notification.md
- 17-audit-trail.md
- 18-transparency.md

Related Modules:

- All Financial Modules
- Transparency
- Audit Trail
- Notification

---

# PRD 19A — OVERVIEW

## 1. Purpose

Modul Reporting bertanggung jawab untuk menyediakan sistem pembuatan, pengelolaan, penyimpanan, export, dan distribusi laporan dalam ZETRA.

Reporting Module menjadi centralized reporting layer.

Semua module dapat menyediakan data sebagai:

Report Data Source.

Contoh:

Zakat Collection

↓

Reporting Data Source

Fund Management

↓

Reporting Data Source

Distribution

↓

Reporting Data Source

Accounting Ledger

↓

Reporting Data Source

Reporting Module

↓

Generate Report

↓

Preview

↓

Export

↓

Download

↓

Schedule

↓

Send Notification.

---

## 2. Goals

Modul harus mampu:

1. Menyediakan laporan standar.
2. Menyediakan laporan berdasarkan periode.
3. Mendukung custom filter.
4. Mendukung report template.
5. Mendukung preview laporan.
6. Mendukung export.
7. Mendukung report history.
8. Mendukung scheduled report.
9. Mendukung report delivery.
10. Mendukung report snapshot.
11. Mendukung report parameter.
12. Mendukung laporan per Organization.
13. Mendukung laporan financial.
14. Mendukung laporan collection.
15. Mendukung laporan distribution.
16. Mendukung laporan program.
17. Mendukung laporan beneficiary.
18. Mendukung laporan reconciliation.
19. Mendukung laporan audit.
20. Mendukung report access permission.

Versi awal tidak wajib:

- Visual Report Builder.
- Drag and Drop Report Builder.
- AI Generated Report.
- Embedded BI.
- Advanced Custom Formula.
- Pixel Perfect Designer.

---

# PRD 19B — CORE PRINCIPLE

## 3. Reporting is Not Source of Truth

Reporting Module bukan pemilik data transaksi.

Data tetap dimiliki oleh Source Module.

Contoh:

Collection Report

Source of Truth:

Collection Module.

Distribution Report

Source of Truth:

Distribution Module.

Financial Statement

Source of Truth:

Accounting Ledger.

Reporting Module bertanggung jawab untuk:

- Query.
- Aggregate.
- Filter.
- Transform.
- Format.
- Generate.
- Export.
- Schedule.

---

## 4. Report Snapshot

Laporan yang telah dihasilkan dapat memiliki Snapshot.

Tujuan:

- Historical consistency.
- Auditability.
- Reproducibility.
- Prevent data changes from altering old reports.

Contoh:

Laporan Bulanan Januari 2026.

Generated:

1 Februari 2026.

Jika terdapat perubahan data pada Maret 2026:

Laporan snapshot Januari tetap sama.

---

# PRD 19C — REPORT ENTITY

## 5. Entity

reports

Fields:

id

organization_id

report_number

report_code

name

description

category

report_type

data_source

status

created_by

created_at

updated_at.

---

## 6. Report Number

Format:

RPT{YEAR}{SEQUENCE}

Contoh:

RPT2026000001

RPT2026000002

Rules:

- unique;
- immutable;
- uppercase;
- tidak menggunakan dash;
- human readable.

---

## 7. Report Code

Report Code digunakan untuk identitas jenis laporan.

Contoh:

ZAKATCOLLECTION

FUNDPOSITION

DISTRIBUTIONSUMMARY

ASNAFDISTRIBUTION

PROGRAMPERFORMANCE

MUZAKKISUMMARY

MUSTAHIKSUMMARY

BANKRECONCILIATION

FINANCIALPOSITION

ACTIVITYSUMMARY

AUDITSUMMARY.

Rules:

- uppercase;
- descriptive;
- tidak menggunakan dash;
- stable;
- tidak boleh berubah setelah digunakan sebagai system report identifier.

---

# PRD 19D — REPORT CATEGORY

## 8. Initial Categories

COLLECTION

FUND

DISTRIBUTION

FINANCIAL

ACCOUNTING

MUZAKKI

MUSTAHIK

ASSESSMENT

PROGRAM

BANKING

RECONCILIATION

AUDIT

TRANSPARENCY

ORGANIZATION

OPERATIONAL

CUSTOM.

---

# PRD 19E — STANDARD REPORTS

## 9. Collection Reports

Minimal:

Zakat Collection Summary.

Zakat Collection Detail.

Collection by Fund Type.

Collection by Payment Method.

Collection by Period.

Collection by Organization Unit.

Collection Trend.

---

## 10. Fund Reports

Minimal:

Fund Position.

Fund Movement.

Fund Balance.

Fund Inflow.

Fund Outflow.

Fund Allocation.

Fund Utilization.

---

## 11. Distribution Reports

Minimal:

Distribution Summary.

Distribution Detail.

Distribution by Asnaf.

Distribution by Region.

Distribution by Program.

Distribution by Period.

Distribution by Fund Type.

Beneficiary Distribution Summary.

---

## 12. Financial Reports

Minimal:

Financial Position.

Fund Balance Summary.

Income and Distribution Summary.

Cash Movement.

Financial Transaction Summary.

Period Financial Summary.

Financial Statement structure dapat dikembangkan mengikuti kebijakan organisasi dan standar akuntansi yang diterapkan.

---

## 13. Muzakki Reports

Minimal:

Muzakki Summary.

Active Muzakki.

New Muzakki.

Muzakki Collection History.

Collection by Muzakki Category.

Muzakki Contribution Summary.

Sensitive information harus mengikuti permission dan privacy policy.

---

## 14. Mustahik Reports

Minimal:

Mustahik Summary.

Mustahik by Category.

Mustahik by Region.

Mustahik Assessment Summary.

Beneficiary Distribution Summary.

Mustahik identity tidak boleh otomatis diexport secara penuh tanpa permission.

---

## 15. Assessment Reports

Minimal:

Assessment Summary.

Assessment Status.

Assessment by Category.

Assessment Result Distribution.

Pending Assessment.

Approved Assessment.

Rejected Assessment.

---

## 16. Program Reports

Minimal:

Program Summary.

Program Performance.

Program Budget.

Program Fund Utilization.

Program Distribution.

Program Beneficiary Reach.

Program Progress.

---

## 17. Banking Reports

Minimal:

Bank Transaction Summary.

Matched Transaction.

Unmatched Transaction.

Reconciliation Summary.

Reconciliation Difference.

---

## 18. Audit Reports

Minimal:

Audit Activity Summary.

User Activity.

Security Event Summary.

Financial Activity Audit.

Critical Event Summary.

Data Change Summary.

---

# PRD 19F — REPORT PARAMETERS

## 19. Purpose

Setiap laporan dapat memiliki Parameter.

Contoh:

Period Start.

Period End.

Organization.

Fund Type.

Program.

Region.

Asnaf.

Status.

Payment Method.

---

## 20. Entity

report_parameters

Fields:

id

report_id

parameter_code

label

type

required

default_value

options_source

sort_order

created_at

updated_at.

---

## 21. Parameter Type

DATE

DATE_RANGE

SELECT

MULTI_SELECT

TEXT

NUMBER

BOOLEAN.

---

# PRD 19G — REPORT GENERATION

## 22. Generate Flow

User memilih:

Report

↓

Set Parameters

↓

Validate Permission

↓

Validate Parameters

↓

Load Data Source

↓

Apply Organization Scope

↓

Apply Filters

↓

Generate Data

↓

Create Report Snapshot

↓

Render Preview

↓

Export jika diperlukan.

---

# PRD 19H — REPORT SNAPSHOT

## 23. Entity

report_runs

Fields:

id

organization_id

run_number

report_id

parameters

snapshot_data

status

generated_by

generated_at

completed_at

failed_at

error_message

created_at

updated_at.

---

## 24. Run Number

Format:

RPR{YEAR}{SEQUENCE}

Contoh:

RPR2026000001.

---

## 25. Run Status

QUEUED

PROCESSING

COMPLETED

FAILED

CANCELLED.

---

# PRD 19I — REPORT EXPORT

## 26. Initial Export Format

CSV

XLSX

PDF.

---

## 27. Export Flow

Report Run Completed

↓

User Select Format

↓

Generate Export File

↓

Store File

↓

Document Management

↓

Create Download Reference.

---

## 28. Export Security

Export file harus:

- mengikuti permission;
- mengikuti organization scope;
- memiliki expiration jika menggunakan temporary link;
- tidak mengekspos sensitive data tanpa authorization;
- dicatat pada Audit Trail.

---

# PRD 19J — REPORT TEMPLATE

## 29. Purpose

Report dapat memiliki Template.

Template menentukan:

Title.

Layout.

Columns.

Grouping.

Sorting.

Footer.

Summary.

---

## 30. Entity

report_templates

Fields:

id

organization_id

template_code

name

report_id

configuration

status

created_by

created_at

updated_at.

---

## 31. Template Code

Contoh:

MONTHLYCOLLECTION

MONTHLYDISTRIBUTION

FINANCIALSUMMARY

PROGRAMSUMMARY.

Rules:

- uppercase;
- unique dalam organization;
- tidak menggunakan dash.

---

# PRD 19K — REPORT SCHEDULING

## 32. Purpose

Laporan dapat dijalankan otomatis.

Contoh:

Monthly Collection Report.

Schedule:

Setiap tanggal 1.

↓

Generate.

↓

Export PDF.

↓

Send Email.

---

## 33. Entity

report_schedules

Fields:

id

organization_id

report_id

name

frequency

schedule_configuration

parameters

output_format

recipient_configuration

status

last_run_at

next_run_at

created_by

created_at

updated_at.

---

## 34. Frequency

DAILY

WEEKLY

MONTHLY

QUARTERLY

YEARLY.

---

# PRD 19L — REPORT DELIVERY

## 35. Delivery Channel

Initial:

IN_APP

EMAIL.

Future:

WHATSAPP

WEBHOOK

CLOUD_STORAGE.

---

## 36. Integration

Report Delivery menggunakan:

Notification Module.

Flow:

Scheduled Report

↓

Generate

↓

Export

↓

Notification Event

↓

Notification Module

↓

Email / In App.

---

# PRD 19M — REPORT ACCESS CONTROL

## 37. Permission Principle

Tidak semua user dapat melihat semua laporan.

Contoh:

Finance User:

Financial Report.

Program Manager:

Program Report.

Auditor:

Audit Report.

Organization Admin:

Organization Scope.

System Administrator:

Cross Organization berdasarkan permission.

---

# PRD 19N — REPORT DATA VISIBILITY

## 38. Visibility Level

PUBLIC

INTERNAL

RESTRICTED

CONFIDENTIAL.

---

## 39. Visibility Rules

PUBLIC:

Dapat digunakan untuk Transparency setelah publication process.

INTERNAL:

User Organization sesuai permission.

RESTRICTED:

Role tertentu.

CONFIDENTIAL:

Explicit permission.

---

# PRD 19O — REPORT HISTORY

## 40. Purpose

Sistem menyimpan histori Report Run.

User dapat melihat:

Run Number.

Report.

Parameters.

Generated By.

Generated At.

Status.

Export History.

---

# PRD 19P — REPORT FAVORITE

## 41. User Favorite

User dapat menandai laporan sebagai:

Favorite.

Entity:

user_report_favorites.

Fields:

id

user_id

report_id

created_at.

---

# PRD 19Q — REPORT DASHBOARD

## 42. Dashboard

Reporting Dashboard menampilkan:

Available Reports.

Recently Generated.

Scheduled Reports.

Failed Reports.

Favorite Reports.

Quick Reports.

---

# PRD 19R — API SPECIFICATION

## 43. Reports

GET

/api/v1/reports

POST

/api/v1/reports

GET

/api/v1/reports/{id}

PATCH

/api/v1/reports/{id}

POST

/api/v1/reports/{id}/activate

POST

/api/v1/reports/{id}/deactivate.

---

## 44. Report Run

POST

/api/v1/reports/{id}/run

GET

/api/v1/report-runs

GET

/api/v1/report-runs/{id}

POST

/api/v1/report-runs/{id}/cancel.

---

## 45. Report Export

POST

/api/v1/report-runs/{id}/export

GET

/api/v1/report-exports/{id}

GET

/api/v1/report-exports/{id}/download.

---

## 46. Report Template

GET

/api/v1/report-templates

POST

/api/v1/report-templates

GET

/api/v1/report-templates/{id}

PATCH

/api/v1/report-templates/{id}

POST

/api/v1/report-templates/{id}/activate

POST

/api/v1/report-templates/{id}/deactivate.

---

## 47. Report Schedule

GET

/api/v1/report-schedules

POST

/api/v1/report-schedules

GET

/api/v1/report-schedules/{id}

PATCH

/api/v1/report-schedules/{id}

POST

/api/v1/report-schedules/{id}/activate

POST

/api/v1/report-schedules/{id}/deactivate

POST

/api/v1/report-schedules/{id}/run-now.

---

## 48. User Favorite

GET

/api/v1/reports/favorites

POST

/api/v1/reports/{id}/favorite

DELETE

/api/v1/reports/{id}/favorite.

---

# PRD 19S — PERMISSIONS

## 49. Permission Codes

report.view

report.create

report.update

report.delete

report.run

report.export

report.download

report.template.view

report.template.create

report.template.update

report.template.manage

report.schedule.view

report.schedule.create

report.schedule.update

report.schedule.manage

report.financial.view

report.collection.view

report.fund.view

report.distribution.view

report.muzakki.view

report.mustahik.view

report.assessment.view

report.program.view

report.banking.view

report.audit.view

report.confidential.view

report.cross_organization.view.

---

# PRD 19T — AUDIT EVENTS

## 50. Audit Events

Minimal:

report.created

report.updated

report.deleted

report.activated

report.deactivated

report.run.created

report.run.started

report.run.completed

report.run.failed

report.run.cancelled

report.export.created

report.export.downloaded

report.template.created

report.template.updated

report.template.activated

report.template.deactivated

report.schedule.created

report.schedule.updated

report.schedule.activated

report.schedule.deactivated

report.schedule.executed

report.favorite.added

report.favorite.removed.

---

# PRD 19U — UI REQUIREMENTS

## 51. Reporting Dashboard

Gunakan ZETRA Dashboard.

Sections:

Quick Reports

Favorite Reports

Recent Reports

Scheduled Reports

Report Categories.

Cards:

Reports Available

Generated Today

Scheduled Reports

Failed Reports.

---

## 52. Report Catalog

Gunakan Card atau DataTable.

Informasi:

Report Name

Report Code

Category

Description

Visibility

Last Generated.

Action:

Run Report.

Favorite.

View History.

---

## 53. Report Parameter Page

User memilih parameter sebelum menjalankan report.

Contoh:

Period:

01 Januari 2026

sampai

31 Januari 2026.

Fund Type:

All.

Program:

All.

Region:

All.

Button:

Generate Report.

---

## 54. Report Preview

Menampilkan:

Report Title

Period

Generated At

Generated By

Summary

Table Data

Charts apabila relevan.

Actions:

Export CSV.

Export XLSX.

Export PDF.

Save Template jika memiliki permission.

---

## 55. Report Run History

ZETRA DataTable.

Columns:

Run Number

Report

Parameters

Generated By

Generated At

Status

Actions.

---

## 56. Report Schedule Management

Fields:

Name.

Report.

Frequency.

Schedule.

Parameters.

Output Format.

Recipients.

Status.

Next Run.

Last Run.

---

# PRD 19V — PERFORMANCE REQUIREMENTS

## 57. Large Report

Report besar tidak boleh diproses langsung pada HTTP Request utama.

Gunakan:

Queue.

Flow:

Request Report

↓

Create Report Run

↓

QUEUED

↓

Queue Job

↓

PROCESSING

↓

Generate

↓

COMPLETED.

---

## 58. Report Timeout

Jika report gagal:

Status:

FAILED.

Error harus dicatat.

User dapat melakukan:

Retry.

---

## 59. Caching

Report data dapat menggunakan cache untuk:

Standard Dashboard Reports.

Frequently Requested Aggregates.

Public-safe data.

Cache tidak boleh menyebabkan:

Cross Organization Data Leakage.

---

# PRD 19W — BUSINESS RULES

## 60. General Rules

1. Reporting Module bukan Source of Truth.
2. Setiap Report memiliki Report Code.
3. Report Number dibuat untuk record laporan yang relevan.
4. Report Run memiliki Run Number.
5. Organization isolation wajib diterapkan.
6. Report hanya dapat mengakses data sesuai Organization Scope.
7. Permission wajib diperiksa sebelum menjalankan report.
8. Sensitive Report membutuhkan permission tambahan.
9. Report Parameter harus divalidasi.
10. Required Parameter tidak boleh kosong.
11. Invalid Date Range tidak boleh diproses.
12. Large Report harus menggunakan Queue.
13. Failed Report dapat di-retry.
14. Report Snapshot tidak boleh berubah setelah completed.
15. Historical Report tetap dapat ditelusuri.
16. Export harus mengikuti permission.
17. Download harus dicatat pada Audit Trail.
18. Export file dapat memiliki expiration.
19. Scheduled Report menggunakan Scheduler.
20. Scheduled Report Delivery menggunakan Notification Module.
21. Report Template mengikuti Organization Scope.
22. Report Visibility harus diterapkan.
23. Cross Organization Report membutuhkan explicit permission.
24. Cache harus dipisahkan berdasarkan Organization.
25. Data Confidential tidak boleh muncul dalam Public Report.
26. Semua aktivitas material dicatat dalam Audit Trail.

---

# PRD 19X — TESTING REQUIREMENTS

## 61. Unit Test

Minimal:

- Report Number Generation.
- Report Code Validation.
- Run Number Generation.
- Parameter Validation.
- Date Range Validation.
- Organization Scope.
- Collection Report Generation.
- Fund Report Generation.
- Distribution Report Generation.
- Financial Report Generation.
- Program Report Generation.
- Audit Report Generation.
- Report Snapshot.
- Report Export CSV.
- Report Export XLSX.
- Report Export PDF.
- Report Schedule.
- Report Favorite.
- Report Permission.
- Confidential Report Permission.
- Queue Processing.
- Failed Report Handling.
- Retry Handling.

---

## 62. Integration Test

Flow:

User memilih:

Monthly Collection Report.

↓

Set Period.

↓

Validate Permission.

↓

Create Report Run.

↓

Queue Report.

↓

Load Collection Data.

↓

Apply Organization Scope.

↓

Generate Snapshot.

↓

Render Report.

↓

COMPLETED.

↓

Export XLSX.

↓

Document Stored.

↓

Audit Event Created.

---

## 63. Security Test

Test:

- Cross Organization Report Access.
- Unauthorized Report Access.
- Unauthorized Confidential Report.
- Parameter Manipulation.
- SQL Injection.
- Export Data Leakage.
- Temporary Download Link Abuse.
- Expired Download Link.
- Scheduled Report Unauthorized Recipient.
- Cache Leakage.
- Report Snapshot Modification.
- Audit Bypass.

---

# PRD 19Y — ACCEPTANCE CRITERIA

- [ ] Report Catalog tersedia.
- [ ] Standard Reports tersedia.
- [ ] Collection Reports tersedia.
- [ ] Fund Reports tersedia.
- [ ] Distribution Reports tersedia.
- [ ] Financial Reports tersedia.
- [ ] Muzakki Reports tersedia.
- [ ] Mustahik Reports tersedia.
- [ ] Assessment Reports tersedia.
- [ ] Program Reports tersedia.
- [ ] Banking Reports tersedia.
- [ ] Audit Reports tersedia.
- [ ] Report Parameters tersedia.
- [ ] Report Preview tersedia.
- [ ] Report Run tersedia.
- [ ] Report Snapshot tersedia.
- [ ] CSV Export tersedia.
- [ ] XLSX Export tersedia.
- [ ] PDF Export tersedia.
- [ ] Report History tersedia.
- [ ] Report Template tersedia.
- [ ] Scheduled Report tersedia.
- [ ] Report Delivery tersedia.
- [ ] Favorite Report tersedia.
- [ ] Permission diterapkan.
- [ ] Visibility Level diterapkan.
- [ ] Organization isolation diterapkan.
- [ ] Queue untuk Large Report tersedia.
- [ ] Audit Trail Integration tersedia.
- [ ] Automated Test tersedia.

---

# PRD 19Z — DEFINITION OF DONE

Modul Reporting dianggap selesai apabila:

1. Report Catalog tersedia.
2. Standard Report dapat dijalankan.
3. Collection Report dapat dibuat.
4. Fund Report dapat dibuat.
5. Distribution Report dapat dibuat.
6. Financial Report dapat dibuat.
7. Program Report dapat dibuat.
8. Banking Report dapat dibuat.
9. Audit Report dapat dibuat.
10. Report Parameter dapat digunakan.
11. Parameter tervalidasi.
12. Report Preview tersedia.
13. Report Run dibuat otomatis.
14. Run Number dibuat otomatis.
15. Report Snapshot tersedia.
16. Historical Report tetap dapat ditelusuri.
17. Export CSV berjalan.
18. Export XLSX berjalan.
19. Export PDF berjalan.
20. Export dicatat pada Audit Trail.
21. Report Template tersedia.
22. Scheduled Report tersedia.
23. Scheduled Report dapat dijalankan otomatis.
24. Report Delivery terintegrasi dengan Notification Module.
25. Favorite Report tersedia.
26. Permission diterapkan.
27. Confidential Report dilindungi.
28. Organization isolation berjalan.
29. Large Report menggunakan Queue.
30. Failed Report dapat ditangani.
31. Automated Test berhasil.

---

# FUTURE DEVELOPMENT

Fitur berikut dapat dikembangkan pada versi selanjutnya:

- Visual Report Builder
- Drag and Drop Report Builder
- Custom Formula Builder
- Saved Report Filter
- Embedded Business Intelligence
- Metabase Integration
- Apache Superset Integration
- Custom Dashboard Builder
- Real Time Report
- AI Generated Report Summary
- AI Data Analysis
- Natural Language Query
- Automated Insight
- Report Comparison
- Benchmark Report
- Forecasting
- Predictive Analytics
- Interactive Report
- Drill Down Report
- Drill Through Report
- Public Report Sharing
- Report API
- Webhook Delivery
- Cloud Storage Delivery
- Multi Language Report
- Digital Signature
- Report Watermark
- Regulatory Report Template

---

# END OF PRD MODULE 19 — REPORTING