# Company Holidays System

A dedicated Settings subsystem that lets a **Super Admin** mark and unmark
**Company Holidays** for any given year using a 12-month calendar. Holiday
days are rendered in a distinct colour so they are visually distinguishable
from regular working days, and a right-click context menu provides the
mark/unmark interaction without ever leaving the calendar page.

This document is the canonical reference for the AI Coder when extending,
refactoring, or integrating the Company Holidays feature (for example, when
wiring holiday awareness into the Leave Management or Overtime engines).

---

## 1. Purpose and Scope

Company Holidays are **per-company, date-based** markers that represent days
the company is officially closed (public holidays, company-off days, special
closures). They are *not* the same as an individual employee's leave — they
apply to the whole company and are intended to drive downstream behaviour
such as:

* Excluding holiday days from leave-duration calculations (so an employee
  requesting leave that spans a company holiday is not charged for that day).
* Applying the correct overtime multiplier on company holidays.
* Rendering holiday badges on shared calendars.

The current implementation delivers the **management surface** (the Super
Admin calendar page and the AJAX toggle endpoint). Downstream consumers
should read from the `company_holidays` table directly, as described in
section *6. Integration Points*.

### Design Principles

* **Super Admin only.** Marking/unmarking holidays is a high-privilege
  action. The controller gates both the page and the AJAX endpoint with
  `isSuperAdmin()`, mirroring the Help Center pattern.
* **Per-company isolation.** Every row is scoped by `company_id`, following
  the platform's multi-tenant golden rule.
* **Date as the natural key.** A `UNIQUE(company_id, holiday_date)`
  constraint guarantees one record per company per calendar day, so the
  toggle endpoint can simply "insert or delete" without an upsert.
* **Instant, optimistic UI.** The calendar re-renders client-side after
  every successful toggle; there is no full page reload.
* **Audit trail.** Every mark/unmark is recorded in `activity_logs` via
  `logActivity()`.

---

## 2. Database Schema

### 2.1 The `company_holidays` Table

A new, minimal table stores one row per company-holiday day. It is strictly
tied to `company_id` for multi-tenant isolation, and uses a composite unique
key on `(company_id, holiday_date)` so the toggle endpoint can detect an
existing holiday with a single lookup.

```sql
CREATE TABLE `company_holidays` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NOT NULL,
    `holiday_date` DATE NOT NULL,
    `name` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_company_holiday` (`company_id`, `holiday_date`),
    KEY `ch_company_idx` (`company_id`),
    CONSTRAINT `ch_company_fk` FOREIGN KEY (`company_id`)
        REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Column Reference

| Column          | Type          | Notes                                                                                          |
| --------------- | ------------- | ---------------------------------------------------------------------------------------------- |
| `id`            | INT AUTO_INC  | Surrogate primary key.                                                                         |
| `company_id`    | INT NOT NULL  | Multi-tenant isolation FK → `companies.id`. `ON DELETE CASCADE` removes holidays when a company is deleted. |
| `holiday_date`  | DATE NOT NULL | The calendar day marked as a company holiday. Combined with `company_id` it is unique.         |
| `name`          | VARCHAR(255)  | Optional human-readable label (e.g. "New Year's Day"). `NULL` means "unnamed company holiday". |
| `created_at`    | DATETIME      | Audit timestamp.                                                                               |
| `updated_at`    | DATETIME      | Audit timestamp, auto-updated on change.                                                       |

**Why no `public_id` UUID?** Unlike `leave_types` or `overtime_requests`,
company holidays are never referenced by external clients via a public
identifier — the toggle endpoint is keyed by the date itself, which is the
natural identifier. Adding a UUID would be dead weight. If a future external
API needs to address a holiday by UUID, it can be added then.

### 2.2 Migration Files

* `app/migrations/company_holidays.sql` — the raw DDL.
* `app/migrations/run_company_holidays_migration.php` — one-time runner.

Run once before deploying the feature:

```bash
php app/migrations/run_company_holidays_migration.php
```

The runner reads `app/config/config.php` for DB credentials, splits the SQL
on `;`, and executes each statement with PDO. It mirrors the existing
`run_leave_migration.php` pattern.

---

## 3. Routing

All routes live in the company-scoped `$tenantGroup` inside
`app/config/router.php`, so they are automatically prefixed with
`/{tenant_slug}/{company_slug}`.

```php
// Company Holidays routes (Super Admin only)
$tenantGroup->addGet('/dashboard/settings/company-holidays', [
    'action' => 'companyHolidays'
]);
$tenantGroup->addPost('/dashboard/settings/company-holidays/toggle', [
    'action' => 'companyHolidaysToggle'
]);
```

| Method | Path                                                  | Action                       | Purpose                              |
| ------ | ----------------------------------------------------- | ---------------------------- | ------------------------------------ |
| GET    | `/dashboard/settings/company-holidays`                | `companyHolidays`            | Renders the 12-month calendar page.  |
| POST   | `/dashboard/settings/company-holidays/toggle`         | `companyHolidaysToggle`      | AJAX mark/unmark a single date.      |

A "Company Holidays" card linking to the GET route was added to the Settings
index page (`app/views/dashboard/settings/index.volt`).

---

## 4. Controller Logic

Both actions live on `DashboardController` (which extends
`TenantBaseController` and therefore has `$this->currentCompanyId`,
`$this->tenantUrl()`, `$this->logActivity()`, and `$this->isSuperAdmin()`
available).

### 4.1 `companyHolidaysAction()` — The Calendar Page

```php
public function companyHolidaysAction()
{
    if (!$this->isSuperAdmin()) {
        $this->flashSession->error($this->locale->t('flash.super_admin_only_company_holidays'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings'));
        return;
    }

    // Year selector (defaults to current year). Clamp to a sane range.
    $year = (int)$this->request->get('year', 'int', (int)date('Y'));
    $currentYear = (int)date('Y');
    if ($year < $currentYear - 5 || $year > $currentYear + 10) {
        $year = $currentYear;
    }

    // Load all holidays for this company + year as a date => name map.
    $rows = $this->db->fetchAll(
        "SELECT holiday_date, name FROM company_holidays " .
        "WHERE company_id = :company_id AND YEAR(holiday_date) = :year " .
        "ORDER BY holiday_date ASC",
        Phalcon\Db\Enum::FETCH_ASSOC,
        ['company_id' => $this->currentCompanyId, 'year' => $year]
    );

    $holidays = [];
    foreach ($rows as $row) {
        $holidays[$row['holiday_date']] = $row['name'];
    }

    $this->view->setVar('title', $this->locale->t('settings.company_holidays.title'));
    $this->view->setVar('selectedYear', $year);
    $this->view->setVar('currentYear', $currentYear);
    $this->view->setVar('holidaysJson', json_encode($holidays));
    $this->view->setVar('isSuperAdmin', true);
    $this->view->setVar('toggleUrl', $this->tenantUrl('/dashboard/settings/company-holidays/toggle'));
    $this->view->pick('dashboard/settings/company-holidays');
}
```

Key points:

* **Super Admin guard first.** Non-Super-Admins are redirected with a flash
  error before any DB work runs.
* **Year is clamped** to `[currentYear - 5, currentYear + 10]` to keep the
  page sane and avoid accidental huge queries.
* **Holidays are shipped to the view as JSON** (`holidaysJson`), keyed by
  `YYYY-MM-DD`. The Volt template injects this directly into JavaScript.
* **`toggleUrl` is precomputed server-side** so the JS never has to
  reconstruct the tenant/company URL itself.

### 4.2 `companyHolidaysToggleAction()` — The AJAX Endpoint

