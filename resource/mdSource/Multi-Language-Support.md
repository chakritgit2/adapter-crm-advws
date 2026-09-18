# Multi-Language Support (Thai / English) — Implementation Blueprint

This blueprint adds **UI string localization (i18n)** to the HR System so the
entire administrative interface can be rendered in **Thai (default)** or
**English**, while reusing the existing per-tenant language switcher and the
already-shipped **Polymorphic Translation Ledger** (which translates *database
record values* such as employee names and leave-type names).

> **Two distinct layers of "translation" coexist in this system. Do not confuse them:**
>
> | Layer | What it translates | Where it lives | Status |
> | --- | --- | --- | --- |
> | **Layer A — UI Strings (i18n)** | Interface text: headings, buttons, labels, nav items, flash messages, validation errors | `app/lang/*.php` message catalogs + `LocaleService` | **NEW (this blueprint)** |
> | **Layer B — Entity Content** | Database record field values (e.g. an employee's `first_name`, a leave type's `name`) | `translations` + `tenant_languages` tables, `TranslationService` | **Already implemented** |
>
> Both layers share the **same active language** (stored in `session.active_language`)
> and the **same language switcher** in `admin.volt`, so the user perceives a single,
> unified language choice.

---

## 1. Design Principles

1. **Thai is the default.** When no language has been explicitly chosen (fresh
   session, or the user picks the "default" entry), the UI renders in Thai (`th`).
   English (`en`) becomes an opt-in alternative rather than the baseline.
2. **File-based message catalogs**, not the database. UI strings are static,
   shared across all tenants, and shipped with the codebase — they belong in
   version-controlled PHP arrays, not in a per-tenant table. (Layer B remains
   database-driven because entity content *is* per-tenant.)
3. **One translation key per string.** Keys are dot-notation, grouped by
   feature area (e.g. `dashboard.title`, `leave.balance.remaining`, `common.save`).
4. **Surgical integration.** Reuse the existing `session.active_language`
   convention, the existing language switcher, and the existing Volt compiler
   function-registration hook in `services.php`. No new routing paradigm, no
   new session key.
5. **Graceful fallback.** If a key is missing in the active language, fall back
   to English; if missing in English, return the key itself. The UI never
   breaks because of a missing string.
6. **Phalcon-native.** Use `Phalcon\Translate\Adapter\NativeArray` as the
   adapter, wrapped by a thin `LocaleService` so controllers/views have a
   single, testable entry point.

---

## 2. Target Language Configuration

Add a top-level `languages` block to `app/config/config.php`. This is the
single source of truth for which UI locales ship with the application and
which one is the default.

```php
// app/config/config.php  (add alongside the existing 'localization' block)

// UI Localization (Layer A — interface strings)
'languages' => [
    'default' => 'th',          // <-- Thai is the default UI language
    'fallback' => 'en',         // used when a key is missing in the active lang
    'supported' => [
        'th' => [
            'name'    => 'ภาษาไทย',
            'english' => 'Thai',
            'locale'  => 'th_TH',
            'dir'     => 'ltr',
        ],
        'en' => [
            'name'    => 'English',
            'english' => 'English',
            'locale'  => 'en_US',
            'dir'     => 'ltr',
        ],
    ],
],

// The existing 'localization.allowed_targets' block (Layer B) stays unchanged.
```

**Why `default => 'th'`?** Every place that resolves the active language
previously assumed `en` was the baseline (e.g. `setLanguageAction` removed the
session key for `en`; the switcher labelled English as "(Default)"). This
blueprint inverts that: the *absence* of a session value now means Thai.

---

## 3. Message Catalog Files

Create a new directory `app/lang/` containing one PHP file per supported
locale. Each file returns a flat, dot-notation-keyed associative array.

```
app/
└── lang/
    ├── th.php      ← Thai  (DEFAULT — must be the most complete)
    └── en.php      ← English (fallback)
```

### 3.1 File format

