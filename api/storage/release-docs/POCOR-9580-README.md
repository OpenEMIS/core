# POCOR-9580 — Migrate Deprecated `Cake\Event\Event` to `EventInterface`

## 1. What is the Task?

Replace all usages of the deprecated `Cake\Event\Event` class across the CakePHP 5 codebase. This covers two distinct problems: (a) `new Event(...)` instantiation combined with `$this->getEventManager()->dispatch()` — replaced with the idiomatic `$this->dispatchEvent()` shorthand; and (b) bare `Event` or union-type `Event|EventInterface` parameter type hints — replaced with `EventInterface`. Also resolves a `ParseError` caused by unresolved git merge conflict markers in `CurrentAssessmentsTable.php`.

## 2. Situation Before

- `Cake\Event\Event` is deprecated in CakePHP 5; `EventInterface` is the correct type for event listener signatures.
- Several table and controller classes called `$this->getEventManager()->dispatch(new Event(...))` without importing `Cake\Event\Event`. Because bare `Event` resolved to the current namespace (e.g. `Institution\Model\Table\Event`), this caused fatal `Class not found` errors at runtime.
- Root cause of Institution > Students > Overview > Edit (404 on save): `afterSave` in `StudentUserTable.php` triggered the `Class "Institution\Model\Table\Event" not found` fatal.
- Root cause confirmed in historical error log for `InstitutionClassesTable.php` and `InstitutionSubjectsTable.php` as well.
- 80+ controller and table files had `Event|EventInterface` or bare `Event` type hints that were formally wrong.
- `CurrentAssessmentsTable.php` contained unresolved git merge conflict markers causing a `ParseError` on line 662.

## 3. What Was Implemented

### Core changes

- **`dispatchEvent()` migration** — replaced `$this->getEventManager()->dispatch(new Event(...))` with `$this->dispatchEvent(...)` in:
  - `plugins/Institution/src/Model/Table/StudentUserTable.php`
  - `plugins/Institution/src/Model/Table/InstitutionClassesTable.php`
  - `plugins/Institution/src/Model/Table/InstitutionSubjectsTable.php`
  - `src/Model/Table/AppTable.php`
  - Cross-component dispatch (3-arg form `$model->dispatchEvent($name, $data, $this)`) in:
    - `plugins/Scholarship/src/Controller/ScholarshipsController.php`
    - `plugins/Examination/src/Controller/ExaminationsController.php`
- **`EventInterface` type hint migration** — replaced `Event` and `Event|EventInterface` with `EventInterface` in 86 controller, table, and behavior files across `plugins/` and `src/`.
- **Removed `use Cake\Event\Event` imports** where the class is no longer referenced.
- **Merge conflict fix** — removed leftover `<<<<<<< HEAD` / `=======` / `>>>>>>>` markers from `CurrentAssessmentsTable.php`.

### Files Changed Summary

| Category | Count |
|---|---|
| Files modified | 93 |
| Files added | 0 |
| Files removed | 0 |

### Database Migrations

| | |
|---|---|
| Required | NO |
| Tables affected | None |
| Backward compatible | YES |

## 4. Deployment Instructions (User Experience)

1. `git pull origin POCOR-9580`
2. No migrations required.
3. Clear CakePHP cache: `./bin/cake cache clear_all` (inside container at `/var/www/html/emis/core`)
4. Smoke-test: navigate to Institution > Students > Overview > Edit and confirm save completes without 404.
5. Check `logs/hin-error.log` — confirm absence of `Class "...Event" not found` errors.

## 5. System Administrator Guide

### Log locations

| Log | Path (inside container) |
|---|---|
| CakePHP errors | `/var/www/html/emis/core/logs/hin-error.log` |
| CakePHP debug | `/var/www/html/emis/core/logs/hin-debug.log` |

### Monitoring

```bash
# Watch for any residual Event class-not-found errors
tail -f /var/www/html/emis/core/logs/hin-error.log | grep -i "event"
```

### Rollback procedure

```bash
git revert b220ae6ac1  # revert EventInterface migration commit
git revert 0a9bd4c991  # revert merge conflict fix commit
./bin/cake cache clear_all
```

### Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| `Class "...\Event" not found` | A new file using `new Event()` without import | Replace with `$this->dispatchEvent()` |
| `ParseError: unexpected token "<<"` | Unresolved merge conflict markers | Remove `<<<<<<<`/`=======`/`>>>>>>>` lines |
| 404 on student edit save | `afterSave` event dispatch fatal | Verify `StudentUserTable::afterSave` uses `dispatchEvent()` |