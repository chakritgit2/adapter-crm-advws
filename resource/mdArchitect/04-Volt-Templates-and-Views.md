# Volt Templates and Views

This document details the Volt template engine configuration, view directory structure, layout hierarchy, custom compiler functions, and frontend integration practices.

---

## View Engine Configuration

The view service is registered in `app/config/services.php` with two registered engines:

```php
$view->registerEngines([
    '.volt' => function ($view) { ... },
    '.phtml' => PhpEngine::class
]);
```

### Volt Options

```php
$volt->setOptions([
    'path' => $config->application->cacheDir,
    'separator' => '_',
    'compiledPath' => $config->application->cacheDir,
    'always' => true,  // Disable caching for development
]);
```

- **`always => true`** — Forces Volt to recompile templates on every request. This is a development setting that should be set to `false` in production for performance.
- Compiled templates are stored in `BASE_PATH/cache/`.
- The `separator` option controls the compiled filename structure.

---

## View Directory Structure

```
app/views/
├── layouts/
│   ├── admin.volt          # Main admin layout (828 lines) — sidebar, header, Tailwind
│   ├── main.volt           # Public-facing layout (247 lines) — login/landing pages
│   ├── auth.volt           # Authentication layout — login forms
│   └── loginwrap.volt      # Login wrapper layout
├── dashboard/
│   ├── index.volt          # Companies dashboard (KPI cards)
│   ├── companies.volt      # Company list
│   ├── companies-dashboard.volt  # Company dashboard
│   ├── companies-action.volt     # Company create/edit form
│   ├── change-password.volt      # Forced password change
│   ├── help-center.volt          # Help center (Super Admin)
│   ├── profile.volt              # User profile
│   ├── reports/                  # HR report views (index, workforce, leave, overtime, turnover)
│   └── settings/                 # Settings sub-views (17 files)
│       ├── index.volt
│       ├── admin-users-*.volt
│       ├── company-holidays.volt
│       ├── custom-attributes-*.volt
│       ├── job-levels-*.volt
│       ├── languages-*.volt
│       ├── leave-policies-*.volt
│       ├── milestone-event-types-*.volt
│       ├── overtime-policies-*.volt
│       └── cssused.volt          # Tailwind class safelist
├── partials/
│   ├── flash.volt          # Flash message rendering
│   ├── index-footer.volt   # Footer partial
│   ├── index-nav.volt      # Navigation bar
│   └── profile-menu.volt   # Profile dropdown menu
├── login/
│   └── (login views)
├── error/
│   └── (error pages)
└── index/
    └── (landing page views)
```

---

## Layout Hierarchy and Template Inheritance

### Volt Template Inheritance Pattern

Views extend layouts using Volt's `{% extends %}` directive:

```volt
{# dashboard/index.volt #}
{% extends 'layouts/admin.volt' %}

{% block content %}
<main class="flex-1">
    ...
</main>
{% endblock %}
```

### Layout Structure

The **`admin.volt`** layout (primary layout for all authenticated pages) contains:

1. **HTML head** — meta tags, title (`{{ title ~ " - " ~ config.appName }}`), pre-main fallback styles
2. **Pre-main CSS fallback** — Hardcoded CSS for Tailwind classes that the scanner may miss (filter pills, dynamic conditional classes)
3. **Body** — Sidebar navigation, top header, `{% block content %}` block, JavaScript includes
4. **Tailwind CSS** — Linked from `/css/main.css` (compiled by Tailwind CLI)
5. **Font Awesome** — Icon library
6. **Noto Sans Thai** — Google Fonts for Thai language support

The **`main.volt`** layout is used for public/unauthenticated pages with:
- Full HTML head with favicon set, Google Fonts, Microsoft Clarity (disabled)
- Toast notification CSS styles
- `.notosan` font-family utility class

---

## Custom Volt Compiler Functions

The Volt compiler is extended with custom functions registered in `services.php`. These functions map Volt template calls to PHP code:

### Server and URL Functions

| Volt Function | PHP Output | Purpose |
|---------------|-----------|---------|
| `server('key')` | `$_SERVER['key']` | Access server variables |
| `urldecode($val)` | `urldecode($val)` | URL-decode a string |
| `http_build_query($arr)` | `http_build_query($arr)` | Build query string from array |

### Formatting Functions

| Volt Function | PHP Output | Purpose |
|---------------|-----------|---------|
| `number_format($val)` | `number_format($val, ".", ",")` | Number formatting |
| `format_minutes($mins)` | `format_minutes($mins)` | Minutes to human-readable duration |
| `bankAccountNumberDisplay($val)` | `substr($val,0,3)."-".substr($val,3,3)."-".substr($val,6)` | Bank account formatting |
| `contains_text($haystack, $needle)` | `mb_stripos($haystack, $needle)` | Case-insensitive substring search |

