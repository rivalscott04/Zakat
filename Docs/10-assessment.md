# PRD MODULE 10 — ASSESSMENT

Project: Zakat OS
Module: Assessment
Module Code: ASM
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md
- 09-mustahik.md

Related Modules:

- 11-distribution.md
- 12-program-management.md
- 13-reporting.md

---

# PRD 10A — OVERVIEW

## 1. Purpose

Modul Assessment bertanggung jawab untuk melakukan penilaian, verifikasi kondisi, pengumpulan data lapangan, scoring, rekomendasi, dan penentuan kelayakan Mustahik.

Modul Assessment dipisahkan dari Modul Mustahik.

Modul Mustahik bertanggung jawab terhadap:

- Master Data Mustahik
- Identity
- Household
- Address
- Profile
- Asnaf
- Status

Modul Assessment bertanggung jawab terhadap:

- Survey
- Questionnaire
- Field Assessment
- Scoring
- Verification
- Recommendation
- Eligibility Recommendation
- Reassessment
- Assessment History

Assessment tidak langsung menghasilkan Distribution.

Assessment menghasilkan keputusan atau rekomendasi yang dapat digunakan oleh modul lain.

Flow utama:

Mustahik

↓

Assessment Request

↓

Assignment

↓

Survey

↓

Data Collection

↓

Verification

↓

Scoring

↓

Recommendation

↓

Review

↓

Approved Assessment

↓

Ready for Distribution atau Program

---

## 2. Goals

Modul harus mampu:

1. Membuat Assessment Request.
2. Menugaskan Assessor.
3. Mendukung assessment lapangan.
4. Mendukung assessment berbasis form.
5. Mendukung dynamic questionnaire.
6. Mendukung conditional question.
7. Mendukung scoring.
8. Mendukung weighted scoring.
9. Mendukung manual scoring.
10. Mendukung recommendation.
11. Mendukung evidence.
12. Mendukung photo.
13. Mendukung document.
14. Mendukung location.
15. Mendukung survey offline preparation.
16. Mendukung reassessment.
17. Menyimpan assessment history.
18. Mendukung review.
19. Mendukung approval.
20. Menghasilkan eligibility recommendation.
21. Mendukung configurable assessment template.
22. Mendukung versioning template.
23. Menyediakan audit trail.

---

# PRD 10B — CORE PRINCIPLE

## 3. Separation Principle

Assessment tidak boleh mengubah master Mustahik secara otomatis tanpa kontrol.

Contoh:

Assessor menemukan perubahan alamat.

Assessment dapat menghasilkan:

Profile Update Suggestion.

Namun perubahan master data harus:

Reviewed

atau:

Approved

sesuai policy.

---

## 4. Assessment Result Principle

Assessment menghasilkan:

Data

Score

Finding

Recommendation

Assessment Result

Assessment bukan Distribution Approval.

Assessment bukan Fund Allocation.

Assessment bukan Payment.

---

## 5. Immutable Submission

Assessment yang telah:

SUBMITTED

tidak dapat diedit secara langsung.

Jika membutuhkan perubahan:

RETURNED

atau:

REASSESSMENT

harus dibuat.

Assessment yang telah APPROVED harus tetap dapat ditelusuri sebagai historical record.

---

# PRD 10C — ASSESSMENT REQUEST

## 6. Purpose

Assessment Request digunakan untuk memulai proses assessment terhadap Mustahik.

Assessment dapat dibuat karena:

- New Mustahik
- Distribution Request
- Program Application
- Reassessment
- Periodic Review
- Complaint
- Manual Request
- Emergency Case

---

## 7. Entity

assessment_requests

Fields:

id

organization_id

request_number

mustahik_id

assessment_type

priority

reason

requested_by

requested_at

due_date

status

created_at

updated_at

---

## 8. Request Number

Format:

ASR{YEAR}{SEQUENCE}

Contoh:

ASR2026000001

ASR2026000002

Rules:

- unique;
- immutable;
- human readable;
- tidak menggunakan dash.

---

# PRD 10D — ASSESSMENT TYPE

## 9. Initial Types

INITIAL

REASSESSMENT

PROGRAM

DISTRIBUTION

EMERGENCY

VERIFICATION

FOLLOWUP

COMPLAINT

CUSTOM

---

# PRD 10E — ASSESSMENT PRIORITY

## 10. Priority

LOW

NORMAL

HIGH

URGENT

Priority digunakan untuk:

Assignment

Queue

SLA

Dashboard

Notification

---

# PRD 10F — ASSESSMENT ASSIGNMENT

## 11. Purpose

