# UDA-04: Universal JSON Translator

## 1. System Objective

`app/services/TransformerService.php` creates the stable JSON contract between an external data source and the CRM. It is used by the passive API and the CLI sync task. The payload below describes the implemented response envelope.

The current application does have a `TranslationService` and `LocaleService`, but those solve a different problem: human-language UI/entity translations for the HR/CRM interface. They should not be reused as the external-record transformer merely because their names are similar.

## 2. Inputs and Outputs

The translator should accept a normalized list of associative rows or documents plus trusted routing metadata supplied by the adapter configuration. It should return a serializable envelope:

```json
{
  "status": "success",
  "meta": {
    "tenant_public_id": "UUID-HERE",
    "companies_public_id": "UUID-HERE",
    "source": "vendor-products",
    "fetched_at": "2026-09-18T00:00:00Z"
  },
  "data": [
    {
      "external_record_id": "64a7b2c9...",
      "device_name": "Sensor Array",
      "status": "active"
    }
  ]
}
```

Define whether an empty result is `status: success` with `data: []` (recommended) and define a separate error envelope. Keep metadata separate from records so downstream ingestion does not mistake routing fields for vendor columns.

## 3. Translation Rules

### Preserve nested values

Arrays and nested documents should remain structured JSON values. Do not flatten nested vendor data unless an endpoint-specific mapping explicitly requires it. BSON `ObjectId`, date, decimal, and binary values need a documented serialization policy before calling `json_encode`.

### Normalize identifiers

Map the configured source identifier to `external_record_id` and cast it to a stable string. The source field must be configuration-driven because vendors may use `id`, `_id`, `uuid`, or another key. Reject or quarantine records with no usable identifier rather than generating a non-deterministic ID.

### Inject trusted routing metadata

`tenant_public_id` and `companies_public_id` must come from verified adapter configuration or a signed invocation context. Do not copy these values from arbitrary external rows or ordinary query parameters. The current integrated implementation uses authorized tenant/company slugs as the public scope values; replace these with dedicated UUID/public-ID columns when those identifiers are added to the tenant schema.

### Handle collisions and invalid data

Define behavior for vendor fields that collide with reserved envelope keys (`status`, `meta`, `data`, or `external_record_id`). Validate UTF-8, preserve nulls intentionally, bound record and field sizes, and report malformed records with a safe error classification. Avoid silently converting an object or array to the string `"Array"`.

## 4. Relationship to the Current Codebase

The current persistence layer uses Phalcon models such as `Tenants` and `Companies`, while most controller actions also use bound SQL through the shared DB service. The translator should remain a focused service and should not reach into Volt, session state, or controller globals. Inject routing context explicitly and make the output deterministic so both passive HTTP requests and future CLI synchronization can use the same code.

The current UI localization pipeline is separate:

- `LocaleService` loads `app/lang/{language}.php` catalogs and falls back from the active language to English.
- Volt exposes `t()` and `date_localized()` helpers through the Volt compiler.
- `TranslationService` manages configured translatable entity fields.

Those services should not alter vendor payload values unless a specific integration contract says so.

## 5. Error Envelope and Observability

Use a stable error shape, for example:

```json
{
  "status": "error",
  "error": {
    "code": "SOURCE_UNAVAILABLE",
    "message": "The configured source is temporarily unavailable.",
    "request_id": "correlation-id"
  }
}
```

Keep internal exception messages, connection strings, SQL, API keys, and stack traces in protected logs only. Log endpoint ID, source ID, request ID, duration, record count, and outcome. For batch synchronization, include per-record rejection counts and retry-safe failure information.

## 6. Acceptance Criteria

- The translator has unit tests for SQL rows, nested documents, nulls, identifiers, reserved keys, and malformed values.
- The same transformation rules are used by passive fetches and future sync tasks.
- Output metadata is trusted, deterministic, and independently testable.
- JSON encoding failures are surfaced rather than silently returning corrupt data.
- No claim of a live universal protocol is made until a consumer contract and versioning strategy exist.
