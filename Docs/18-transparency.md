# PRD MODULE 18 — TRANSPARENCY

Project: Zakat OS
Module: Transparency
Module Code: TRP
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 02-organization-amil.md
- 04-zakat-collection.md
- 05-infaq-sedekah.md
- 06-donation.md
- 07-fund-management.md
- 08-accounting-ledger.md
- 11-program-management.md
- 12-distribution.md
- 17-audit-trail.md

Related Modules:

- 15-document-management.md
- 16-notification.md
- 19-reporting.md

---

# PRD 18A — OVERVIEW

## 1. Purpose

Modul Transparency bertanggung jawab untuk menyediakan informasi publik dan internal mengenai pengelolaan dana zakat, infak, sedekah, dan dana sosial lainnya.

Tujuan utama modul ini adalah:

Transparency

↓

Trust

↓

Accountability

↓

Traceability.

Transparency Module bukan Accounting Module.

Transparency Module mengambil data dari module sumber dan menyajikannya dalam bentuk yang mudah dipahami.

Contoh sumber data:

Collection

↓

Fund Management

↓

Distribution

↓

Accounting Ledger

↓

Transparency Layer

↓

Public Dashboard.

---

## 2. Goals

Modul harus mampu:

1. Menampilkan total dana terkumpul.
2. Menampilkan total dana tersalurkan.
3. Menampilkan saldo dana.
4. Menampilkan distribusi berdasarkan jenis dana.
5. Menampilkan distribusi berdasarkan program.
6. Menampilkan distribusi berdasarkan wilayah.
7. Menampilkan periode transaksi.
8. Menampilkan laporan publik.
9. Menampilkan ringkasan penggunaan dana.
10. Menampilkan progress program.
11. Menampilkan transparency dashboard.
12. Mendukung public access.
13. Mendukung internal transparency.
14. Mendukung privacy protection.
15. Mendukung publication workflow.
16. Mendukung data snapshot.
17. Mendukung audit reference.
18. Mendukung verification.
19. Mendukung public sharing link.
20. Mendukung embeddable widget di masa depan.

---

# PRD 18B — CORE PRINCIPLE

## 3. Transparency Without Exposing Sensitive Data

Transparansi tidak berarti seluruh data harus dibuka.

Data publik tidak boleh menampilkan:

- NIK.
- Nomor rekening penuh.
- Alamat lengkap.
- Nomor telepon.
- Password.
- Token.
- Credential.
- Data identitas sensitif.
- Data mustahik yang dapat membahayakan privasi.

Public Transparency menggunakan:

Aggregation

Masking

Anonymization.

---

## 4. Source of Truth

Transparency Module bukan sumber transaksi utama.

Source of Truth tetap berada pada module asal.

Contoh:

Zakat Collection

Source:

Zakat Collection Module.

Distribution

Source:

Distribution Module.

Balance

Source:

Fund Management dan Accounting Ledger.

Transparency Module hanya:

- membaca;
- mengagregasi;
- membuat snapshot;
- mempublikasikan data yang telah disetujui.

---

# PRD 18C — TRANSPARENCY SCOPE

## 5. Initial Scope

Transparency Dashboard minimal menampilkan:

Total Collection

Total Distribution

Available Fund

Distribution Ratio

Program Allocation

Fund Allocation

Geographic Distribution

Period Summary.

---

# PRD 18D — TRANSPARENCY SNAPSHOT

## 6. Purpose

Data publik tidak selalu mengambil data langsung dari transaksi real-time.

Sistem dapat membuat:

Transparency Snapshot.

Tujuannya:

- konsistensi;
- performa;
- auditability;
- historical reporting.

Entity:

transparency_snapshots.

Fields:

id

organization_id

snapshot_number

period_start

period_end

snapshot_type

data

status

generated_at

generated_by

approved_by

approved_at

published_at

created_at

updated_at.

---

## 7. Snapshot Number

Format:

TRP{YEAR}{SEQUENCE}

Contoh:

TRP2026000001

TRP2026000002

Rules:

- unique;
- immutable;
- uppercase;
- tidak menggunakan dash;
- human readable.

---

# PRD 18E — SNAPSHOT TYPE

## 8. Initial Snapshot Type

DAILY

MONTHLY

QUARTERLY

YEARLY

CUSTOM.

---