Assessment harus dapat ditugaskan kepada Assessor.

Assessor dapat berupa:

- Amil
- Surveyor
- Field Officer
- Authorized Staff

---

## 12. Entity

assessment_assignments

Fields:

id

assessment_request_id

assessor_id

assigned_by

assigned_at

accepted_at

due_date

status

notes

created_at

updated_at

---

## 13. Assignment Status

ASSIGNED

ACCEPTED

IN_PROGRESS

COMPLETED

REASSIGNED

CANCELLED

OVERDUE

---

## 14. Assignment Rule

Satu Assessment Request dapat memiliki:

Primary Assessor

dan optional:

Supporting Assessor.

Assignment harus memiliki audit trail.

---

# PRD 10G — ASSESSMENT TEMPLATE

## 15. Purpose

Assessment Template memungkinkan organisasi membuat berbagai format assessment.

Contoh:

General Mustahik Assessment

Fakir Assessment

Miskin Assessment

Emergency Assessment

Education Assistance Assessment

Business Capital Assessment

Housing Assistance Assessment

---

## 16. Entity

assessment_templates

Fields:

id

organization_id

template_code

name

description

assessment_type

mustahik_type

version

status

effective_from

effective_until

created_at

updated_at

---

## 17. Template Code

Contoh:

GENERAL

FAKIR

MISKIN

EMERGENCY

EDUCATION

BUSINESS

HOUSING

Code harus:

- unique dalam organization;
- uppercase;
- tidak menggunakan dash;
- versioned.

---

## 18. Template Version

Template yang sudah digunakan oleh Assessment tidak boleh diubah secara destruktif.

Jika ada perubahan:

Create New Version.

Contoh:

GENERALV1

GENERALV2

Assessment lama tetap menggunakan snapshot atau version sebelumnya.

---

# PRD 10H — ASSESSMENT SECTION

## 19. Entity

assessment_sections

Fields:

id

template_id

section_code

name

description

sort_order

created_at

updated_at

---

## 20. Example Sections

IDENTITY

HOUSEHOLD

INCOME

EXPENSE

ASSET

HOUSING

HEALTH

EDUCATION

EMPLOYMENT

EMERGENCY

RECOMMENDATION

---

# PRD 10I — ASSESSMENT QUESTION

## 21. Entity

assessment_questions

Fields:

id

section_id

question_code

label

description

question_type

required

weight

scoring_rule

validation_rule

condition_rule

options

sort_order

status

created_at

updated_at

---

## 22. Question Type

TEXT

TEXTAREA

NUMBER

CURRENCY

DATE

SELECT

MULTISELECT

RADIO

CHECKBOX

BOOLEAN

PHOTO

DOCUMENT

LOCATION

SIGNATURE

---

## 23. Question Code

Contoh:

MONTHLYINCOME

MONTHLYEXPENSE

TOTALDEPENDENTS

HOUSINGSTATUS

EMPLOYMENTSTATUS

Code:

- unique dalam template version;
- uppercase;
- tidak menggunakan dash.

---

# PRD 10J — CONDITIONAL QUESTION

## 24. Purpose

Question dapat muncul berdasarkan jawaban sebelumnya.

Contoh:

Question:

Apakah memiliki pekerjaan?

Answer:

YES

↓

Show:

Employment Type

Employer

Monthly Income

Jika:

NO

↓

Show:

Reason

Unemployment Duration

---

## 25. Condition Rule

Condition dapat menggunakan:

EQUALS

NOT_EQUALS

GREATER_THAN

LESS_THAN

CONTAINS

IN

NOT_IN

AND

OR

---

# PRD 10K — ASSESSMENT INSTANCE

## 26. Entity

assessments

Fields:

id

organization_id

assessment_number

assessment_request_id

mustahik_id

template_id

template_version

assessment_type

assessor_id

assessment_date

started_at

submitted_at

approved_at

status

total_score

result

recommendation

created_at

updated_at

---

## 27. Assessment Number

Format:

ASM{YEAR}{SEQUENCE}

Contoh:

ASM2026000001

ASM2026000002

Rules:

- unique;
- immutable;
- tidak menggunakan dash.

---

# PRD 10L — ASSESSMENT ANSWER

## 28. Entity

assessment_answers

Fields:

id

assessment_id

question_id

question_code

answer_value

answer_data

score

notes

created_at

updated_at

---

## 29. Snapshot Principle

Assessment Answer harus menyimpan:

question_code

dan apabila diperlukan:

question_snapshot

Tujuannya agar perubahan template di masa depan tidak merusak historical assessment.

