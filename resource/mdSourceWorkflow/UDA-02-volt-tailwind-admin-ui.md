# UDA-02: Volt & Tailwind CSS Admin UI

## 1. System Objective

The current repository already uses server-rendered Phalcon Volt views and Tailwind CSS for the HR/CRM administration interface. The proposed UDA administration screens should follow those conventions instead of introducing a second frontend application.

The UDA screens are implemented under `app/views/adapter/` and are served by `AdapterController`. They use the existing authenticated tenant/company route prefix and are restricted to Super Admin users.

## 2. Existing UI Stack

- Volt is registered in `app/config/services.php` as the `.volt` view engine.
- View files live under `app/views/` and normally extend `layouts/admin.volt` for authenticated administration pages.
- `layouts/admin.volt` loads `/css/main.css`, exposes tenant navigation variables, and provides the common sidebar/header structure.
- Tailwind CSS 4 is declared in `package.json` through `tailwindcss` and `@tailwindcss/cli`.
- `resource/css/main.css` imports Tailwind with `@import "tailwindcss";` and contains the shared Noto Sans Thai utility class.
- The compiled artifact is `public/css/main.css`.
- `app/views/dashboard/settings/cssused.volt` is used as a safelist source for classes that are otherwise difficult for the Tailwind scanner to discover. `admin.volt` also contains fallback styles for dynamic UI states.

The existing admin layout also uses Font Awesome, DataTables assets, vanilla JavaScript, and a Noto Sans Thai web font. A UDA screen should reuse these assets and avoid adding a separate Svelte or SPA build unless the architecture is intentionally changed.

## 3. Current Authentication and Scope

The login route is configured as `/loginhrm`. Successful authentication stores a small `auth` record in the Phalcon session. Tenant-scoped controllers then use `TenantBaseController` to:

1. require the authenticated session;
2. resolve `tenant_slug`;
3. verify tenant membership;
4. optionally resolve and authorize `company_slug`;
5. expose the resolved context to Volt.

A UDA administrator page must use the same authentication boundary if it is hosted in this application. Connection-management permissions should not be inferred from the presence of a session alone: use an explicit role/capability check, such as the existing `Super Admin` convention, or define a dedicated adapter-admin permission. If the UDA is standalone, use a separate admin authentication mechanism and do not share the CRM session cookie implicitly.

## 4. Proposed Views

The following paths are implemented:

### `app/views/adapter/index.volt`

A list of configured external connections showing a safe summary only:

- display name and engine;
- host and port, with credentials omitted;
- enabled/disabled state;
- last successful or failed test time;
- actions for edit, disable, and test connection.

The test action should be a POST endpoint with authorization and CSRF protection. It must not return the remote password, include credentials in a URL, or expose raw driver exceptions to the browser.

### `app/views/adapter/connection-form.volt`

A create/edit form for the connection label, engine, host, port, database name, username, and password. On edit, an empty password field must mean “retain the existing encrypted credential,” not “replace it with an empty password.” Validate engine-specific fields on the server.

### `app/views/adapter/index.volt` (endpoint section)

A list of API slots showing the route name, linked connection, enabled state, and last-used or last-error metadata. Never render the API key or its hash in a list view.

### `app/views/adapter/endpoint-form.volt`

A form containing `api_name`, target connection, enabled state, and a parameterized `query_template`. Helper text should show named placeholders such as `:user_id`; it must explicitly state that values are bound parameters and that SQL fragments, table names, and arbitrary operators cannot be supplied by request parameters.

If a one-time API key is generated, display it only once over TLS and store only a verifier/hash where possible. Provide a rotation/revocation action rather than making a long-lived plaintext key easy to copy repeatedly.

## 5. Implemented Routes and Controllers

UDA UI routes are registered explicitly in `app/config/router.php` under the tenant/company `/adapter` prefix rather than relying on the generic route fallback:

```text
GET  /adapter/connections
GET  /adapter/connections/create
POST /adapter/connections/store
GET  /adapter/connections/edit/{id}
POST /adapter/connections/update/{id}
POST /adapter/connections/test/{id}
GET  /adapter/endpoints
GET  /adapter/endpoints/create
POST /adapter/endpoints/store
POST /adapter/endpoints/delete/{id}
```

The final prefix and controller names are an implementation decision. Keep the routes distinct from the current legacy `/api` redirect routes and preserve tenant/company authorization if the adapter is CRM-hosted.

## 6. Adapter Logs UI

`app/views/adapter/logs.volt` is available at the tenant/company-scoped `/adapter/logs` route. It displays the latest 100 external connection events and API endpoint requests. The page includes a manual Refresh button and an Auto refresh selector with Off, 5-second, 15-second, 30-second, and 60-second intervals. The selected interval is stored in browser local storage, and refresh only reloads the current scoped log page.

The page does not display credentials, query parameters, or raw external error details. Logs are written by `AdapterController` for connection tests and by `AdapterApiController` for authentication outcomes, source execution results, status codes, row counts, durations, and correlation IDs.

## 7. Build and Verification

Use the repository's existing Tailwind CLI dependency and source/output locations. The exact command depends on the installed package manager, but the source/output contract is:

```text
input:  resource/css/main.css
output: public/css/main.css
```

After adding a Volt view, run the Tailwind build, verify that the generated CSS contains its utility classes, render the page through the real router, and test unauthorized, forbidden, validation-error, and successful states. Do not commit secrets or generated connection values into templates.
