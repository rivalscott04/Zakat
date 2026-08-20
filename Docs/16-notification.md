# PRD MODULE 16 — NOTIFICATION

Project: ZETRA
Module: Notification
Module Code: NTF
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md

Related Modules:

- All Modules

---

# PRD 16A — OVERVIEW

## 1. Purpose

Modul Notification bertanggung jawab untuk mengirim, mengelola, mencatat, dan memantau notifikasi dalam ZETRA.

Notification merupakan centralized service.

Semua module dapat menghasilkan Notification Event.

Contoh:

Payment Received

↓

Notification Module

↓

In App Notification

Email

WhatsApp

Push Notification

Webhook

Channel lain di masa depan.

---

## 2. Goals

Modul harus mampu:

1. Membuat notification.
2. Mengirim in-app notification.
3. Mengirim email.
4. Mendukung notification template.
5. Mendukung multiple channel.
6. Mendukung notification preference.
7. Mendukung read dan unread status.
8. Mendukung notification queue.
9. Mendukung failed notification.
10. Mendukung retry.
11. Mendukung notification history.
12. Mendukung priority.
13. Mendukung scheduled notification sederhana.
14. Mendukung bulk notification.
15. Mendukung audit trail.

Versi awal tidak wajib:

- WhatsApp integration.
- SMS integration.
- Mobile Push Notification.
- Advanced notification campaign.
- AI notification personalization.

---

# PRD 16B — CORE PRINCIPLE

## 3. Centralized Notification

Module lain tidak boleh memiliki implementasi notification sendiri.

Module menghasilkan:

Domain Event.

Contoh:

payment_paid

↓

Notification Event

↓

Notification Module

↓

Resolve Recipient

↓

Resolve Template

↓

Queue Notification

↓

Send Notification

↓

Store Result.

---

## 4. Channel Abstraction

Notification menggunakan abstraction.

Contoh channel:

IN_APP

EMAIL

WEBHOOK

Future:

WHATSAPP

SMS

PUSH.

Setiap channel memiliki driver sendiri.

---

# PRD 16C — NOTIFICATION ENTITY

## 5. Entity

notifications

Fields:

id

organization_id

notification_number

recipient_type

recipient_id

title

message

priority

status

read_at

scheduled_at

sent_at

created_at

updated_at

---

## 6. Notification Number

Format:

NTF{YEAR}{SEQUENCE}

Contoh:

NTF2026000001

NTF2026000002

Rules:

- unique;
- immutable;
- uppercase;
- tidak menggunakan dash;
- human readable.

Primary key menggunakan:

ULID.

---

# PRD 16D — RECIPIENT

## 7. Recipient Type

Initial:

USER

MUSTAHIK

MUZAKKI

ORGANIZATION

OTHER.

Versi awal fokus utama:

USER.

Mustahik dan Muzakki dapat menggunakan channel eksternal seperti Email atau WhatsApp pada versi berikutnya.

---

## 8. Recipient Resolution

Notification dapat ditujukan kepada:

Single User

Multiple Users

Role

Permission

Organization.

Contoh:

Distribution Failed

↓

Recipient:

Finance Manager.

---

# PRD 16E — NOTIFICATION CHANNEL

## 9. Initial Channel

IN_APP

EMAIL

WEBHOOK.

Future:

WHATSAPP

SMS

PUSH.

---

## 10. Entity

notification_deliveries

Fields:

id

notification_id

channel

recipient_address

status

provider

provider_reference

attempt_count

last_attempt_at

sent_at

delivered_at

failed_at

error_message

created_at

updated_at

---

# PRD 16F — DELIVERY STATUS

## 11. Status

PENDING

QUEUED

PROCESSING

SENT

DELIVERED

FAILED

CANCELLED

---

## 12. Notification Status

DRAFT

SCHEDULED

QUEUED

PARTIALLY_SENT

SENT

FAILED

CANCELLED

---

# PRD 16G — IN APP NOTIFICATION

## 13. Purpose

In-App Notification digunakan untuk menampilkan notifikasi langsung dalam aplikasi.

Contoh:

Payment berhasil diterima.

Distribution membutuhkan approval.

Document akan expired.

Bank reconciliation memiliki unmatched transaction.

---

## 14. Features

User dapat:

View Notification

Mark as Read

Mark as Unread

View All

Delete Personal Notification.

---

## 15. Read Status

Notification memiliki:

read_at.

Jika:

read_at IS NULL

maka:

UNREAD.

Jika:

read_at IS NOT NULL

maka:

READ.

---

# PRD 16H — EMAIL NOTIFICATION

## 16. Purpose

Email digunakan untuk notification yang membutuhkan komunikasi eksternal.

Contoh:

Payment Confirmation.

Distribution Confirmation.