---

# PRD 10M — SCORING

## 30. Purpose

Scoring digunakan untuk membantu evaluasi kondisi Mustahik.

Scoring tidak boleh menjadi satu-satunya dasar keputusan otomatis.

Keputusan akhir dapat mempertimbangkan:

- Score
- Evidence
- Field Finding
- Assessor Recommendation
- Reviewer Decision
- Organization Policy

---

## 31. Score Formula

Contoh:

Question Score

×

Weight

=

Weighted Score

Total Score:

SUM(Weighted Score)

---

## 32. Scoring Mode

AUTOMATIC

MANUAL

HYBRID

---

## 33. Automatic Scoring

Contoh:

Monthly Income

Less than threshold:

Score 10

Medium:

Score 5

High:

Score 0

Rule harus configurable.

---

## 34. Manual Scoring

Assessor dapat memberikan score manual apabila:

Permission tersedia.

Manual Score wajib memiliki:

reason

notes

audit trail.

---

# PRD 10N — SCORE BAND

## 35. Entity

assessment_score_bands

Fields:

id

template_id

minimum_score

maximum_score

result_code

result_name

recommendation

priority

created_at

updated_at

---

## 36. Example

Score:

0 sampai 30

Result:

LOW_PRIORITY

Score:

31 sampai 60

Result:

MEDIUM_PRIORITY

Score:

61 sampai 100

Result:

HIGH_PRIORITY

Nilai dan aturan harus configurable.

---

# PRD 10O — FIELD FINDING

## 37. Purpose

Assessor dapat mencatat temuan lapangan.

Entity:

assessment_findings

Fields:

id

assessment_id

finding_type

title

description

severity

created_by

created_at

updated_at

---

## 38. Finding Type

POSITIVE

NEGATIVE

RISK

URGENT

FRAUD_INDICATOR

DATA_MISMATCH

OTHER

---

# PRD 10P — EVIDENCE

## 39. Purpose

Assessment dapat memiliki bukti pendukung.

Entity:

assessment_evidences

Fields:

id

assessment_id

evidence_type

file_id

caption

captured_at

latitude

longitude

uploaded_by

created_at

updated_at

---

## 40. Evidence Type

PHOTO

DOCUMENT

VIDEO

AUDIO

LOCATION

SIGNATURE

OTHER

File disimpan melalui Core File Management.

Assessment hanya menyimpan reference.

---

# PRD 10Q — LOCATION VERIFICATION

## 41. Purpose

Assessment lapangan dapat menyimpan lokasi.

Data:

latitude

longitude

accuracy

captured_at

---

## 42. Location Rule

Location harus optional.

Organization dapat menentukan apakah assessment tertentu:

REQUIRES_LOCATION

atau:

LOCATION_OPTIONAL.

---

# PRD 10R — RECOMMENDATION

## 43. Recommendation

Assessor dapat memberikan:

ELIGIBLE

NOT_ELIGIBLE

NEEDS_REVIEW

EMERGENCY_SUPPORT

REASSESSMENT_REQUIRED

PROGRAM_RECOMMENDED

DOCUMENT_REQUIRED

---

## 44. Recommendation Requirement

Recommendation wajib memiliki:

summary

reason

supporting finding apabila diperlukan.

---

# PRD 10S — ASSESSMENT REVIEW

## 45. Purpose

Assessment yang telah disubmit dapat diperiksa oleh Reviewer.

Reviewer berbeda dari Assessor apabila Maker Checker aktif.

---

## 46. Entity

assessment_reviews

Fields:

id

assessment_id

reviewer_id

decision

notes

reviewed_at

created_at

updated_at

---

## 47. Review Decision

APPROVE

RETURN

REJECT

ESCALATE

---

# PRD 10T — ASSESSMENT STATUS

## 48. Status

DRAFT

ASSIGNED

IN_PROGRESS

SUBMITTED

UNDER_REVIEW

RETURNED

APPROVED

REJECTED

EXPIRED

CANCELLED

---

## 49. Status Flow

DRAFT

↓

ASSIGNED

↓

IN_PROGRESS

↓

SUBMITTED

↓

UNDER_REVIEW

↓

APPROVED

atau:

RETURNED

↓

IN_PROGRESS

atau:

REJECTED

---

# PRD 10U — REASSESSMENT

## 50. Purpose

Reassessment digunakan untuk memperbarui kondisi Mustahik.

Reassessment harus tetap mempertahankan Assessment sebelumnya.

---

## 51. Trigger

Reassessment dapat dipicu oleh:

Periodic Review

Distribution Request