```php
// app/lang/th.php
return [
    // ---- common / shared ----
    'common.save'              => 'บันทึก',
    'common.cancel'            => 'ยกเลิก',
    'common.delete'            => 'ลบ',
    'common.edit'              => 'แก้ไข',
    'common.back'              => 'ย้อนกลับ',
    'common.search'            => 'ค้นหา',
    'common.actions'           => 'การจัดการ',
    'common.confirm_delete'    => 'คุณแน่ใจหรือว่าต้องการลบรายการนี้?',
    'common.required'          => 'จำเป็นต้องกรอก',

    // ---- auth / login ----
    'auth.login'               => 'เข้าสู่ระบบ',
    'auth.logout'              => 'ออกจากระบบ',
    'auth.email'               => 'อีเมล',
    'auth.password'            => 'รหัสผ่าน',

    // ---- dashboard ----
    'dashboard.title'          => 'แดชบอร์ด',
    'dashboard.subtitle'       => 'ภาพรวมองค์กรที่ใช้งานอยู่',
    'dashboard.active_companies'  => 'องค์กรที่ใช้งาน',
    'dashboard.total_employees'   => 'พนักงานทั้งหมด',
    'dashboard.total_positions'   => 'ตำแหน่งทั้งหมด',
    'dashboard.avg_employees'     => 'พนักงานเฉลี่ย / องค์กร',

    // ---- leave management ----
    'leave.title'              => 'การจัดการการลา',
    'leave.balance.remaining'  => 'วันลาคงเหลือ',
    'leave.balance.used'       => 'วันลาที่ใช้แล้ว',
    'leave.balance.allowance'  => 'สิทธิวันลา',
    'leave.request.submit'     => 'ส่งคำขอลา',
    'leave.request.status.pending'  => 'รอดำเนินการ',
    'leave.request.status.approved' => 'อนุมัติแล้ว',
    'leave.request.status.rejected' => 'ปฏิเสธ',

    // ---- settings / languages ----
    'settings.languages.title' => 'ภาษา',
    'settings.languages.add'   => 'เพิ่มภาษา',
    'settings.languages.code'  => 'รหัสภาษา',
    'settings.languages.name'  => 'ชื่อภาษา',
    'settings.languages.install' => 'ติดตั้งภาษา',

    // ---- translations (Layer B admin UI) ----
    'translations.title'       => 'การแปล',
    'translations.subtitle'    => 'แปลข้อมูลในองค์กรของคุณเป็นภาษาที่ติดตั้งไว้',
    'translations.browse'      => 'เลือกข้อมูลเพื่อแปล',
    'translations.no_languages'=> 'ยังไม่มีภาษาที่ติดตั้งไว้',

    // ---- flash messages ----
    'flash.saved'              => 'บันทึกเรียบร้อยแล้ว',
    'flash.deleted'            => 'ลบเรียบร้อยแล้ว',
    'flash.error'              => 'เกิดข้อผิดพลาด กรุณาลองอีกครั้ง',
    'flash.language_installed' => 'ติดตั้งภาษาเรียบร้อยแล้ว',
    'flash.language_removed'   => 'ลบภาษาและคำแปลที่เกี่ยวข้องเรียบร้อยแล้ว',
];
```

```php
// app/lang/en.php   (English — fallback, mirrors the same keys)
return [
    'common.save'              => 'Save',
    'common.cancel'            => 'Cancel',
    'common.delete'            => 'Delete',
    'common.edit'              => 'Edit',
    'common.back'              => 'Back',
    'common.search'            => 'Search',
    'common.actions'           => 'Actions',
    'common.confirm_delete'    => 'Are you sure you want to delete this item?',
    'common.required'          => 'Required',

    'auth.login'               => 'Sign In',
    'auth.logout'              => 'Sign Out',
    'auth.email'               => 'Email',
    'auth.password'            => 'Password',

    'dashboard.title'          => 'Dashboard',
    'dashboard.subtitle'       => 'Overview of your active organizations.',
    'dashboard.active_companies'  => 'Active Companies',
    'dashboard.total_employees'   => 'Total Employees',
    'dashboard.total_positions'   => 'Total Positions',
    'dashboard.avg_employees'     => 'Avg. Employees / Company',

    'leave.title'              => 'Leave Management',
    'leave.balance.remaining'  => 'Remaining Leave',
    'leave.balance.used'       => 'Used Leave',
    'leave.balance.allowance'  => 'Leave Allowance',
    'leave.request.submit'     => 'Submit Leave Request',
    'leave.request.status.pending'  => 'Pending',
    'leave.request.status.approved' => 'Approved',
    'leave.request.status.rejected' => 'Rejected',

    'settings.languages.title' => 'Languages',
    'settings.languages.add'   => 'Add Language',
    'settings.languages.code'  => 'Language Code',
    'settings.languages.name'  => 'Language Name',
    'settings.languages.install' => 'Install Language',

    'translations.title'       => 'Translations',
    'translations.subtitle'    => 'Translate fields across your organization into installed languages.',
    'translations.browse'      => 'Browse records to translate',
    'translations.no_languages'=> 'No languages installed',

    'flash.saved'              => 'Saved successfully.',
    'flash.deleted'            => 'Deleted successfully.',
    'flash.error'              => 'An error occurred. Please try again.',
    'flash.language_installed' => 'Language installed successfully.',
    'flash.language_removed'   => 'Language and all associated translations have been removed.',
];
```

