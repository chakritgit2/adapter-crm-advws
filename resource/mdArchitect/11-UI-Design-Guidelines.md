# UI Design Guidelines

This document defines the Tailwind CSS 4 design system used across all Volt templates: layout structure, color palette, typography, spacing, and canonical class recipes for components (cards, buttons, form inputs, tables, badges). Follow these recipes so every page looks consistent and the Tailwind scanner reliably emits all required classes.

---

## Tailwind CSS 4 Setup

Tailwind CSS 4 uses a CSS-first configuration model. There is **no `tailwind.config.js`** — all setup lives in the source stylesheet.

```css
/* resource/css/main.css */
@import "tailwindcss";

.notosan {
    font-family: "Noto Sans Thai", sans-serif;
}
```

- **Source:** `resource/css/main.css` → **Output:** `public/css/main.css`
- Compiled with the standalone Tailwind CLI binary (`tailwindcss-linux-x64`), not PostCSS.
- Tailwind 4 **automatically scans** template files for class names. Classes must appear as complete literal strings — never concatenate class names in PHP or JavaScript.
- Dynamically generated classes must be safelisted in `app/views/dashboard/settings/cssused.volt` or covered by the pre-main fallback `<style>` block in `layouts/admin.volt`.
- `@theme` blocks in `main.css` are the place to add design tokens (custom colors, fonts) if needed — do not create a JS config file.

### Tailwind 4 Syntax Notes (vs v3)

| v3 class | v4 replacement | Notes |
|----------|----------------|-------|
| `shadow-sm` | `shadow-xs` | v4 renamed the scale: `shadow-sm` in v4 ≈ old `shadow` |
| `shadow` | `shadow-sm` | The codebase already uses `shadow-sm` — it renders correctly in v4 |
| `rounded-sm` | `rounded-xs` | Same rename pattern as shadows |
| `outline-none` | `outline-hidden` | v4 `outline-none` keeps a forced-colors outline; use `outline-hidden` for the old behavior |
| `ring` (3px) | `ring-3` | v4 `ring` defaults to **1px** — always specify the width |
| `bg-gradient-to-r` | `bg-linear-to-r` | Gradient utilities renamed |
| `blur-sm` | `blur-xs` | Same rename pattern |
| (default border `gray-200`) | must set color | v4 default border color is `currentColor` — **always** write `border-slate-200` etc. |
| `space-x-*` caveat | unchanged | still fine, but prefer `gap-*` with `flex`/`grid` |
| plugin `line-clamp` | built-in | `line-clamp-2` works out of the box in v4 |

Other v4 capabilities available without plugins: `focus-visible:`, `disabled:`, `data-*:` variants, `@container` queries, `starting:` transitions, dynamic spacing values (any numeric multiple of `0.25rem`).

---

## Color Palette

The app uses a **slate + blue** palette. Do not introduce `gray-*`, `zinc-*`, or `indigo-*` — stay on `slate` for neutrals and `blue` for the primary accent.

| Role | Classes | Usage |
|------|---------|-------|
| Page background | `bg-slate-50` | `<body>` |
| Surface | `bg-white` | Cards, sidebar, header, tables |
| Primary text | `text-slate-900` | Headings, strong values |
| Body text | `text-slate-800` | Default body color (set on `<body>`) |
| Secondary text | `text-slate-700` | Nav links, labels |
| Muted text | `text-slate-500` | Descriptions, timestamps, empty states |
| Faint text | `text-slate-400` | Section eyebrow labels, placeholder-adjacent text |
| Borders | `border-slate-200` | Cards, dividers, sidebar/header edges |
| Input borders | `border-slate-300` | Form controls |
| Primary action | `bg-blue-600` / `hover:bg-blue-700` | Primary buttons, active toggles |
| Dark action | `bg-slate-700` / `hover:bg-slate-800` | Secondary-strong buttons (e.g. "Add connection") |
| Destructive | `text-red-600` / `hover:text-red-800`, `bg-red-100 text-red-700` | Delete links, error badges |
| Success | `bg-emerald-100 text-emerald-700`, `bg-emerald-50` / `text-emerald-600` | Status badges, icon tiles |
| Info links | `text-blue-600` / `hover:text-blue-800` | Inline actions |
| Warning | `bg-amber-100 text-amber-700`, `bg-amber-50` / `text-amber-600` | Warnings, icon tiles |
| Neutral badge | `bg-slate-100 text-slate-700` | Inactive states |
| Accent tile | `bg-purple-50` / `text-purple-600` | Category icons |

**Status badge recipe:** `px-2 py-1 rounded-full text-xs bg-{color}-100 text-{color}-700`

---

## Typography

- **Font:** `Noto Sans Thai` (Google Fonts, weights 400–800). Apply the `notosan` class on headings, nav items, buttons, and any element that can render Thai text.
- **Base size:** default 16px body; most UI copy is `text-sm` (14px).