Major Change

Complaint

Expiration

Manual Request

---

## 52. Previous Assessment

Reassessment menyimpan:

previous_assessment_id

Comparison dapat dilakukan antara:

Previous Score

Current Score

Previous Recommendation

Current Recommendation

---

# PRD 10V — SLA

## 53. Assessment SLA

Organization dapat mengatur SLA.

Contoh:

NORMAL:

7 Days

HIGH:

3 Days

URGENT:

1 Day

Sistem menghitung:

due_date

overdue status.

---

## 54. Overdue Event

Jika:

Current Date

>

Due Date

dan status belum:

APPROVED

REJECTED

CANCELLED

maka:

OVERDUE.

---

# PRD 10W — API SPECIFICATION

## 55. Assessment Request

GET

/api/v1/assessment-requests

POST

/api/v1/assessment-requests

GET

/api/v1/assessment-requests/{id}

POST

/api/v1/assessment-requests/{id}/assign

POST

/api/v1/assessment-requests/{id}/cancel

---

## 56. Assessment Template

GET

/api/v1/assessment/templates

POST

/api/v1/assessment/templates

GET

/api/v1/assessment/templates/{id}

PATCH

/api/v1/assessment/templates/{id}

POST

/api/v1/assessment/templates/{id}/publish

POST

/api/v1/assessment/templates/{id}/new-version

---

## 57. Assessment

GET

/api/v1/assessments

POST

/api/v1/assessments

GET

/api/v1/assessments/{id}

PATCH

/api/v1/assessments/{id}

POST

/api/v1/assessments/{id}/submit

POST

/api/v1/assessments/{id}/review

POST

/api/v1/assessments/{id}/approve

POST

/api/v1/assessments/{id}/return

POST

/api/v1/assessments/{id}/reject

---

## 58. Evidence

POST

/api/v1/assessments/{id}/evidences

DELETE

/api/v1/assessment-evidences/{id}

---

## 59. Reassessment

POST

/api/v1/assessments/{id}/reassess

---

# PRD 10X — PERMISSIONS

## 60. Permission Codes

assessment.view

assessment.request.create

assessment.request.assign

assessment.request.cancel

assessment.create

assessment.update

assessment.submit

assessment.review

assessment.approve

assessment.reject

assessment.return

assessment.reassess

assessment.template.view

assessment.template.create

assessment.template.update

assessment.template.publish

assessment.evidence.view

assessment.evidence.upload

assessment.evidence.delete

assessment.score.override

assessment.export

assessment.audit.view

---

# PRD 10Y — AUDIT EVENTS

## 61. Audit Events

Minimal:

assessment_request_created

assessment_request_assigned

assessment_request_reassigned

assessment_request_cancelled

assessment_started

assessment_updated

assessment_submitted

assessment_returned

assessment_reviewed

assessment_approved

assessment_rejected

assessment_reassessed

assessment_score_calculated

assessment_score_overridden

assessment_recommendation_created

assessment_evidence_uploaded

assessment_evidence_deleted

assessment_finding_created

assessment_template_created

assessment_template_updated

assessment_template_published

assessment_template_version_created

assessment_overdue

---

# PRD 10Z — UI REQUIREMENTS

## 62. Assessment Dashboard

Cards:

Pending Assignment

In Progress

Submitted

Under Review

Approved

Returned

Overdue

Urgent Assessment

---

## 63. Assessment Request List

Velzon DataTable.

Columns:

Request Number

Mustahik

Assessment Type

Priority

Assessor

Requested Date

Due Date

Status

Actions

---

## 64. Assessment Workspace

Layout:

Header:

Assessment Number

Mustahik

Priority

Status

Assessor

Due Date

Main Content:

Section Navigation

↓

Dynamic Questions

↓

Evidence Panel

↓

Finding Panel

↓

Score Panel

↓

Recommendation

↓

Save Draft

Submit

---

## 65. Assessment Detail

Tabs:

Overview

Answers

Score

Findings

Evidence

Recommendation

Review

History

Audit

---

## 66. Template Builder

Admin dapat:

Create Section

Create Question

Set Question Type

Set Required

Set Weight

Set Scoring Rule

Set Conditional Rule

Set Options

Set Sort Order

Preview Template

Publish Version

---

# PRD 10AA — BUSINESS RULES

## 67. General Rules