### 3.2 Key naming convention

- **`<area>.<noun>`** for labels/headings: `dashboard.title`, `leave.balance.remaining`.
- **`<area>.<verb>`** for buttons/actions: `leave.request.submit`, `common.save`.
- **`<area>.<state>`** for enums/statuses: `leave.request.status.pending`.
- **`flash.<event>`** for user-facing flash messages: `flash.saved`, `flash.language_installed`.
- **`common.<word>`** for strings reused across many pages: `common.cancel`, `common.required`.

Keep keys in **English snake/dot case** (never Thai) so developers can always
locate a string regardless of the active language.

---

## 4. The LocaleService

A thin service that resolves the active language, loads the correct message
catalog, and exposes a `t()` (translate) method. It is registered in the DI
container so controllers and the Volt compiler can reach it.

```php
// app/services/LocaleService.php
<?php
declare(strict_types=1);

use Phalcon\Di\Injectable;
use Phalcon\Translate\Adapter\NativeArray;

class LocaleService extends Injectable
{
    protected NativeArray $translator;
    protected string $activeLang;
    protected string $fallbackLang;

    public function __construct()
    {
        $config = $this->getDI()->get('config');
        $this->fallbackLang = $config->path('languages.fallback', 'en');
        $default            = $config->path('languages.default', 'th');

        // Resolve active language: explicit session choice > default (Thai)
        $sessionLang = $this->session->get('active_language');
        $this->activeLang = $this->isSupported($sessionLang) ? $sessionLang : $default;

        $this->translator = $this->loadCatalog($this->activeLang);
    }

    /** Translate a key, with optional sprintf-style placeholders. */
    public function t(string $key, array $params = []): string
    {
        $value = $this->translator->_($key, $params);
        // NativeArray returns the key itself when missing — fall back if needed
        if ($value === $key && $this->activeLang !== $this->fallbackLang) {
            $fallback = $this->loadCatalog($this->fallbackLang);
            $value = $fallback->_($key, $params);
        }
        return $value;
    }

    public function getActiveLanguage(): string
    {
        return $this->activeLang;
    }

    public function getHtmlLang(): string
    {
        return $this->activeLang; // 'th' or 'en'
    }

    public function getLocale(): string
    {
        return $this->getDI()->get('config')
            ->path("languages.supported.{$this->activeLang}.locale", 'th_TH');
    }

    public function getDirection(): string
    {
        return $this->getDI()->get('config')
            ->path("languages.supported.{$this->activeLang}.dir", 'ltr');
    }

    public function isSupported(?string $code): bool
    {
        if ($code === null || $code === '') {
            return false;
        }
        $supported = $this->getDI()->get('config')->path('languages.supported');
        return $supported && $supported->offsetExists($code);
    }

    protected function loadCatalog(string $lang): NativeArray
    {
        $file = APP_PATH . '/lang/' . $lang . '.php';
        $messages = file_exists($file) ? require $file : [];
        return new NativeArray(['content' => $messages]);
    }
}
```

### 4.1 Register in the DI container