| Element | Classes |
|---------|---------|
| Page title (h1) | `text-2xl font-bold text-slate-900 notosan` |
| Section title (h2) | `text-lg font-semibold text-slate-900 notosan` |
| Card title (h3) | `text-sm font-semibold text-slate-900` |
| Subtitle / description | `text-sm text-slate-500` (add `mt-1` under a title) |
| Eyebrow / nav category | `text-xs font-semibold uppercase tracking-wide text-slate-400` |
| Helper text | `text-xs text-slate-500 mt-1` |
| Mono values (IDs, API names) | `font-mono text-xs` |

---

## Layout and Spacing

### Page container

Every dashboard page wraps content in:

```html
<div class="container mx-auto px-4 lg:px-8 py-8">
```

Add `max-w-3xl` for narrow form pages, `space-y-8` for stacked card sections.

### Section rhythm

- Between top-level sections: `mt-10` on the `<section>`, or `space-y-8` on the container.
- Section header block: `mb-4` (`h2` + description paragraph).
- Card grids: `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4`.
- Form field grids: `grid md:grid-cols-2 gap-5`; form vertical rhythm: `space-y-5`.

### Chrome

- Sidebar: `w-64 bg-white border-r border-slate-200`, fixed `h-screen sticky top-0`, `hidden lg:flex`.
- Header: `h-20 bg-white border-b border-slate-200 sticky top-0 z-50`.
- Sidebar nav links: `flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100` with icon `w-5 text-center text-slate-500`.

---

## Components

### Cards

```html
<!-- Interactive nav card -->
<a href="..." class="block bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:shadow-md hover:border-slate-300 transition notosan">
    <div class="flex items-center gap-3">
        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
            <i class="fas fa-building text-blue-600 text-base"></i>
        </div>
        <div class="min-w-0">
            <h3 class="text-sm font-semibold text-slate-900">Title</h3>
            <p class="text-xs text-slate-500 line-clamp-2">Description</p>
        </div>
    </div>
</a>

<!-- Static content card -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-6 py-4 border-b border-slate-200">
        <h2 class="font-semibold text-slate-900">Card heading</h2>
        <p class="text-xs text-slate-500 mt-1">Supporting text</p>
    </div>
    <!-- body -->
</div>
```

- Card corner radius is **`rounded-xl`** on the outer shell; inner icon tiles use `rounded-lg` (`w-10 h-10 ... flex items-center justify-center` with `bg-{color}-50` + `text-{color}-600`).
- Icon tile colors: `blue`, `emerald`, `amber`, `purple` — pick per category, not per state.

### Buttons

```html
<!-- Primary -->
<button class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm font-medium">
    <i class="fas fa-plug"></i> Save
</button>

<!-- Strong secondary -->
<a href="..." class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-800 text-sm font-medium">Add connection</a>

<!-- Outline / neutral -->
<a href="..." class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 text-sm font-medium">View logs</a>

<!-- Ghost cancel -->
<a href="..." class="px-4 py-2 text-slate-600">Cancel</a>

<!-- Destructive inline -->
<button class="text-red-600 hover:text-red-800">Delete</button>
```

- Standard button padding is **`px-4 py-2`** with `rounded-lg` and `text-sm font-medium`.
- Form action rows: `flex justify-end gap-3` (cancel link left, submit button right).

### Tables

```html
<div class="overflow-x-auto">
<table class="w-full text-left text-sm">
    <thead class="bg-slate-50 text-slate-600">
        <tr><th class="px-6 py-3">Name</th>...</tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
        <tr><td class="px-6 py-4 font-medium text-slate-900">...</td></tr>
    </tbody>
</table>
</div>
```

- Cell padding: **`px-6 py-3`** for `<th>`, **`px-6 py-4`** for `<td>`.
- Right-align action columns: `text-right` on both `th` and `td`.
- Empty state row: `<td colspan="N" class="px-6 py-10 text-center text-slate-500">`.
- Row separators use `divide-y divide-slate-100` on `<tbody>`, never `border` on each `<tr>`.

---

## Form Inputs

This is the **canonical form control recipe**. All text-like inputs, selects, and textareas share one class string:

```html
<input type="text" name="name"
    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed transition">
```

### Input padding

| Size | Padding classes | Use for |
|------|-----------------|---------|
| Small | `px-2.5 py-1.5 text-sm` | Dense filter bars, inline table editors |
| **Default** | **`px-3 py-2`** | All standard form fields (text, email, number, password, date, select) |
| Large | `px-4 py-2.5` | Login screens, hero search, single-field pages |
| Search with icon | `pl-10 pr-3 py-2` | Icon sits at `absolute left-3` inside a `relative` wrapper |
| Textarea | `px-3 py-2` + `rows="4"` | Same padding as default inputs |
| Checkbox / radio | `w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500` | No padding — size via `w-4 h-4` |