1. Assessment harus terkait dengan Mustahik.
2. Assessment Request dapat memiliki SLA.
3. Assessment dapat ditugaskan kepada Assessor.
4. Assessor hanya dapat mengakses assessment yang menjadi kewenangannya.
5. Template harus versioned.
6. Template yang sudah digunakan tidak boleh diubah secara destruktif.
7. Assessment Answer harus mempertahankan historical context.
8. Conditional Question harus dievaluasi secara konsisten.
9. Score dapat otomatis atau manual.
10. Manual Score membutuhkan reason.
11. Assessment yang Submitted tidak dapat diedit langsung.
12. Assessment dapat dikembalikan untuk revisi.
13. Assessment Approved bersifat historical record.
14. Reassessment tidak menghapus assessment sebelumnya.
15. Evidence harus mengikuti permission.
16. Assessment tidak langsung menghasilkan Distribution.
17. Recommendation bukan Distribution Approval.
18. Eligibility decision dapat menggunakan Assessment sebagai input.
19. Maker Checker dapat diaktifkan.
20. Organization isolation wajib diterapkan.
21. Permission diperiksa di backend.
22. Semua aktivitas penting harus diaudit.

---

# PRD 10AB — TESTING REQUIREMENTS

## 68. Unit Test

Minimal:

- Assessment Request Creation
- Request Number Generation
- Assessor Assignment
- Assignment Validation
- Template Creation
- Template Versioning
- Dynamic Question Rendering
- Conditional Question Evaluation
- Required Question Validation
- Automatic Scoring
- Manual Score Override
- Score Band Resolution
- Assessment Submission
- Assessment Review
- Assessment Approval
- Assessment Return
- Assessment Rejection
- Evidence Upload
- Reassessment
- SLA Calculation
- Overdue Detection

---

## 69. Integration Test

Flow:

Mustahik

↓

Assessment Request

↓

Assignment

↓

Assessment Start

↓

Answer Questions

↓

Upload Evidence

↓

Calculate Score

↓

Create Recommendation

↓

Submit

↓

Review

↓

Approve

↓

Eligibility Input

↓

Distribution Ready

---

## 70. Security Test

Test:

- Cross organization assessment access;
- Unauthorized assessment access;
- Unauthorized assessor update;
- Template manipulation after publish;
- Score manipulation;
- Evidence unauthorized access;
- Approval by unauthorized user;
- Self approval when Maker Checker active;
- Historical assessment modification;
- Audit bypass.

---

# PRD 10AC — ACCEPTANCE CRITERIA

- [ ] Assessment Request dapat dibuat.
- [ ] Request Number dibuat otomatis.
- [ ] Assessment dapat ditugaskan.
- [ ] Priority tersedia.
- [ ] SLA tersedia.
- [ ] Dynamic Template tersedia.
- [ ] Template Versioning tersedia.
- [ ] Section tersedia.
- [ ] Dynamic Question tersedia.
- [ ] Conditional Question tersedia.
- [ ] Scoring tersedia.
- [ ] Weighted Scoring tersedia.
- [ ] Manual Score tersedia.
- [ ] Score Band tersedia.
- [ ] Field Finding tersedia.
- [ ] Evidence tersedia.
- [ ] Photo tersedia.
- [ ] Document tersedia.
- [ ] Location tersedia.
- [ ] Recommendation tersedia.
- [ ] Review tersedia.
- [ ] Approval tersedia.
- [ ] Return tersedia.
- [ ] Reassessment tersedia.
- [ ] Assessment History tersedia.
- [ ] Audit Trail tersedia.
- [ ] Organization isolation diterapkan.
- [ ] Permission diterapkan.
- [ ] Automated Test tersedia.

---

# PRD 10AD — DEFINITION OF DONE

Modul Assessment dianggap selesai apabila:

1. Assessment Request dapat dibuat.
2. Assessment dapat ditugaskan kepada Assessor.
3. Priority dan SLA berjalan.
4. Dynamic Assessment Template tersedia.
5. Template mendukung versioning.
6. Assessment mendukung berbagai jenis pertanyaan.
7. Conditional Question berjalan.
8. Evidence dapat ditambahkan.
9. Field Finding dapat dicatat.
10. Automatic Scoring berjalan.
11. Manual Score dapat dilakukan dengan audit.
12. Recommendation dapat dibuat.
13. Assessment dapat disubmit.
14. Assessment dapat direview.
15. Assessment dapat disetujui atau dikembalikan.
16. Reassessment dapat dilakukan.
17. Historical Assessment tetap tersimpan.
18. Assessment dapat digunakan sebagai input Eligibility dan Distribution.
19. Audit Trail tersedia.
20. Organization isolation berjalan.
21. Permission berjalan.
22. Automated Test berhasil.

---

# END OF PRD MODULE 10 — ASSESSMENT