Add to `app/config/services.php`, near the other shared services:

```php
// app/config/services.php

/**
 * Locale / UI translation service (Layer A — interface strings)
 */
$di->setShared('locale', function () {
    return new LocaleService();
});
```

---

## 5. Register the `t()` Volt Helper

The Volt compiler in `services.php` already registers custom functions
(`date`, `format_minutes`, `json_encode`, etc.). Add `t()` in the same block so
every Volt template can call `{{ t('dashboard.title') }}`.

```php
// app/config/services.php — inside the '.volt' closure, after the existing
// $compiler->addFunction(...) calls:

// UI translation helper:  {{ t('dashboard.title') }}
//                         {{ t('leave.balance.remaining', ['name': 'Annual']) }}
$compiler->addFunction('t', function ($resolvedArgs, $exprArgs) {
    // Delegates to the shared 'locale' DI service at render time.
    return '$this->getDI()->get(\'locale\')->t(' . $resolvedArgs . ')';
});
```

> **Why a function and not a filter?** A function (`t('key')`) keeps the
> translation key visually first, matching the convention used by every major
> i18n library. It also accepts a second optional array argument for
> placeholders without conflicting with Volt's filter chaining.

---

## 6. Wire the Active Language Into the View Layer

### 6.1 `TenantBaseController::initialize()`

The base controller already loads `tenantLanguages` for the company and sets
view vars. Extend it to also expose the resolved locale and the `t()`-capable
service to every view.

```php
// app/controllers/TenantBaseController.php — inside initialize(), after the
// existing $this->view->setVar('tenantLanguages', ...) block:

/** @var LocaleService $locale */
$locale = $this->getDI()->get('locale');
$this->view->setVar('locale', $locale);
$this->view->setVar('activeLanguage', $locale->getActiveLanguage());
$this->view->setVar('htmlLang', $locale->getHtmlLang());
$this->view->setVar('htmlDir', $locale->getDirection());
```

Because `LocaleService` reads `session.active_language` in its constructor,
this single registration makes the active language available to **every**
tenant/company-scoped page with no per-controller changes.

### 6.2 Non-tenant controllers (login, index, error)

Controllers that do not extend `TenantBaseController` (e.g. `LoginController`,
`IndexController`) should still resolve the locale. Add the same four
`setVar()` calls in their `initialize()` methods, or — to avoid duplication —
move those calls into a tiny trait:

```php
// app/traits/HasLocale.php
<?php
declare(strict_types=1);

trait HasLocale
{
    protected function shareLocaleToView(): void
    {
        $locale = $this->getDI()->get('locale');
        $this->view->setVar('locale', $locale);
        $this->view->setVar('activeLanguage', $locale->getActiveLanguage());
        $this->view->setVar('htmlLang', $locale->getHtmlLang());
        $this->view->setVar('htmlDir', $locale->getDirection());
    }
}
```

Then call `$this->shareLocaleToView();` from `TenantBaseController::initialize()`
and from each non-tenant controller's `initialize()`.

---

## 7. Update the Layout

### 7.1 Dynamic `<html lang>` and font

`admin.volt` currently hardcodes `<html lang="th">`. Make it dynamic:

```volt
{# app/views/layouts/admin.volt — line 2 #}
<html lang="{{ htmlLang|default('th') }}" dir="{{ htmlDir|default('ltr') }}">
```

The Noto Sans Thai font already loaded in the `<head>` covers both Thai and
Latin glyphs, so no font swap is needed when switching languages.

### 7.2 Language switcher — Thai as the default entry

The existing switcher in `admin.volt` (around line 336) lists "English
(Default)" first and treats `en` as the baseline. Reorder it so **Thai is the
default** and `en` is the alternative. The hidden input's default value must
also change from `'en'` to `'th'`.