Rules:

- **Always set padding explicitly.** Tailwind 4's preflight resets inputs to `padding: 0` — an input without `px-*`/`py-*` renders flush against its border. The existing `w-full rounded-lg border-slate-300` pattern is missing padding; treat `px-3 py-2` as mandatory.
- Keep padding symmetric (`px-3 py-2`), except icon inputs which get `pl-10`.
- Selects use the same `px-3 py-2` and add `pr-8` if a custom chevron icon is placed on the right.
- In v4, `focus:outline-hidden` replaces `focus:outline-none`; pair it with `focus:ring-2 focus:ring-blue-500 focus:border-blue-500` so focus remains visible.

### Labels and structure

```html
<div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Host</label>
    <input required name="host" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
    <p class="text-xs text-slate-500 mt-1">Optional helper text.</p>
</div>
```

- Label: `block text-sm font-medium text-slate-700 mb-1`
- Helper text: `text-xs text-slate-500 mt-1`
- Field spacing: `space-y-5` on the `<form>`; side-by-side fields use `grid md:grid-cols-2 gap-5`.
- Required fields use the HTML `required` attribute; do not style with asterisks.

### Input states

```html
<!-- Error -->
<input class="w-full px-3 py-2 rounded-lg border border-red-300 text-sm focus:outline-hidden focus:ring-2 focus:ring-red-500 focus:border-red-500">
<p class="text-xs text-red-600 mt-1">This field is required.</p>

<!-- Disabled -->
<input disabled class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-slate-50 text-slate-500 cursor-not-allowed">

<!-- Read-only display (e.g. generated API key) -->
<input readonly class="flex-1 px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm bg-slate-50">
```

### Checkbox / toggle row

```html
<label class="flex items-center gap-2 text-sm font-medium text-slate-700">
    <input type="checkbox" name="sync_enabled" value="1" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
    Enable scheduled synchronization
</label>
```

---

## Feedback

### Toast notifications

Toasts are styled by the `.toast-notification` CSS block in `admin.volt` (variants: `.success`, `.error`, `.warning`, `.info`) — do not restyle them with Tailwind; add new variants in the same `<style>` block if needed.

### Badges / pills

```html
<span class="px-2 py-1 rounded-full text-xs {{ status == 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ status }}</span>
```

Because the color classes are inside a Volt conditional, both variants must also exist in `cssused.volt` (the safelist file) so the scanner emits them.

### Filter pills

`.filter-pill` variants are covered by the pre-main fallback styles in `admin.volt` — reuse that mechanism rather than inventing new dynamically-named classes.

---

## Dynamic Class Safety (Scanner Rules)

Tailwind 4 detects classes by scanning source text. These patterns **break** the scanner:

```php
<!-- WRONG — scanner sees neither class -->
<span class="bg-{{ color }}-100 text-{{ color }}-700">

<!-- WRONG — constructed in JS -->
el.className = 'bg-' + status + '-100';
```

Do instead:

- Write full conditional strings: `{{ ok ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}`
- Safelist unavoidable dynamic classes in `app/views/dashboard/settings/cssused.volt`
- For JS-only classes, add them to the fallback `<style>` block in `admin.volt` or to `cssused.volt`

---

## Accessibility and Interaction Defaults

- Focus ring recipe for all interactive elements: `focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-0` (`focus:ring-offset-2` on white cards if a halo looks better).
- Prefer `focus-visible:` over `focus:` for non-input elements (nav links, icon buttons) so keyboard focus shows but mouse clicks don't.
- Icon-only buttons need `aria-label` and visible affordance: `p-2 text-slate-500 hover:text-slate-700`.
- Transitions: `transition` on cards, `transition-colors` on small toggles — never `transition-all` on layout-heavy elements.
- Disabled affordance: `opacity-60 cursor-not-allowed` for non-input controls; `disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed` for inputs.
- Language switcher pattern (active vs inactive): `{{ active ? 'bg-blue-600 text-white' : 'text-slate-500 hover:bg-slate-200' }}` on `px-2 py-0.5 rounded-md text-xs font-medium`.

---

## Quick Reference — Copy-Paste Recipes

```html
<!-- Page -->
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 notosan">Title</h1>
        <p class="text-sm text-slate-500 mt-1 notosan">Subtitle</p>
    </div>
</div>

<!-- Form field -->
<div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Label</label>
    <input name="field" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm placeholder-slate-400 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed transition">
</div>

<!-- Actions -->
<div class="flex justify-end gap-3">
    <a href="..." class="px-4 py-2 text-slate-600">Cancel</a>
    <button class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm font-medium">Save</button>
</div>
```
