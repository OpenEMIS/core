# POCOR-9509 Release Documentation Index

This release contains **three major features** for asynchronous alert and webhook processing. Each feature is independent but works best when deployed together.

## Features in This Release

### 1. POCOR-9509: Alert Queueing System (FOUNDATION)
**Deploy First** - This is the core async infrastructure that other features depend on.

- Async alert processing via database queue
- Alert audit trail in permanent table
- Threshold-based alert conditions
- Performance: 20-50x faster than synchronous sending

📖 [Read POCOR-9509-README.md](POCOR-9509-README.md)

**Key Metrics:**
- Files modified: 40+
- New tables: 2 (alerts_queue, alert_logs)
- Migrations required: YES
- Performance gain: 5-10ms async vs 200-500ms sync

---

### 2. POCOR-9257: Webhook Async Queueing (RECOMMENDED)
**Deploy Second** - Converts all webhook firing to async processing.

- Asynchronous webhook delivery without blocking requests
- Retry mechanism for failed deliveries
- Webhook audit trail via webhook_logs
- Compatible with external systems and webhooks

📖 [Read POCOR-9257-README.md](POCOR-9257-README.md)

**Key Metrics:**
- Files modified: 35+
- Existing tables: Uses webhooks_queue (already exists)
- Migrations required: NO
- Benefits: No request blocking, retry support, visibility

---

### 3. POCOR-7554: Student Status Change Alerts (OPTIONAL)
**Deploy Third** - Automatic alerts when student status changes.

- Automatic notifications on enrollment status transitions
- Configurable recipients per school
- Audit trail of all status changes
- Integrates with async alert system

📖 [Read POCOR-7554-README.md](POCOR-7554-README.md)

**Key Metrics:**
- Files modified: 8-10
- New tables: 1 (student_status_changes)
- Migrations required: YES
- Depends on: POCOR-9509

---

## Deployment Order

### Option A: Full Deployment (All Features)
1. Deploy **POCOR-9509** (Alert Queueing) - Run migrations
2. Deploy **POCOR-9257** (Webhook Queueing) - No migrations
3. Deploy **POCOR-7554** (Student Status Alerts) - Run migrations
4. Verify all three systems working together

**Total deployment time:** ~30 minutes
**Systems affected:** All alert/webhook functionality

### Option B: Minimal Deployment (Alerts Only)
1. Deploy **POCOR-9509** only
2. Skip webhooks and student status alerts for now
3. Can add other features later

**Total deployment time:** ~15 minutes
**Systems affected:** Alert processing

### Option C: Phased Deployment (One at a Time)
- Day 1: Deploy POCOR-9509, monitor for 24 hours
- Day 2: Deploy POCOR-9257, verify webhook queueing
- Day 3: Deploy POCOR-7554, configure alert rules

**Recommended for production**

---

## Quick Reference: What Gets Changed

| Feature | Tables Modified | New Tables | Migrations | Risk Level |
|---------|-----------------|-----------|-----------|-----------|
| POCOR-9509 | 40+ | 2 | YES | Medium |
| POCOR-9257 | 35+ | 0 | NO | Low |
| POCOR-7554 | 8-10 | 1 | YES | Low |

---

## Rollback Procedure

**If any feature causes issues:**

1. Identify the problem feature from logs
2. Revert the specific commits for that feature
3. Restart services: `docker restart oe-poe-application-1`
4. Data is preserved - all audit tables kept intact

**Individual rollback examples:**
```bash
# Revert just webhooks (POCOR-9257)
git revert <commit-hash>

# Revert just alerts (POCOR-9509)
git revert <commit-hash>

# Revert just student status (POCOR-7554)
git revert <commit-hash>
```

---

## Monitoring Commands

```bash
# Check alert queue status
cd /path/to/emis/core/api
php artisan tinker
>>> DB::table('alerts_queue')->where('status', 0)->count()

# Check webhook queue status
>>> DB::table('webhooks_queue')->where('status', 0)->count()
>>> exit()

# Monitor logs
tail -f /path/to/emis/core/logs/hin-error.log
tail -f /path/to/emis/core/api/storage/logs/laravel.log
```

---

## Support & Questions

- **Alert issues:** Check `logs/alert_*.log` and `api/storage/logs/laravel.log`
- **Webhook issues:** Check `logs/hin-error.log` and `api/storage/logs/laravel.log`
- **Student status issues:** Check `logs/hin-error.log` for status change triggers
- **Database issues:** Check Laravel migrations status with `php artisan migrate:status`

For detailed troubleshooting, see the individual feature READMEs above.