```volt
{# admin.volt — language switcher form #}
<form id="languageSwitcherForm"
      action="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/translations/set-language"
      method="POST" class="flex items-center gap-2">
    <input type="hidden" name="language_code" id="languageCodeInput"
           value="{{ activeLanguage|default('th') }}">

    {# ... current-language label block stays, but default name becomes Thai #}
    {% set currentLangName = 'ภาษาไทย' %}
    {# ... rest of label resolution unchanged ... #}

    <div class="absolute right-0 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
        {# Thai is now the default entry #}
        <a href="javascript:void(0)"
           onclick="document.getElementById('languageCodeInput').value='th';document.getElementById('languageSwitcherForm').submit()"
           class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
            <i class="fas fa-globe mr-2 text-slate-400"></i> ภาษาไทย (ค่าเริ่มต้น)
        </a>
        <a href="javascript:void(0)"
           onclick="document.getElementById('languageCodeInput').value='en';document.getElementById('languageSwitcherForm').submit()"
           class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
            <i class="fas fa-globe mr-2 text-slate-400"></i> English
        </a>
    </div>
</form>
```

> The tenant-installed languages from `tenantLanguages` (Layer B) can still be
> listed below these two UI locales, but they only affect entity-content
> translation, not the interface. Keep the two groups visually separated
> (e.g. a divider labelled "Content translations") so users understand the
> difference.

---

## 8. Update `setLanguageAction` — Thai Is the Default

`TranslationController::setLanguageAction()` currently removes the session key
when `en` is chosen (treating English as the default/no-op). Invert the logic
so **Thai is the no-op default** and any *other* supported language is stored
explicitly.

```php
// app/controllers/TranslationController.php
public function setLanguageAction()
{
    $code = trim($this->request->getPost('language_code', 'string', ''));
    $config = $this->getDI()->get('config');
    $default = $config->path('languages.default', 'th');
    $supported = $config->path('languages.supported');

    // 1. UI locale: only the two supported UI languages (th/en) set the session.
    if ($supported && $supported->offsetExists($code)) {
        if ($code === $default) {
            $this->session->remove('active_language');   // default = no session entry
        } else {
            $this->session->set('active_language', $code);
        }
    }

    // 2. Entity-content locale (Layer B): a tenant-installed language that is
    //    NOT a UI language still sets active_language so TranslationService
    //    resolves translated record values. Keep the existing tenant check.
    if (!$this->locale->isSupported($code) && $code !== '' && $code !== 'en') {
        if (!$this->translationService->isLanguageInstalled($this->currentCompanyId, $code)) {
            $this->flashSession->error($this->locale->t('flash.language_not_installed'));
            $this->response->redirect($_SERVER['HTTP_REFERER'] ?? $this->tenantUrl('/dashboard'));
            return;
        }
        $this->session->set('active_language', $code);
    }

    $this->response->redirect($_SERVER['HTTP_REFERER'] ?? $this->tenantUrl('/dashboard'));
}
```

---

## 9. Translate Flash Messages in Controllers

Controller flash messages are currently hardcoded English strings. Replace
each with a `t()` call through the `locale` service.

**Before:**
```php
$this->flashSession->error('Language code and name are required.');
$this->flashSession->success('Language installed successfully.');
```

**After:**
```php
$this->flashSession->error($this->locale->t('flash.language_required'));
$this->flashSession->success($this->locale->t('flash.language_installed'));
```

Add the corresponding keys to both `app/lang/th.php` and `app/lang/en.php`.
Apply this pattern to every controller that emits flash messages
(`TranslationController`, `EmployeeController`, `LeaveController`, etc.).

> **Tip:** Because `LocaleService` is DI-registered, controllers access it as
> `$this->locale` (Phalcon auto-resolves shared services as properties).
> Verify the magic property resolution works in your Phalcon 5.8 setup; if not,
> use `$this->getDI()->get('locale')->t(...)`.

---

## 10. Migrate Existing Volt Templates

This is the largest mechanical task. Every hardcoded English string in
`app/views/` must be wrapped in `{{ t('...') }}`.

### 10.1 Worked example — `dashboard/index.volt`

**Before:**
```volt
<h1 class="text-2xl font-bold text-slate-900 notosan">Dashboard</h1>
<p class="text-sm text-slate-500 mt-1 notosan">Overview of your active organizations.</p>
...
<p class="text-xs text-slate-500 font-medium">Active Companies</p>
...
<p class="text-xs text-slate-500 font-medium">Total Employees</p>
```

