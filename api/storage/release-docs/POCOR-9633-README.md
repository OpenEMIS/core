# POCOR-9633 — Regression fixes: Meals Students View, Default Delivery Status, SMS Alert Edit

## 1. What is the Task?

Fix three regression bugs introduced by POCOR-9607 and POCOR-9604 merges (April 2):

1. **Institutions > Meals > Students > View** — page not loading (crash)
2. **Administration > System Setup > System Configuration > Meals > Default delivery status > Edit** — changes don't reflect in meals section
3. **Administration > System Setup > System Configuration > External Alert Service > SMS > Edit** — SMS alerts not working (page broken)

## 2. Situation Before

### Bugs 1 & 2
`StudentMealsTable::getDefaultMealReceiveID()` used CakePHP 4 ORM syntax:
```php
$ConfigItemsTable->find('all', ['conditions' => ['code' => 'DefaultDeliveryStatus']])->first();
```
In CakePHP 5, the second argument to `find()` no longer accepts `conditions`. The conditions were silently ignored, returning the first arbitrary `config_items` row instead of the `DefaultDeliveryStatus` row. This caused a fatal crash when accessing `.id` on null or a mis-matched `meal_received` entity.

### Bug 3
`ConfigExternalAlertServiceSmsTable::onGetCustomExternalSourceElement()` called:
```php
$event->getSubject()->renderElement('Configuration.external_alert_service_sms', [...]);
```
`renderElement()` was renamed to `element()` in CakePHP 5. The view threw a fatal error, breaking the SMS edit page.

## 3. What Was Implemented

### Files Changed Summary

| File | Change |
|------|--------|
| `plugins/Institution/src/Model/Table/StudentMealsTable.php` | Fixed `getDefaultMealReceiveID()` to use `ConfigItems->value()` + CakePHP 5 `find()->where()` |
| `plugins/Configuration/src/Model/Table/ConfigExternalAlertServiceSmsTable.php` | Fixed `renderElement()` → `element()` |
| `frontend/src/app/student-meals/student-meals.config.ts` | Fixed `getEditMealElement()` — was hardcoding `meal_received_id = 1` when null; now uses `data.default_meal_receive_id` |
| `frontend/src/app/student-meals/student-meals.component.ts` | Fixed `onBackClick()` — was hardcoding `meal_received_id = 1`; now uses `item.default_meal_receive_id`. Switched `getMealStudent()` to v5 API |
| `api/routes/api.php` | Added `GET /api/v5/institutions/{id}/meal-students` route (v5 equivalent of v4 endpoint) |

### Bug 1 & 2 Fix (`StudentMealsTable.php`)

```php
// Before (broken in CakePHP 5 — conditions silently ignored):
$configItemData = $ConfigItemsTable->find('all', ['conditions' => ['code' => 'DefaultDeliveryStatus']])->first();
$DefaultDeliveryStatus = $configItemData->value;
$mealReceivedData = $MealReceivedTable->find('all')->where(['name' => $DefaultDeliveryStatus])->first();
$default_meal_receive_id = $mealReceivedData->id;

// After (CakePHP 5 compatible):
$DefaultDeliveryStatus = $ConfigItemsTable->value('DefaultDeliveryStatus');
$mealReceivedData = $MealReceivedTable->find()->where(['name' => $DefaultDeliveryStatus])->first();
$default_meal_receive_id = $mealReceivedData ? $mealReceivedData->id : null;
```

### Bug 3 Fix (`ConfigExternalAlertServiceSmsTable.php`)

```php
// Before:
return $event->getSubject()->renderElement('Configuration.external_alert_service_sms', ['attr' => $attr]);

// After:
return $event->getSubject()->element('Configuration.external_alert_service_sms', ['attr' => $attr]);
```

### Bug 4 — Default Delivery Status Still Showed "Received" (Root Cause: Angular Frontend)

Investigation via Playwright network requests revealed the Meals Students page is an **Angular 11 SPA** (`frontend/src/`) that calls the Laravel API directly. The CakePHP `StudentMealsTable::getDefaultMealReceiveID()` fix was correct but irrelevant for this page.

The actual bug was in the Angular frontend (`student-meals.config.ts` and `student-meals.component.ts`):

```ts
// student-meals.config.ts — getEditMealElement() — Before:
if (data[dataKey] == null) {
    data[dataKey] = 1; // Hardcoded to Received!
}

// After:
if (data[dataKey] == null) {
    data[dataKey] = data.default_meal_receive_id ?? 1; //POCOR-9633: use API-supplied default
}
```

```ts
// student-meals.component.ts — onBackClick() — Before:
} else if (indexInArray2.index != -1 && indexInArray2.data == null) {
    item.meal_received_id = 1; // Hardcoded to Received!

// After:
    item.meal_received_id = item.default_meal_receive_id ?? 1; //POCOR-9633: use API-supplied default
```

The Laravel API already correctly returns `default_meal_receive_id` from `config_items.DefaultDeliveryStatus` — the frontend was just ignoring it.

### v5 API Migration

Added `GET /api/v5/institutions/{institutionId}/meal-students` route before the v5 catch-all, reusing the existing `MealController@getMealStudents` logic. Angular component updated to call v5 endpoint (`getWithToken(..., true)`).

### Database Migrations
None required.

## 4. Deployment Instructions

1. Merge branch `POCOR-9633` to master
2. Clear CakePHP cache: `php bin/cake.php cache clear_all`
3. No migration needed

## 5. System Administrator Guide

- **Meals > Students > View** should load normally after deploy
- **Default delivery status** changes in System Configuration will now correctly reflect in the Meals module
- **SMS External Alert Service** edit page will render correctly; Twilio credentials can be saved