```php
public function companyHolidaysToggleAction()
{
    $this->view->disable();

    if (!$this->isSuperAdmin()) {
        $this->response->setStatusCode(403, 'Forbidden');
        $this->response->setJsonContent([
            'success' => false,
            'message' => $this->locale->t('settings.company_holidays.not_authorized'),
        ]);
        return $this->response->send();
    }

    if (!$this->request->isPost()) {
        $this->response->setStatusCode(405, 'Method Not Allowed');
        $this->response->setJsonContent(['success' => false, 'message' => 'POST required.']);
        return $this->response->send();
    }

    $date = trim((string)$this->request->getPost('date', 'string', ''));
    $name = trim((string)$this->request->getPost('name', 'string', ''));

    // Validate YYYY-MM-DD and a real calendar date.
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
        || !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) {
        $this->response->setStatusCode(400, 'Bad Request');
        $this->response->setJsonContent([
            'success' => false,
            'message' => $this->locale->t('settings.company_holidays.invalid_date'),
        ]);
        return $this->response->send();
    }

    $existing = $this->db->fetchOne(
        "SELECT id, name FROM company_holidays
         WHERE company_id = :company_id AND holiday_date = :date LIMIT 1",
        Phalcon\Db\Enum::FETCH_ASSOC,
        ['company_id' => $this->currentCompanyId, 'date' => $date]
    );

    if ($existing) {
        // Unmark → delete the holiday.
        $this->db->execute(
            "DELETE FROM company_holidays WHERE id = :id AND company_id = :company_id",
            ['id' => (int)$existing['id'], 'company_id' => $this->currentCompanyId]
        );

        $this->logActivity('delete', 'company_holidays', (int)$existing['id'],
            ['holiday_date' => $date, 'name' => $existing['name']], null);

        $this->response->setJsonContent([
            'success' => true, 'is_holiday' => false, 'date' => $date,
        ]);
        return $this->response->send();
    }

    // Mark → insert the holiday (name optional).
    $this->db->execute(
        "INSERT INTO company_holidays (company_id, holiday_date, name)
         VALUES (:company_id, :date, :name)",
        [
            'company_id' => $this->currentCompanyId,
            'date' => $date,
            'name' => $name !== '' ? $name : null,
        ]
    );

    $newId = (int)$this->db->lastInsertId();
    $this->logActivity('create', 'company_holidays', $newId, null,
        ['holiday_date' => $date, 'name' => $name !== '' ? $name : null]);

    $this->response->setJsonContent([
        'success' => true,
        'is_holiday' => true,
        'date' => $date,
        'name' => $name !== '' ? $name : null,
    ]);
    return $this->response->send();
}
```

#### Toggle Contract

* **Request:** `POST /dashboard/settings/company-holidays/toggle`
  * `date` — `YYYY-MM-DD` (required, validated against `checkdate`).
  * `name` — optional holiday label.
* **Response (success, marked):**
  ```json
  { "success": true, "is_holiday": true,  "date": "2026-01-01", "name": "New Year's Day" }
  ```
* **Response (success, unmarked):**
  ```json
  { "success": true, "is_holiday": false, "date": "2026-01-01" }
  ```
* **Response (error):** non-2xx status with `{ "success": false, "message": "..." }`.

The endpoint is **idempotent in the sense of "toggle by date"**: a row that
already exists is deleted; a row that does not exist is inserted. The
`UNIQUE(company_id, holiday_date)` constraint is the safety net.

---

## 5. Frontend (Volt + Tailwind + Vanilla JS)

The view is `app/views/dashboard/settings/company-holidays.volt`. It extends
`layouts/admin.volt` and uses only Tailwind utility classes plus a single
inline `<script>` — no external JS framework is required.

### 5.1 Layout

* **Header row:** title + subtitle on the left, year navigator on the right.
  The year navigator has prev/next chevrons, an editable year `<input
  type="number">` (clamped client-side to the same range as the server), and
  a "This year" reset link that only appears when the user has navigated
  away from the current year.
* **Legend:** a small row showing the rose (holiday) swatch, the white
  (working day) swatch, and a hint that right-clicking a day opens the
  context menu.