**After:**
```volt
<h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('dashboard.title') }}</h1>
<p class="text-sm text-slate-500 mt-1 notosan">{{ t('dashboard.subtitle') }}</p>
...
<p class="text-xs text-slate-500 font-medium">{{ t('dashboard.active_companies') }}</p>
...
<p class="text-xs text-slate-500 font-medium">{{ t('dashboard.total_employees') }}</p>
```

### 10.2 Rollout order (highest traffic first)

1. `layouts/admin.volt` — nav, sidebar, top bar, language switcher, footer.
2. `dashboard/index.volt` — main landing page.
3. `dashboard/companies*.volt` — company list and company dashboard.
4. `dashboard/reports/*.volt` — HR report screens.
5. `dashboard/settings/*.volt` — settings screens.
6. `partials/*.volt`, `login/*.volt`, `error/*.volt`.

### 10.3 What NOT to translate

- **Dynamic data values** (employee names, leave-type names, etc.) — these are
  handled by Layer B (`TranslationService::getTranslatedValue`). In Volt, render
  the already-resolved display value, e.g. `{{ employee.display_first_name }}`.
- **CSS class names, IDs, data attributes, JavaScript identifiers.**
- **Enum values stored in the DB** (e.g. `status = 'pending'`). Translate only
  the *display label*: `{{ t('leave.request.status.' ~ request.status) }}`.

---

## 11. Date & Number Formatting per Locale

The Volt compiler already registers `date`, `date_time`, and `format_minutes`
helpers that use fixed English formats (`"F j, Y"`). For full localization,
add locale-aware variants that consult `LocaleService::getLocale()`.

```php
// app/config/services.php — Volt compiler block
$compiler->addFunction('date_localized', function ($resolvedArgs) {
    // date_localized(value) -> uses the active locale at render time
    return '\\format_date_localized(' . $resolvedArgs . ', $this->getDI()->get(\'locale\')->getLocale())';
});
```

```php
// app/helpers/LocaleFormat.php  (new helper, autoloaded from helpersDir)
<?php
declare(strict_types=1);

function format_date_localized(string $value, string $locale): string
{
    $ts = strtotime($value);
    if ($ts === false) {
        return $value;
    }
    $formatter = new \IntlDateFormatter(
        $locale,
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::NONE,
        date_default_timezone_get(),
        \IntlDateFormatter::GREGORIAN
    );
    return $formatter->format($ts);
}
```

> Requires the PHP `intl` extension. If unavailable on the server, fall back to
> the existing `date()` helper — Thai users can still read English-formatted
> dates, and the rest of the UI is fully Thai.

---

## 12. Seed Thai as the Default Tenant Language (Layer B)

So that entity-content translation also defaults to Thai, seed the
`tenant_languages` table with a `th` row for every existing company that does
not yet have one.

```sql
-- app/migrations/1.0.0/seed_default_thai_language.sql
INSERT INTO tenant_languages (company_id, language_code, language_name, is_active)
SELECT c.id, 'th', 'ภาษาไทย', 1
FROM companies c
WHERE NOT EXISTS (
    SELECT 1 FROM tenant_languages tl
    WHERE tl.company_id = c.id AND tl.language_code = 'th'
);
```

Run this once during deployment. New companies created after this should also
auto-install `th` — add that to the company-creation service/controller.

---

## 13. Verification Checklist

Each item is a pass/fail gate before the feature ships.

- [ ] `app/lang/th.php` and `app/lang/en.php` exist and return arrays with
      identical key sets.
- [ ] `LocaleService` is registered as `locale` in the DI container.
- [ ] `t()` Volt function is registered and `{{ t('common.save') }}` renders
      "บันทึก" when the active language is Thai.
- [ ] A fresh session (no `active_language`) renders the dashboard in **Thai**.
- [ ] Switching to English via the language switcher renders the dashboard in
      English and persists across page loads.
- [ ] Switching back to Thai clears the session key and the UI returns to Thai.
- [ ] `<html lang="...">` reflects the active language (`th` or `en`).
- [ ] Flash messages from `TranslationController` appear in the active language.
- [ ] A missing key in `th.php` falls back to the English value, not the raw key.
- [ ] Layer B (entity-content translation) still works: switching language
      shows translated employee/leave-type names where translations exist.