# PRD 18F — SNAPSHOT STATUS

## 9. Status

DRAFT

GENERATED

PENDING_APPROVAL

APPROVED

PUBLISHED

ARCHIVED

REVOKED.

---

# PRD 18G — PUBLICATION WORKFLOW

## 10. Flow

Source Data

↓

Generate Snapshot

↓

Validation

↓

Review

↓

Approval

↓

Publish

↓

Public Transparency Dashboard.

---

## 11. Publication Rule

Data tidak langsung menjadi publik.

Minimal flow:

GENERATED

↓

PENDING_APPROVAL

↓

APPROVED

↓

PUBLISHED.

---

# PRD 18H — COLLECTION TRANSPARENCY

## 12. Collection Summary

Public dapat melihat:

Total Collection.

Breakdown:

ZAKAT

INFAQ

SEDEKAH

DONATION

WAKAF apabila didukung module.

Contoh:

Total Collection

Rp10.000.000.000.

Zakat

Rp6.000.000.000.

Infak dan Sedekah

Rp3.000.000.000.

Donation

Rp1.000.000.000.

---

## 13. Collection Period

Filter:

Today

This Month

This Year

Custom Period.

---

# PRD 18I — FUND TRANSPARENCY

## 14. Fund Summary

Menampilkan:

Opening Balance

Total Inflow

Total Outflow

Available Balance.

Formula:

Available Balance

=

Opening Balance

+

Inflow

-

Outflow.

---

## 15. Fund Category

Contoh:

ZAKAT_MAL

ZAKAT_FITRAH

INFAQ

SEDEKAH

SOCIAL_DONATION

OPERATIONAL apabila diperbolehkan policy.

---

# PRD 18J — DISTRIBUTION TRANSPARENCY

## 16. Distribution Summary

Menampilkan:

Total Distributed

Total Beneficiaries

Total Programs

Distribution by Category

Distribution by Region.

---

## 17. Beneficiary Privacy

Public tidak menampilkan identitas lengkap Mustahik.

Contoh:

Bukan:

Ahmad Bin Abdullah

NIK:

123456789.

Tetapi:

Penerima Manfaat

Lombok Timur

Kategori:

Fakir.

Jumlah:

Rp2.000.000.

---

# PRD 18K — ASNAF TRANSPARENCY

## 18. Distribution by Asnaf

Zakat Distribution dapat ditampilkan berdasarkan kategori:

FAKIR

MISKIN

AMIL

MUALLAF

RIQAB

GHARIMIN

FISABILILLAH

IBNU_SABIL.

Tampilan dapat berupa:

Amount

Percentage

Beneficiary Count.

---

# PRD 18L — PROGRAM TRANSPARENCY

## 19. Program Summary

Setiap Program dapat memiliki halaman transparansi.

Menampilkan:

Program Name

Program Code

Description

Target Fund

Collected Fund

Distributed Fund

Beneficiary Target

Actual Beneficiary

Progress

Status.

---

## 20. Program Progress

Formula:

Distributed Fund

/

Target Fund

×

100%.

Contoh:

Target:

Rp100.000.000.

Distributed:

Rp75.000.000.

Progress:

75%.

---

# PRD 18M — GEOGRAPHIC TRANSPARENCY

## 21. Distribution by Region

Menampilkan data berdasarkan:

Province

Regency

District

sesuai scope Organization.

Public view tidak menampilkan alamat detail Mustahik.

---

# PRD 18N — TRANSPARENCY REPORT

## 22. Report

Public Transparency Report dapat dibuat berdasarkan:

Monthly

Quarterly

Yearly

Custom Period.

Isi minimal:

Organization Summary

Collection Summary

Fund Summary

Distribution Summary

Program Summary

Beneficiary Summary

Financial Summary

Important Notes.

---

# PRD 18O — REPORT PUBLICATION

## 23. Publication

Report dapat memiliki:

Report Number

Publication Date

Period

Status.

Entity:

transparency_reports.

Fields:

id

organization_id

report_number

title

period_start

period_end

report_type

snapshot_id

document_id

status

published_at

published_by

created_at

updated_at.

---

## 24. Report Number

Format:

RPT{YEAR}{SEQUENCE}

Contoh:

RPT2026000001.

---

# PRD 18P — PUBLIC DASHBOARD

## 25. Public Dashboard