Approval Request.

System Alert.

Document Expiration.

---

## 17. Email Configuration

Organization dapat menggunakan:

Default System Email.

Atau:

Custom SMTP Configuration.

Entity:

notification_email_configs

Fields:

id

organization_id

driver

host

port

username_encrypted

password_encrypted

from_name

from_email

encryption

status

created_at

updated_at

---

## 18. Security

Credential email harus:

- encrypted;
- tidak ditampilkan kembali secara penuh;
- hanya dapat dikelola oleh authorized user.

---

# PRD 16I — WEBHOOK NOTIFICATION

## 19. Purpose

Webhook digunakan untuk integrasi dengan sistem eksternal.

Contoh:

ZETRA

↓

Payment Paid

↓

External System Webhook.

---

## 20. Entity

notification_webhooks

Fields:

id

organization_id

name

url

secret_encrypted

events

status

created_at

updated_at

---

## 21. Webhook Security

Webhook dapat menggunakan:

Signature.

Header:

XZakatOSSignature

Payload harus ditandatangani menggunakan secret.

---

# PRD 16J — NOTIFICATION TEMPLATE

## 22. Purpose

Template digunakan agar notification tidak dibuat hardcoded.

Entity:

notification_templates

Fields:

id

organization_id

template_code

name

channel

subject

content

locale

status

created_at

updated_at

---

## 23. Template Code

Contoh:

PAYMENTRECEIVED

PAYMENTFAILED

DISTRIBUTIONAPPROVAL

DISTRIBUTIONCOMPLETED

DOCUMENTEXPIRING

ASSESSMENTREVIEW

BANKRECONCILIATIONALERT

Rules:

- uppercase;
- unique dalam organization;
- tidak menggunakan dash.

---

# PRD 16K — TEMPLATE VARIABLES

## 24. Purpose

Template mendukung dynamic variable.

Contoh:

Hello {{recipient_name}}

Payment {{payment_number}}

sebesar {{amount}}

telah diterima.

---

## 25. Variable Source

Variable dapat berasal dari:

Notification Data

Source Module

Organization

Recipient.

---

## 26. Validation

Template harus divalidasi sebelum diaktifkan.

Sistem harus mendeteksi:

Unknown Variable.

Invalid Syntax.

Missing Required Variable.

---

# PRD 16L — NOTIFICATION EVENT

## 27. Event

Module lain dapat menghasilkan event.

Contoh:

payment_paid

distribution_approved

distribution_completed

document_verified

document_expiring

bank_transaction_unmatched.

Notification Module dapat memiliki Event Rule.

---

## 28. Entity

notification_rules

Fields:

id

organization_id

event_name

template_id

channels

recipient_strategy

priority

enabled

created_at

updated_at

---

# PRD 16M — RECIPIENT STRATEGY

## 29. Strategy

USER

ROLE

PERMISSION

ORGANIZATION_ADMIN

SOURCE_OWNER

CUSTOM.

Contoh:

Event:

distribution_approved.

Recipient Strategy:

SOURCE_OWNER.

---

# PRD 16N — PRIORITY

## 30. Priority

LOW

NORMAL

HIGH

URGENT.

---

## 31. Priority Example

LOW:

Informational.

NORMAL:

General system notification.

HIGH:

Action required.

URGENT:

Critical system issue.

---

# PRD 16O — QUEUE

## 32. Queue Principle

Notification eksternal tidak dikirim langsung dalam request utama.

Gunakan:

Queue.

Flow:

Event

↓

Create Notification

↓

Create Delivery

↓

Queue Job

↓

Process

↓

Send

↓

Update Status.

---

# PRD 16P — RETRY

## 33. Retry Rule

Jika Notification gagal:

Retry dapat dilakukan otomatis.

Contoh:

Attempt 1

↓

Failed

↓

Retry 1

↓

Failed

↓

Retry 2

↓

Failed

↓

FAILED.

Default maximum:

3 attempts.

Configurable per channel.

---

# PRD 16Q — SCHEDULED NOTIFICATION

## 34. Purpose

Notification dapat dijadwalkan.

Contoh:

Document Expiring:

30 hari sebelum expiration.

Scheduled:

1 September 2026.

---

## 35. Rule

Jika:

scheduled_at > current_time

Status:

SCHEDULED.

Ketika waktu tercapai:

SCHEDULED

↓

QUEUED.

---

# PRD 16R — BULK NOTIFICATION

## 36. Purpose

System dapat mengirim notification ke banyak recipient.

Contoh:

100 user.

Sistem membuat:

1 Notification Campaign atau Batch.

↓

100 Notification Delivery.

Versi awal cukup mendukung:

Multiple Recipient.

---

## 37. Entity

notification_batches

Fields:

id