* **Calendar grid:** `#holidaysCalendar` is a responsive Tailwind grid
  (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4`). Each month
  is a white card with the month/year header and a 7-column day grid
  (Sunday-first).
* **Context menu:** `#holidayContextMenu` is a fixed-position floating panel
  that shows either the "Mark" form (optional name input + rose "Mark as
  Company Holiday" button) or the "Unmark" form (current name + slate
  "Unmark Company Holiday" button), depending on whether the right-clicked
  day is already a holiday.

### 5.2 Colour Coding (the visual distinction)

| Day type        | Tailwind classes                                            | Visual            |
| --------------- | ----------------------------------------------------------- | ----------------- |
| Company holiday | `bg-rose-500 text-white font-semibold hover:bg-rose-600`    | Solid rose/red    |
| Weekend (non-holiday) | `text-slate-400 hover:bg-slate-100`                   | Muted grey        |
| Working day     | `text-slate-700 hover:bg-blue-50`                           | Default dark      |

This is the core UX requirement: a Super Admin can scan a month and
instantly spot every company holiday because they are the only solid-colour
cells.

### 5.3 Interaction Model

* **Right-click** (`contextmenu` event) on any day cell opens the context
  menu at the cursor position. The default browser context menu is
  suppressed (`e.preventDefault()`).
* **Left-click** also opens the menu (positioned just below the cell), which
  makes the feature usable on touch devices and trackpads without a right
  click.
* **Mark flow:** the user types an optional name and clicks "Mark as
  Company Holiday" (or presses Enter in the name field). The JS POSTs to
  `toggleUrl`, and on success it adds the date to the in-memory `holidays`
  map and re-renders the calendar. The cell immediately turns rose.
* **Unmark flow:** the user clicks "Unmark Company Holiday". The JS POSTs
  to `toggleUrl`, and on success it removes the date from `holidays` and
  re-renders. The cell reverts to its default colour.
* **Dismissal:** the menu closes on outside click, on Escape, or on the
  in-menu close (×) button.

### 5.4 Client-Side State

The script keeps a single source of truth:

```js
var holidays = {{ holidaysJson }}; // { "2026-01-01": "New Year's Day", ... }
```

After every successful toggle, this object is mutated and
`renderCalendar()` is called to rebuild all 12 month cards from scratch.
This is fast (a year is at most ~366 cells) and avoids fragile per-cell
DOM patching.

### 5.5 AJAX Call

```js
function toggleHoliday(dateStr, name, callback) {
    setBusy(true);
    var body = new URLSearchParams();
    body.append('date', dateStr);
    if (name) body.append('name', name);

    fetch(toggleUrl, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: body
    })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
        .then(function (res) {
            setBusy(false);
            if (!res.ok || !res.json || res.json.success === false) {
                showError((res.json && res.json.message) || genericError);
                return;
            }
            if (res.json.is_holiday) {
                holidays[res.json.date] = res.json.name || '';
            } else {
                delete holidays[res.json.date];
            }
            renderCalendar();
            if (callback) callback();
        })
        .catch(function () {
            setBusy(false);
            showError(genericError);
        });
}
```

Notes for the AI Coder:

* The request is sent as `application/x-www-form-urlencoded` (via
  `URLSearchParams`), which is what Phalcon's `$this->request->getPost()`
  expects. Do **not** switch to JSON without also updating the controller
  to use `getJsonRawBody()`.
* The `X-Requested-With: XMLHttpRequest` header is sent so the server can
  distinguish AJAX calls if needed (the current controller does not require
  it, but it is good hygiene).
* Buttons are disabled while the request is in flight (`setBusy`) to
  prevent double-submits.

---

## 6. Integration Points

The `company_holidays` table is the single source of truth for "is this
date a company holiday for this company?". Downstream systems should query
it directly rather than reimplementing the notion of a holiday.

### 6.1 Helper Query (recommended)

When you need to know whether a date is a company holiday, use a single
bounded lookup:

```php
$isHoliday = (bool) $this->db->fetchOne(
    "SELECT 1 FROM company_holidays
     WHERE company_id = :company_id AND holiday_date = :date LIMIT 1",
    Phalcon\Db\Enum::FETCH_ASSOC,
    ['company_id' => $companyId, 'date' => $date]
);
```

For a date range (e.g. "all holidays that fall inside this leave request"):

```php
$holidaysInRange = $this->db->fetchAll(
    "SELECT holiday_date, name FROM company_holidays
     WHERE company_id = :company_id
       AND holiday_date BETWEEN :start AND :end
     ORDER BY holiday_date ASC",
    Phalcon\Db\Enum::FETCH_ASSOC,
    ['company_id' => $companyId, 'start' => $startDate, 'end' => $endDate]
);
```

### 6.2 Leave Duration Calculation — IMPLEMENTED

The Leave Management engine now excludes Company Holidays (and weekends) from
the requested-minutes calculation for day-range leave modes. The single source
of truth is the static helper:

```php
LeaveRequests::countChargeableDays($db, $companyId, $startDate, $endDate)
```

It counts the chargeable working days in an inclusive `Y-m-d` range, excluding
Saturdays, Sundays, and rows in `company_holidays` for the given company. A day
that is both a weekend and a Company Holiday is excluded only once. It is called
by:

- `DashboardController::leaveRequestsStoreAction` — admin `whole_day` (date
  range) mode.

The `datetime` mode is minute/hour-based and does not apply the
exclusion. If the range contains zero chargeable days (every day is a weekend or
Company Holiday), submission is blocked with a flash error
(`flash.leave_request_no_chargeable_days`).

**Example:** Mon–Wed leave with Tuesday a Company Holiday, `workday_hours = 8`:
`countChargeableDays = 2` → `requested_minutes = 2 × 8 × 60 = 960` → two days
deducted at approval, not three.

See [`Employee-Leave-Management.md`](./Employee-Leave-Management.md) §5.3 and
§11 for the full calculation contract.

### 6.3 Overtime Multiplier Lookup

The Overtime engine already supports per-policy multipliers (see
`Employee-Over-Time.md`). A natural extension is to allow a policy to
declare "applies on company holidays" and then have the approval flow
verify the requested date against `company_holidays` to pick the correct
policy automatically.

---

## 7. Internationalisation

All UI strings are translated via the `t()` Volt helper backed by
`LocaleService`. The keys live in the fragment file
`app/lang/_fragments/settings_translations.php` and are merged into
`app/lang/en.php` and `app/lang/th.php` by
`app/lang/merge_fragments.ps1`.

### 7.1 Key Catalogue

| Key prefix                         | Purpose                                              |
| ---------------------------------- | ---------------------------------------------------- |
| `settings.index.company_holidays`  | Card label on the Settings index page.               |
| `settings.index.company_holidays_desc` | Card description.                                |
| `settings.company_holidays.title`  | Page title.                                          |
| `settings.company_holidays.subtitle` | Page subtitle (the right-click hint).              |
| `settings.company_holidays.prev_year` / `next_year` / `this_year` | Year navigator controls.          |
| `settings.company_holidays.legend_holiday` / `legend_normal` | Legend swatch labels.                |
| `settings.company_holidays.hint_right_click` | Inline hint in the legend.              |
| `settings.company_holidays.name_optional` / `name_placeholder` | Mark form name field.            |
| `settings.company_holidays.mark` / `unmark` | Context menu action buttons.              |
| `settings.company_holidays.current_holiday` / `current_holiday_unnamed` | Unmark form label.        |
| `settings.company_holidays.not_authorized` / `invalid_date` / `error_generic` | AJAX error messages. |
| `settings.company_holidays.month_jan` … `month_dec` | Month headers (localized).            |
| `settings.company_holidays.dow_sun` … `dow_sat` | Weekday headers (localized, 2-letter).   |
| `flash.super_admin_only_company_holidays` | Flash error when a non-Super-Admin hits the page. |

### 7.2 Adding / Changing Keys

1. Edit `app/lang/_fragments/settings_translations.php` and add or modify
   the key with both `en` and `th` values.
2. Run `powershell -ExecutionPolicy Bypass -File
   app/lang/merge_fragments.ps1`. The script appends only *new* keys to
   `en.php` / `th.php`; it will not overwrite existing keys, so changing
   an existing value requires editing `en.php` / `th.php` directly.

---

## 8. Security and Audit

* **Authorisation:** Both the page and the AJAX endpoint call
  `isSuperAdmin()`, which checks `($user['role'] ?? '') === 'Super Admin'`
  on the session `auth` user. Non-Super-Admins get a 403 JSON response
  (AJAX) or a flash-error redirect (page).
* **Multi-tenant isolation:** Every query is scoped by
  `$this->currentCompanyId`, which is resolved and validated by
  `TenantBaseController::initialize()`. A user can never toggle a holiday
  for a company they do not have access to.
* **Input validation:** The `date` POST field is validated with both a
  regex (`^\d{4}-\d{2}-\d{2}$`) and PHP's `checkdate()` to reject
  impossible dates like `2026-02-30`. The `name` field is trimmed and
  capped at 120 characters by the `<input maxlength="120">` on the
  client; the server stores it as-is (it is parameterised, so SQL
  injection is not a concern).
* **Audit trail:** Every mark logs a `create` activity and every unmark
  logs a `delete` activity against `target_table = 'company_holidays'`,
  via the shared `logActivity()` helper on `DashboardController`. This
  writes to the same `activity_logs` table used by the rest of the HR
  platform.

---

## 9. File Inventory

| File | Purpose |
| --- | --- |
| `app/migrations/company_holidays.sql` | DDL for the `company_holidays` table. |
| `app/migrations/run_company_holidays_migration.php` | One-time migration runner. |
| `app/config/router.php` | Adds the two company-holidays routes to `$tenantGroup`. |
| `app/controllers/DashboardController.php` | `companyHolidaysAction()` and `companyHolidaysToggleAction()`. |
| `app/views/dashboard/settings/company-holidays.volt` | The 12-month calendar view + context menu + JS. |
| `app/views/dashboard/settings/index.volt` | Adds the "Company Holidays" card to the Settings index. |
| `app/lang/_fragments/settings_translations.php` | Source of truth for the new translation keys. |
| `app/lang/en.php` / `app/lang/th.php` | Merged output of the fragment file (do not edit the merged sections directly for new keys). |

---

## 10. Future Enhancements (Roadmap)

These are *not* implemented yet, but the schema and routes are designed to
accommodate them:

* **Recurring holidays.** Add a `recurrence` column (`ENUM('none',
  'yearly')`) so "New Year's Day" can be marked once and automatically
  apply every year. The toggle endpoint would then need to expand
  recurring rules into concrete rows for the selected year.
* **Half-day holidays.** Some companies have half-day closures. A
  `duration_minutes` column (defaulting to a full workday) would let the
  Leave engine charge only half a day for those.
* **Holiday import / export.** A CSV import similar to the Leave Balances
  importer would let a Super Admin bulk-load a year's holidays at once.
* **Regional holiday presets.** A "Load Thai public holidays for {year}"
  button that pre-populates the calendar from a curated dataset, which
  the Super Admin can then amend.

> **Implemented:** Holiday-aware leave calculation is now live —
> `LeaveRequests::countChargeableDays()` excludes Company Holidays (and
> weekends) from day-range leave requests. See section 6.2 for details.

When implementing any of these, follow the existing patterns: gate new
write endpoints with `isSuperAdmin()`, scope every query by
`company_id`, log every mutation via `logActivity()`, and add translation
keys to the fragment file before re-running the merge script.