- [ ] No hardcoded English UI string remains in `layouts/admin.volt` or
      `dashboard/index.volt`.

---

## 14. Phased Implementation Plan

| Phase | Scope | Exit criteria |
| --- | --- | --- |
| **1 — Foundation** | Config block, `app/lang/th.php` + `en.php`, `LocaleService`, DI registration, `t()` Volt function, `HasLocale` trait. | `{{ t('common.save') }}` works in a throwaway Volt test page; fresh session resolves to Thai. |
| **2 — Layout & Switcher** | `admin.volt` `<html lang>`, language switcher reordered (Thai default), nav/sidebar/footer strings wrapped in `t()`. | Full chrome of every page renders in Thai by default; switching to English updates the chrome. |
| **3 — Dashboard & Core Pages** | `dashboard/index.volt`, employee list/action, leave screens, position list, org hierarchy. | All high-traffic pages fully localized. |
| **4 — Settings & Layer B Admin** | `dashboard/settings/*.volt`, `dashboard/translations-*.volt`; controller flash messages converted to `t()`. | Settings and translation-management screens localized. |
| **5 — Auth & Error Pages** | `login/*.volt`, `error/*.volt`, `partials/*.volt`. | Non-tenant pages localized. |
| **6 — Locale-aware Formatting** | `date_localized`/`format_date_localized` helper (requires `intl`); seed `th` into `tenant_languages`. | Dates render in Thai long format on Thai UI; Layer B defaults to Thai. |

---

## 15. File Inventory (new + modified)

**New files:**
- `app/lang/th.php` — Thai UI message catalog (default).
- `app/lang/en.php` — English UI message catalog (fallback).
- `app/services/LocaleService.php` — resolves active language + `t()`.
- `app/traits/HasLocale.php` — view-var sharing for non-tenant controllers.
- `app/helpers/LocaleFormat.php` — `format_date_localized()` helper.
- `app/migrations/1.0.0/seed_default_thai_language.sql` — seed Layer B default.

**Modified files:**
- `app/config/config.php` — add `languages` block (default `th`).
- `app/config/services.php` — register `locale` service + `t()` Volt function.
- `app/controllers/TenantBaseController.php` — share locale view vars.
- `app/controllers/TranslationController.php` — `setLanguageAction` Thai-default logic; flash messages via `t()`.
- `app/views/layouts/admin.volt` — dynamic `<html lang>`, reordered switcher, `t()` wrapping.
- `app/views/dashboard/*.volt` — wrap hardcoded strings in `t()`.
- `app/views/dashboard/settings/*.volt` — wrap hardcoded strings in `t()`.
- `app/views/partials/*.volt`, `app/views/login/*.volt`, `app/views/error/*.volt` — wrap strings in `t()`.
- Every controller emitting flash messages — convert to `$this->locale->t('...')`.

---

## 16. Relationship to the Existing Polymorphic Translation System

This blueprint **does not replace** the existing Layer B system documented in
[`02-Polymorphic-Translation-System.md`](../mdSourceWorkflow/02-Polymorphic-Translation-System.md)
and the `translations` / `tenant_languages` schema in
[`Database-Schema.md`](./Database-Schema.md). The two layers cooperate:

- **Layer A (this blueprint)** localizes the *interface chrome* — the buttons,
  labels, and messages that are identical for every tenant.
- **Layer B (existing)** localizes *tenant-specific data* — the actual names of
  employees, positions, and leave types that differ per company.
- Both layers read the **same** `session.active_language` value, so a single
  language switch updates the interface *and* the displayed entity content
  simultaneously.
- `TranslationService::getTranslatedValue()` already falls back to the original
  column value when no translation exists; Layer A's `LocaleService::t()` falls
  back to English then to the key. The two fallback chains are independent and
  safe.

This separation keeps static UI strings in version control (fast, cacheable,
shared) while keeping dynamic tenant data in the database (flexible,
per-company, admin-editable) — the correct home for each kind of text.