organization_id

batch_number

name

total_recipient

total_success

total_failed

status

created_by

created_at

updated_at

---

## 38. Batch Number

Format:

NFB{YEAR}{SEQUENCE}

Contoh:

NFB2026000001

---

# PRD 16S — NOTIFICATION PREFERENCE

## 39. Purpose

User dapat mengatur preference notification.

Entity:

notification_preferences

Fields:

id

user_id

organization_id

event_name

channel

enabled

created_at

updated_at

---

## 40. Example

User dapat memilih:

Payment Notification

In App:

Enabled.

Email:

Enabled.

Document Reminder

In App:

Enabled.

Email:

Disabled.

---

# PRD 16T — NOTIFICATION CENTER

## 41. Purpose

Frontend memiliki Notification Center.

Features:

Unread Count.

Latest Notifications.

Mark as Read.

Mark All as Read.

View All.

Notification Detail.

---

## 42. Real Time

Versi awal:

Polling.

Future:

WebSocket.

SSE.

Laravel Reverb atau equivalent dapat digunakan kemudian.

---

# PRD 16U — API SPECIFICATION

## 43. Notifications

GET

/api/v1/notifications

GET

/api/v1/notifications/{id}

POST

/api/v1/notifications/{id}/read

POST

/api/v1/notifications/{id}/unread

POST

/api/v1/notifications/read-all

DELETE

/api/v1/notifications/{id}

---

## 44. Templates

GET

/api/v1/notification-templates

POST

/api/v1/notification-templates

GET

/api/v1/notification-templates/{id}

PATCH

/api/v1/notification-templates/{id}

POST

/api/v1/notification-templates/{id}/activate

POST

/api/v1/notification-templates/{id}/deactivate

---

## 45. Rules

GET

/api/v1/notification-rules

POST

/api/v1/notification-rules

PATCH

/api/v1/notification-rules/{id}

POST

/api/v1/notification-rules/{id}/enable

POST

/api/v1/notification-rules/{id}/disable

---

## 46. Preferences

GET

/api/v1/notification-preferences

PATCH

/api/v1/notification-preferences

---

## 47. Batch

GET

/api/v1/notification-batches

POST

/api/v1/notification-batches

GET

/api/v1/notification-batches/{id}

POST

/api/v1/notification-batches/{id}/send

POST

/api/v1/notification-batches/{id}/cancel

---

# PRD 16V — PERMISSIONS

## 48. Permission Codes

notification.view

notification.create

notification.send

notification.delete

notification.template.view

notification.template.create

notification.template.update

notification.template.manage

notification.rule.view

notification.rule.create

notification.rule.update

notification.rule.manage

notification.preference.manage

notification.batch.view

notification.batch.create

notification.batch.send

notification.webhook.view

notification.webhook.manage

notification.email_config.manage

notification.audit.view

---

# PRD 16W — AUDIT EVENTS

## 49. Audit Events

Minimal:

notification_created

notification_scheduled

notification_queued

notification_sent

notification_delivered

notification_failed

notification_cancelled

notification_read

notification_unread

notification_template_created

notification_template_updated

notification_template_activated

notification_template_deactivated

notification_rule_created

notification_rule_updated

notification_rule_enabled

notification_rule_disabled

notification_batch_created

notification_batch_sent

notification_preference_updated

notification_webhook_created

notification_webhook_updated

notification_email_config_updated

---

# PRD 16X — UI REQUIREMENTS

## 50. Notification Bell

Diletakkan pada Navbar ZETRA.

Features:

Unread Badge.

Latest 5 Notifications.

Mark as Read.

View All.

---

## 51. Notification List

ZETRA DataTable.

Columns:

Notification Number

Title

Recipient

Priority

Channels

Created Date

Sent Date

Status

Read Status.

Filters:

Status.

Priority.

Channel.

Date Range.

Read Status.

---

## 52. Notification Detail

Header:

Notification Number

Title

Priority

Status.

Tabs:

Content

Recipients

Deliveries

Source Event

Timeline

Audit.

---

## 53. Template Management

List:

Template Code

Name

Channel

Locale

Status

Updated Date.

Editor:

Subject.

Content.

Available Variables.

Preview.

Test Send.

---

## 54. Notification Rules

Fields:

Event Name

Template

Channel

Recipient Strategy

Priority

Status.

---

## 55. User Notification Preference

User dapat mengatur:

Event.

Channel.

Enabled atau Disabled.

---

# PRD 16Y — BUSINESS RULES

## 56. General Rules

