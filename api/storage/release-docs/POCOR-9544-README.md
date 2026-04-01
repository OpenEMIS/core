# POCOR-9544 — Attendance Week Dropdown: Overlapping Weeks

## What is the Task?
Fix the attendance week dropdown generating overlapping week ranges — the last date of one week incorrectly reappeared as the first date of the next week.

---

## Situation Before
When opening the weekly attendance view for any institution, the week selector dropdown displayed overlapping date ranges. For example:

- Week 15: Dec 7 – Dec 14
- Week 16: Dec 14 – Dec 21  ← Dec 14 appeared twice

This was caused by `FrozenDate` being immutable in CakePHP 5. The old code called `$startDate->addDay()` and discarded the return value, leaving `$startDate` equal to `$endDate` (the previous week's end). Every week therefore started one day early, producing the overlap.

---

## What Was Implemented

**Root cause:** In CakePHP 5, `FrozenDate` (a Carbon-based immutable date) returns a **new object** from every mutation method such as `addDay()`. The old code did:

```php
$startDate = $endDate->copy();
$startDate->addDay();   // return value discarded — $startDate unchanged
```

Because `$startDate` was never updated, each new week started on the same date the previous week ended.

**Fix:** Chain the call so the returned object is captured:

```php
$startDate = $endDate->copy()->addDay(); //POCOR-9544: FrozenDate is immutable — capture addDay() return value to prevent week overlap
```

**Files Changed Summary:**
- Modified: `plugins/AcademicPeriod/src/Model/Table/AcademicPeriodsTable.php`

**Database Migrations:** Not required.

---

## Deployment Instructions
1. `git pull` on the target server.
2. Clear CakePHP cache: `php bin/cake.php cache clear_all`.
3. Verify: navigate to Institutions > Attendance (weekly view) and open the week dropdown — confirm no date appears in two consecutive week labels.

### System Administrator Guide
No configuration changes required. Rollback: revert `AcademicPeriodsTable.php` via git.

---

## Playwright Smoke Test

### Manual steps

```
1. Navigate to https://[host]/core
2. Log in (admin / demo)
3. Click "Institutions" in the top navigation
4. Find "Avory Primary School" row → click "Select" dropdown → choose "View"
5. In the left sidebar expand "Attendance" → click "Students"
6. Open the "Week" dropdown
7. Scroll through all week options
8. Expected: each week's end date + 1 day == the next week's start date (no overlap, no gap)
9. Expected: no date label appears in two consecutive week entries
```

**Actual week format on this system:**
`Week N (Month DD, YYYY - Month DD, YYYY)` — e.g. `Week 1 (January 01, 2025 - January 05, 2025)`

### Playwright automation snippet (verified on https://localhost:8482/core)

```js
const BASE = 'https://[host]';

// Must use ignoreHTTPSErrors at context level for self-signed certs
const ctx = await browser.newContext({ ignoreHTTPSErrors: true });
const page = await ctx.newPage();

// 1. Login
await page.goto(BASE + '/core', { waitUntil: 'networkidle' });
await page.getByRole('textbox', { name: /username/i }).fill('admin');
await page.getByRole('textbox', { name: /password/i }).fill('demo');
await page.getByRole('button', { name: /login/i }).click();
await page.waitForLoadState('networkidle');

// 2. Institutions list → Avory Primary School (use Select dropdown → View)
await page.goto(BASE + '/core/Institutions/Institutions/index', { waitUntil: 'networkidle' });
await page.waitForSelector('tr:has-text("Avory Primary School")', { timeout: 10000 });
const avoryRow = page.locator('tr:has-text("Avory Primary School")').first();
await avoryRow.locator('button:has-text("Select"), .dropdown-toggle').first().click();
await page.waitForTimeout(1000);
await page.locator('a:has-text("View"), .dropdown-menu a:has-text("View")').first().click();
await page.waitForLoadState('networkidle');

// 3. Navigate to Attendance > Students via sidebar link (avoids menu-expand timing issues)
const href = await page.locator('a[href*="StudentAttendances"]').first().getAttribute('href');
await page.goto(BASE + href, { waitUntil: 'networkidle' });

// 4. Collect all week options
const optionTexts = await page.locator('select[name="week"] option').allTextContents();

// 5. Parse format: "Week N (Month DD, YYYY - Month DD, YYYY)"
const ranges = optionTexts.map(o => {
    const m = o.match(/\((.+?) - (.+?)\)/);
    if (!m) return null;
    const start = new Date(m[1].trim());
    const end   = new Date(m[2].trim());
    return (!isNaN(start) && !isNaN(end)) ? { raw: o.trim(), start, end } : null;
}).filter(Boolean);

// 6. Verify contiguity: end of week N + 1 day === start of week N+1
for (let i = 1; i < ranges.length; i++) {
    const prev = ranges[i - 1];
    const cur  = ranges[i];
    const expected = new Date(prev.end);
    expected.setDate(expected.getDate() + 1);
    const diff = Math.round((cur.start - expected) / 86400000);
    if (diff !== 0) {
        throw new Error(
            `Overlap/gap between week ${i} and ${i + 1}: ` +
            `"${prev.raw}" → "${cur.raw}" (diff: ${diff} days)`
        );
    }
}

console.log(`✓ PASS — all ${ranges.length} weeks contiguous, no overlaps or gaps`);
console.log(`  First: "${ranges[0].raw}"`);
console.log(`  Last:  "${ranges[ranges.length - 1].raw}"`);

await ctx.close();
```

### Verified result (https://localhost:8482/core, 2025 academic period)

```
✓ Attendance > Students — https://localhost:8482/core/Institution/Institutions/StudentAttendances/index/...
✓ 53 week options found
✓ Parsed 53 date ranges
✓ PASS — all 53 weeks are contiguous with no overlaps or gaps
  First: "Week 1 (January 01, 2025 - January 05, 2025)"
  Last:  "Week 53 (December 29, 2025 - December 31, 2025)"
```

### What to look for
- **Pass:** All 53 weeks are contiguous — `Week N` ends on date X, `Week N+1` starts on X+1.
- **Fail (before fix):** A date such as "January 14" appears as both the end of one week and the start of the next, e.g. `Week 15 (January 07 – January 14)` followed by `Week 16 (January 14 – January 21)`.