Public Dashboard dapat diakses tanpa login apabila Organization mengaktifkan Public Transparency.

Route contoh:

/transparency/{organization-slug}

Menampilkan:

Organization Profile

Total Collection

Total Distribution

Available Fund

Program Summary

Distribution by Category

Distribution by Region

Latest Reports

Last Updated.

---

## 26. Public Data Rule

Public Dashboard hanya mengambil data dengan status:

PUBLISHED.

DRAFT tidak boleh dapat diakses publik.

APPROVED tetapi belum PUBLISHED juga tidak boleh muncul.

---

# PRD 18Q — INTERNAL TRANSPARENCY

## 27. Internal Transparency

User internal dapat melihat informasi lebih detail sesuai permission.

Contoh:

Finance Manager dapat melihat:

Fund Balance

Reconciliation Status

Ledger Summary.

Auditor dapat melihat:

Audit Reference

Snapshot History

Publication History.

Public hanya melihat:

Aggregated Data.

---

# PRD 18R — DATA VERIFICATION

## 28. Verification

Snapshot harus memiliki validation process.

Sistem memeriksa:

- Source Data Available.
- Period Valid.
- Collection Valid.
- Distribution Valid.
- Balance Calculation.
- Fund Consistency.

---

## 29. Verification Status

VALID

WARNING

INVALID.

Jika:

INVALID

Snapshot tidak dapat dipublish.

---

# PRD 18S — TRANSPARENCY METRICS

## 30. Core Metrics

Collection Rate.

Distribution Rate.

Fund Utilization.

Program Progress.

Beneficiary Reach.

Fund Balance.

---

## 31. Distribution Rate

Formula:

Total Distribution

/

Total Collection

×

100%.

---

## 32. Fund Utilization

Formula:

Total Distribution

/

Available Fund

×

100%.

---

# PRD 18T — PUBLIC TRANSACTION TRACE

## 33. Optional Transparency Reference

Setiap transaksi publik dapat memiliki:

Transparency Reference.

Contoh:

TRX202600001.

Reference dapat digunakan untuk:

- membuktikan transaksi tercatat;
- verifikasi receipt;
- transparency lookup.

Data yang ditampilkan tetap mengikuti privacy policy.

---

## 34. Public Verification

Endpoint contoh:

/transparency/verify/{reference}

Menampilkan:

Transaction Type

Amount

Date

Status

Organization.

Tidak menampilkan:

Full Donor Identity.

Full Beneficiary Identity.

Sensitive Information.

---

# PRD 18U — TRANSPARENCY CHANGE LOG

## 35. Publication History

Setiap perubahan publikasi harus dicatat.

Contoh:

Snapshot Published.

↓

Audit Event.

Jika Snapshot direvoke:

Snapshot Revoked.

↓

Reason Required.

↓

Audit Event.

---

# PRD 18V — API SPECIFICATION

## 36. Transparency Dashboard

GET

/api/v1/transparency/dashboard

GET

/api/v1/transparency/summary

GET

/api/v1/transparency/collection

GET

/api/v1/transparency/distribution

GET

/api/v1/transparency/funds

GET

/api/v1/transparency/programs

GET

/api/v1/transparency/regions.

---

## 37. Snapshots

GET

/api/v1/transparency/snapshots

POST

/api/v1/transparency/snapshots

GET

/api/v1/transparency/snapshots/{id}

POST

/api/v1/transparency/snapshots/{id}/generate

POST

/api/v1/transparency/snapshots/{id}/validate

POST

/api/v1/transparency/snapshots/{id}/submit

POST

/api/v1/transparency/snapshots/{id}/approve

POST

/api/v1/transparency/snapshots/{id}/publish

POST

/api/v1/transparency/snapshots/{id}/revoke.

---

## 38. Reports

GET

/api/v1/transparency/reports

POST

/api/v1/transparency/reports

GET

/api/v1/transparency/reports/{id}

POST

/api/v1/transparency/reports/{id}/publish

POST

/api/v1/transparency/reports/{id}/archive.

---

## 39. Public API

GET

/api/public/transparency/{organization}

GET

/api/public/transparency/{organization}/summary

GET

/api/public/transparency/{organization}/programs

GET

/api/public/transparency/{organization}/reports

GET

/api/public/transparency/verify/{reference}.

Public API wajib menggunakan:

Rate Limit.

Caching.

Published Data Only.

---

# PRD 18W — PERMISSIONS

## 40. Permission Codes

transparency.view

transparency.dashboard.view

transparency.snapshot.view

transparency.snapshot.create

transparency.snapshot.generate

transparency.snapshot.validate

transparency.snapshot.submit

transparency.snapshot.approve

transparency.snapshot.publish

transparency.snapshot.revoke

transparency.report.view

transparency.report.create

transparency.report.publish

transparency.report.archive

transparency.settings.manage

transparency.public.manage

transparency.export

transparency.audit.view.

---

# PRD 18X — AUDIT EVENTS

## 41. Audit Events

Minimal:

transparency.snapshot.created

transparency.snapshot.generated

transparency.snapshot.validated

transparency.snapshot.validation_failed

transparency.snapshot.submitted

transparency.snapshot.approved

transparency.snapshot.published

transparency.snapshot.revoked

transparency.report.created

transparency.report.published

transparency.report.archived

transparency.public_dashboard.enabled

transparency.public_dashboard.disabled

transparency.transaction.verified

transparency.settings.updated.

---

# PRD 18Y — UI REQUIREMENTS

## 42. Transparency Dashboard

Gunakan Velzon Dashboard.

Cards:

Total Collection

Total Distribution

Available Fund

Distribution Rate

Active Programs

Beneficiary Count.

---

## 43. Charts

Initial charts:

Collection by Fund Type.

Distribution by Asnaf.

Distribution by Program.

Distribution by Region.

Collection vs Distribution.

Period Trend.

---

## 44. Snapshot List

Velzon DataTable.

Columns:

Snapshot Number

Period

Type

Generated At

Validation Status

Status

Published At

Actions.

---

## 45. Snapshot Detail

Header:

Snapshot Number

Period

Status

Validation Result.

Tabs:

Overview

Collection

Funds

Distribution

Programs

Regions

Validation

Publication History

Audit.

---

## 46. Public Dashboard UI

Public UI tidak harus mengikuti tampilan internal Velzon.

Public UI harus:

- mobile responsive;
- ringan;
- mudah dipahami;
- cepat;
- accessible;
- SEO friendly.

Sections:

Hero Transparency Summary

Key Metrics

Collection

Fund Allocation

Distribution

Programs

Regions

Reports

Last Updated.

---

# PRD 18Z — BUSINESS RULES

## 47. General Rules

1. Transparency Module bukan Source of Truth transaksi.
2. Data berasal dari module sumber.
3. Public Data menggunakan snapshot atau published aggregate.
4. Sensitive Data tidak boleh dipublikasikan.
5. NIK tidak boleh dipublikasikan.
6. Nomor rekening penuh tidak boleh dipublikasikan.
7. Nomor telepon tidak boleh dipublikasikan.
8. Alamat detail Mustahik tidak boleh dipublikasikan.
9. Password, Token, Secret, dan Credential tidak boleh dipublikasikan.
10. Beneficiary identity harus mengikuti privacy policy.
11. Hanya Snapshot VALID yang dapat diajukan untuk approval.
12. Hanya Snapshot APPROVED yang dapat dipublish.
13. Hanya data PUBLISHED yang tersedia pada Public API.
14. Snapshot yang sudah PUBLISHED tidak boleh diedit.
15. Perubahan harus membuat Snapshot baru.
16. Revoke Publication membutuhkan reason.
17. Publication dan Revocation harus dicatat dalam Audit Trail.
18. Public API harus menggunakan Rate Limit.
19. Public Dashboard menggunakan caching.
20. Organization hanya dapat melihat dan mengelola datanya sendiri.
21. Internal Transparency mengikuti Permission.
22. Financial calculation harus berasal dari Source of Truth.
23. Invalid Snapshot tidak dapat dipublish.
24. Public Transaction Verification tidak boleh membuka data sensitif.
25. Semua aktivitas material harus dicatat pada Audit Trail.

---

# PRD 18AA — TESTING REQUIREMENTS

## 48. Unit Test

Minimal:

- Snapshot Number Generation.
- Snapshot Generation.
- Collection Aggregation.
- Distribution Aggregation.
- Fund Balance Calculation.
- Distribution Rate Calculation.
- Program Progress Calculation.
- Region Aggregation.
- Privacy Masking.
- Sensitive Data Exclusion.
- Snapshot Validation.
- Invalid Snapshot Handling.
- Approval Workflow.
- Publication Workflow.
- Revoke Workflow.
- Public API Access.
- Published Data Filter.
- Transaction Verification.
- Organization Isolation.

---

## 49. Integration Test

Flow:

Collection Created

↓

Distribution Completed

↓

Fund Updated

↓

Ledger Updated

↓

Generate Transparency Snapshot

↓

Aggregate Data

↓

Validate Snapshot

↓

Submit

↓

Approve

↓

Publish

↓

Public Dashboard Updated.

---

## 50. Security Test

Test:

- Unpublished Snapshot Access.
- Draft Data Exposure.
- Cross Organization Access.
- Sensitive Data Exposure.
- NIK Exposure.
- Bank Account Exposure.
- Mustahik Identity Exposure.
- Unauthorized Publish.
- Unauthorized Revoke.
- Public API Abuse.
- Rate Limit.
- Cache Leakage.
- Transaction Reference Enumeration.

---

# PRD 18AB — ACCEPTANCE CRITERIA

- [ ] Transparency Dashboard tersedia.
- [ ] Total Collection tersedia.
- [ ] Total Distribution tersedia.
- [ ] Available Fund tersedia.
- [ ] Fund Breakdown tersedia.
- [ ] Distribution by Asnaf tersedia.
- [ ] Distribution by Program tersedia.
- [ ] Distribution by Region tersedia.
- [ ] Program Progress tersedia.
- [ ] Transparency Snapshot tersedia.
- [ ] Snapshot Validation tersedia.
- [ ] Approval Workflow tersedia.
- [ ] Publication Workflow tersedia.
- [ ] Public Dashboard tersedia.
- [ ] Internal Transparency tersedia.
- [ ] Public API tersedia.
- [ ] Published Data Only diterapkan.
- [ ] Privacy Protection diterapkan.
- [ ] Sensitive Data tidak dipublikasikan.
- [ ] Public Verification tersedia.
- [ ] Publication History tersedia.
- [ ] Audit Trail Integration tersedia.
- [ ] Rate Limit tersedia.
- [ ] Organization isolation diterapkan.
- [ ] Permission diterapkan.
- [ ] Automated Test tersedia.

---

# PRD 18AC — DEFINITION OF DONE

Modul Transparency dianggap selesai apabila:

1. Data Collection dapat diagregasi.
2. Data Distribution dapat diagregasi.
3. Fund Balance dapat ditampilkan.
4. Program Progress tersedia.
5. Distribution berdasarkan Asnaf tersedia.
6. Distribution berdasarkan Program tersedia.
7. Distribution berdasarkan Region tersedia.
8. Transparency Snapshot dapat dibuat.
9. Snapshot dapat divalidasi.
10. Invalid Snapshot tidak dapat dipublish.
11. Snapshot dapat diajukan untuk approval.
12. Snapshot dapat disetujui.
13. Snapshot dapat dipublish.
14. Published Snapshot tersedia di Public Dashboard.
15. Draft dan unpublished data tidak dapat diakses publik.
16. Public API hanya menampilkan published data.
17. Sensitive Data tidak dipublikasikan.
18. Beneficiary privacy diterapkan.
19. Public Transaction Verification tersedia.
20. Publication History tersedia.
21. Revoke membutuhkan reason.
22. Audit Trail terintegrasi.
23. Rate Limit diterapkan.
24. Organization isolation berjalan.
25. Permission berjalan.
26. Automated Test berhasil.

---

# FUTURE DEVELOPMENT

Fitur berikut dapat dikembangkan pada versi selanjutnya:

- Real Time Transparency Dashboard
- Open Data API
- Embeddable Transparency Widget
- QR Transparency Verification
- Blockchain Proof of Publication
- Public Ledger Explorer
- Donation Journey Tracking
- Program Impact Analytics
- Interactive Geographic Map
- Public API Key
- Data Export API
- Transparency Score
- Organization Transparency Ranking
- Third Party Audit Integration
- External Verification
- Digital Signature for Reports
- Immutable Public Snapshot
- Historical Comparison
- Transparency Alert
- AI Generated Public Summary
- Multi Language Public Dashboard

---

# END OF PRD MODULE 18 — TRANSPARENCY