1. Notification Number harus unik.
2. Notification harus memiliki recipient.
3. Notification dapat memiliki satu atau lebih channel.
4. External notification harus menggunakan Queue.
5. Failed notification dapat di-retry.
6. Retry tidak boleh melebihi configured maximum.
7. Duplicate event tidak boleh menghasilkan duplicate notification apabila idempotency diterapkan.
8. Template harus divalidasi.
9. Unknown variable tidak boleh dikirim.
10. Recipient harus valid.
11. User preference harus diperiksa sebelum mengirim notification non-critical.
12. URGENT notification dapat melewati preference berdasarkan policy.
13. Notification read status bersifat per recipient.
14. Private notification hanya dapat diakses oleh recipient.
15. Cross Organization Notification tidak diperbolehkan.
16. Webhook secret harus terenkripsi.
17. Email credential harus terenkripsi.
18. Scheduled Notification harus diproses oleh scheduler.
19. Batch Notification harus menggunakan Queue.
20. Semua aktivitas material harus diaudit.

---

# PRD 16Z — TESTING REQUIREMENTS

## 57. Unit Test

Minimal:

- Notification Number Generation.
- Notification Creation.
- Recipient Validation.
- Template Rendering.
- Template Variable Validation.
- Unknown Variable Detection.
- In App Notification.
- Email Notification.
- Webhook Notification.
- Notification Preference.
- Priority Handling.
- Queue Processing.
- Retry Logic.
- Scheduled Notification.
- Mark as Read.
- Mark All as Read.
- Batch Notification.
- Organization Isolation.

---

## 58. Integration Test

Flow:

Payment Paid Event

↓

Notification Rule Match

↓

Resolve Recipient

↓

Resolve Template

↓

Create Notification

↓

Create Delivery

↓

Queue

↓

Send Email

↓

Create In App Notification

↓

Update Delivery Status.

---

## 59. Security Test

Test:

- Cross organization notification access;
- Unauthorized notification access;
- Unauthorized template modification;
- Template injection;
- Invalid webhook URL;
- Webhook secret exposure;
- Email credential exposure;
- Notification duplication;
- Queue replay;
- Unauthorized bulk notification;
- Audit bypass.

---

# PRD 16AA — ACCEPTANCE CRITERIA

- [ ] Notification dapat dibuat.
- [ ] Notification Number otomatis dibuat.
- [ ] In App Notification tersedia.
- [ ] Email Notification tersedia.
- [ ] Webhook Notification structure tersedia.
- [ ] Multiple Channel tersedia.
- [ ] Notification Template tersedia.
- [ ] Template Variable tersedia.
- [ ] Template Validation tersedia.
- [ ] Notification Rule tersedia.
- [ ] Recipient Strategy tersedia.
- [ ] Notification Preference tersedia.
- [ ] Priority tersedia.
- [ ] Queue tersedia.
- [ ] Retry tersedia.
- [ ] Scheduled Notification tersedia.
- [ ] Bulk Notification tersedia.
- [ ] Notification Center tersedia.
- [ ] Read dan Unread Status tersedia.
- [ ] Notification History tersedia.
- [ ] Audit Trail tersedia.
- [ ] Organization isolation diterapkan.
- [ ] Permission diterapkan.
- [ ] Automated Test tersedia.

---

# PRD 16AB — DEFINITION OF DONE

Modul Notification dianggap selesai apabila:

1. Notification dapat dibuat.
2. Notification Number otomatis dibuat.
3. Recipient dapat ditentukan.
4. In App Notification berjalan.
5. Email Notification berjalan.
6. Channel abstraction tersedia.
7. Notification Template tersedia.
8. Dynamic Variable dapat digunakan.
9. Template dapat divalidasi.
10. Notification Rule dapat dibuat.
11. Recipient Strategy berjalan.
12. Notification Preference tersedia.
13. Priority tersedia.
14. External Notification menggunakan Queue.
15. Retry berjalan.
16. Scheduled Notification berjalan.
17. Multiple Recipient didukung.
18. Notification Center tersedia.
19. User dapat membaca Notification.
20. User dapat menandai Notification sebagai unread.
21. Notification History tersedia.
22. Audit Trail tersedia.
23. Organization isolation berjalan.
24. Permission berjalan.
25. Automated Test berhasil.

---

# FUTURE DEVELOPMENT

Fitur berikut dapat dikembangkan pada versi selanjutnya:

- WhatsApp Integration
- SMS Integration
- Mobile Push Notification
- Firebase Cloud Messaging
- Laravel Reverb
- WebSocket Notification
- Server Sent Events
- Notification Campaign
- Advanced Segmentation
- Notification Analytics
- Delivery Analytics
- Open Tracking
- Click Tracking
- A/B Testing
- Multi Language Template
- AI Generated Notification
- Smart Notification Scheduling
- Escalation Workflow
- Notification Digest
- Daily Summary
- Weekly Summary

---

# END OF PRD MODULE 16 — NOTIFICATION