### Date and Time Functions

| Volt Function | PHP Output | Format |
|---------------|-----------|--------|
| `date($val)` | `date("F j, Y", strtotime($val))` | "August 10, 2026" |
| `date_time($val)` | `date("F j, Y H:i", strtotime($val))` | "August 10, 2026 14:30" |
| `time($val)` | `date("g:i A", strtotime($val))` | "2:30 PM" |
| `datetimeLocal($val)` | `date("Y-m-d\TH:i", strtotime($val))` | HTML datetime-local input format |
| `from_unixtime($val)` | `date("Y-m-d H:i", $val)` | Unix timestamp to datetime |
| `from_unixtime_dateonly($val)` | `date("Y-m-d", $val)` | Unix timestamp to date only |
| `from_unixtime_timeonly($val)` | `date("H:i", $val)` | Unix timestamp to time only |

### Math Functions

| Volt Function | PHP Output |
|---------------|-----------|
| `ceil($val)` | `ceil($val)` |
| `floor($val)` | `floor($val)` |
| `round($val)` | `round($val)` |

### Encoding Functions

| Volt Function | PHP Output |
|---------------|-----------|
| `base64_encode($val)` | `base64_encode($val)` |
| `json_encode($val)` | `json_encode($val)` |
| `json_encode_beautiful($val)` | `json_encode($val, JSON_PRETTY_PRINT \| JSON_UNESCAPED_SLASHES \| JSON_UNESCAPED_UNICODE)` |

### String Functions

| Volt Function | PHP Output |
|---------------|-----------|
| `basename($val)` | `basename($val)` |
| `str_replace($search, $replace, $subject)` | `str_replace($search, $replace, $subject)` |
| `strtotime($val)` | `strtotime($val)` |

### Custom Filter

| Volt Filter | PHP Output |
|-------------|-----------|
| `replace` | `strtr($resolvedArgs)` |

---

## Tailwind CSS 4 Integration

### Build Pipeline

Tailwind CSS 4 is compiled using the standalone CLI binary (not PostCSS):

- **Source:** `resource/css/main.css`
- **Output:** `public/css/main.css`
- **Dev mode:** `./tailwindcss-linux-x64 -i ../resource/css/main.css -o ../public/css/main.css --watch --minify &`
- **Prod mode:** `./tailwindcss-linux-x64 -i ../resource/css/main.css -o ../public/css/main.css --minify`

### Pre-Main Fallback Styles

The `admin.volt` layout includes a `<style>` block with hardcoded CSS for Tailwind classes that the scanner may miss. This covers:

- **Filter pills** — `.filter-pill` class with color variants (blue, slate, emerald, red, amber, purple)
- Dynamic/conditional classes used in Volt `{% if %}` blocks or constructed in JavaScript

### CSS Safelist

A dedicated `settings/cssused.volt` partial contains Tailwind classes that need to be safelisted for the scanner to detect them. This ensures dynamically generated class names are included in the compiled CSS output.

---

## View Variables Passed from Controllers

Controllers set view variables using `$this->view->setVar()` or `$this->view->setVars()`. Common variables available across all tenant-aware views:

| Variable | Type | Source | Purpose |
|----------|------|--------|---------|
| `user` | object | Session auth | Current authenticated user |
| `currentTenantId` | int | TenantBaseController | Active tenant ID |
| `currentTenantSlug` | string | TenantBaseController | Active tenant URL slug |
| `currentTenant` | object | TenantBaseController | Active tenant company data |
| `currentPathSuffix` | string | TenantBaseController | URL path after slug (for nav highlighting) |
| `userCompanies` | array | TenantBaseController | All companies user can access |
| `isCompaniesPage` | bool | TenantBaseController | Whether current page is companies dashboard |
| `tenantLanguages` | array | TenantBaseController | Active languages for current tenant |
| `title` | string | Individual controllers | Page title for layout |
| `config` | Config | DI container | Global config object |

---

## Volt Template Practices

### Conditional Rendering

```volt
{% if companies|length > 0 %}
    {# render company list #}
{% else %}
    {# render empty state #}
{% endif %}
```

### Loop Iteration

```volt
{% for count in positionCounts %}
    {% set totalPositions = totalPositions + count %}
{% endfor %}
```

### Variable Assignment

Volt uses `{% set %}` for inline variable assignment within templates:

```volt
{% set totalPositions = 0 %}
{% set clarityOn = false %}
```

### Config Access

Configuration values are accessed via the `config` variable:

```volt
<title>{{ title ~ " - " ~ config.appName }}</title>
<meta name="description" content="{{ config.appDescription }}">
```

### View Picking

Controllers can explicitly choose which view to render:

```php
$this->view->pick('dashboard/settings/languages');
$this->view->pick('login/set-password');
```

This allows controller actions to render views from different directories than the default convention.
