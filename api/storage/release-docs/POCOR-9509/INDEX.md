# OpenEMIS Core — Communications & Alerts: Documentation Index

> **Branch:** POCOR-9509 · **Last updated:** 2026-03-13

This index covers all documentation for the OpenEMIS Core communications system — automated alerts, manual messaging, and the delivery infrastructure that powers both.

---

## What This Is

OpenEMIS Core has two complementary ways to communicate with staff, students, guardians, and administrators:

1. **Automated Alerts** — rules-based notifications that fire automatically when data thresholds are reached or events occur (enrolment, attendance, staff retirement, case escalation, etc.)
2. **School Messaging** — a manual broadcast tool that lets authorised school staff compose and send free-form messages to targeted groups within their institution

Both systems deliver via **Email** and/or **SMS**, and all sent messages are recorded in the same **Alert Logs** audit trail.

---

## Core Reference Documents

| Document | Description |
|----------|-------------|
| [README.md](README.md) | User-facing overview: alert types at a glance, recipient roles, placeholders, multiple rules concept, troubleshooting |
| [ALERTS_GUIDE.md](ALERTS_GUIDE.md) | Technical developer reference: architecture, command inventory, dispatch paths, activation checklist, testing |
| [thresholds.md](thresholds.md) | Complete threshold configuration reference: every field, every format, worked multi-rule strategies |

---

## Alert Type Help Files

Each file covers: what the alert is, when it fires, threshold configuration, available placeholders, and 2–3 worked example rules with subject line + full message body.

### Student Alerts

| File | Feature Key | Trigger |
|------|------------|---------|
| [alerts/student-absence.md](alerts/student-absence.md) | `StudentAttendance` | Student exceeds absence day threshold |
| [alerts/student-admission.md](alerts/student-admission.md) | `StudentAdmission` | New student admission saved |
| [alerts/student-enrolment.md](alerts/student-enrolment.md) | `StudentEnrolment` | Student enrolment saved |
| [alerts/student-status-change.md](alerts/student-status-change.md) | `StudentStatus` | Student status transitions (e.g. Enrolled → Withdrawn) |

### Staff Alerts

| File | Feature Key | Trigger |
|------|------------|---------|
| [alerts/retirement-warning.md](alerts/retirement-warning.md) | `RetirementWarning` | Staff approaching retirement date |
| [alerts/staff-employment-end.md](alerts/staff-employment-end.md) | `StaffEmployment` | Staff employment contract end approaching |
| [alerts/staff-leave-end.md](alerts/staff-leave-end.md) | `StaffLeave` | Staff leave end date approaching |
| [alerts/staff-type.md](alerts/staff-type.md) | `StaffType` | Staff contract type review date approaching |
| [alerts/license-validity.md](alerts/license-validity.md) | `LicenseValidity` | Professional license expiring or expired |
| [alerts/license-renewal.md](alerts/license-renewal.md) | `LicenseRenewal` | License renewal CPD hours requirement not met |

### Institutional / Administrative Alerts

| File | Feature Key | Trigger |
|------|------------|---------|
| [alerts/case-escalation.md](alerts/case-escalation.md) | `CaseEscalation` | Institution case open longer than threshold |
| [alerts/system-updates.md](alerts/system-updates.md) | `SystemUpdates` | System update notifications (daily) |

### Scholarship Alerts

| File | Feature Key | Trigger |
|------|------------|---------|
| [alerts/scholarship-application.md](alerts/scholarship-application.md) | `ScholarshipApplication` | Scholarship application close date approaching |
| [alerts/scholarship-disbursement.md](alerts/scholarship-disbursement.md) | `ScholarshipDisbursement` | Scholarship disbursement date approaching or passed |

### Not Implemented

| File | Feature Key | Status |
|------|------------|--------|
| [alerts/staff-attendance.md](alerts/staff-attendance.md) | `StaffAttendance` | Locked — no shell ever implemented |

---

## School Messaging

| File | Description |
|------|-------------|
| [alerts/school-messaging.md](alerts/school-messaging.md) | Manual broadcast messaging tool: recipient levels, compose workflow, Draft → Sent lifecycle, delivery process, failure reasons |

---

## PDF Manual

| File | Description |
|------|-------------|
| [OpenEMIS-Core-Alerts-Guide.pdf](OpenEMIS-Core-Alerts-Guide.pdf) | Comprehensive system administrator / developer guide (generated from combined.md) |

---

## Screenshots

Real screenshots from a live OpenEMIS Core instance, used in the PDF manual.

| File | Content |
|------|---------|
| [screenshots/alerts-list.png](screenshots/alerts-list.png) | Administration → Communications → Alerts (all 15 alert types) |
| [screenshots/alert-rules-list.png](screenshots/alert-rules-list.png) | Communications → Alert Rules list |
| [screenshots/alert-rule-add.png](screenshots/alert-rule-add.png) | Alert Rules → Add form |
| [screenshots/alert-logs.png](screenshots/alert-logs.png) | Communications → Logs (delivery audit trail) |
| [screenshots/alert-queue.png](screenshots/alert-queue.png) | Communications → Queue |
| [screenshots/messaging-list.png](screenshots/messaging-list.png) | Institution → Messaging index (Avory Primary School) |
| [screenshots/messaging-compose.png](screenshots/messaging-compose.png) | Institution → Messaging → Add (compose form) |
