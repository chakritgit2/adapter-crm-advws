# UDA Adapter Handoff: Fix `SOURCE_UNAVAILABLE` on `get-user-products`

**Audience:** AI coder / maintainer of `adapter-crm.advws.com` (the Universal
Data Adapter). **Not** a CRM-repo change — the CRM proxy is verified working.

## 1. Observed failure

```
GET https://adapter-crm.advws.com/api/v1/advancevending-get-user-products?apikey=***&kiosk_ids=7%2C10
→ HTTP 502
{"status":"error","error":{"code":"SOURCE_UNAVAILABLE","message":"The configured source is temporarily unavailable."}}
```

The CRM forwards the request correctly (confirmed end-to-end: URL, params,
masking, envelope). `SOURCE_UNAVAILABLE` is emitted by **the adapter**, meaning
it cannot reach the backing store configured for this endpoint — or that
endpoint's source is misconfigured/absent.

## 2. What to check on the adapter

- Does a `advancevending-get-user-products` route/source definition exist at
  all, and does it point at the same Mongo connection as the (working)
  `get-user`-style endpoint?
- Is the Mongo driver/DSN/credential for that source healthy? Add a startup
  connectivity check or `/health` detail that reports per-source status so
  `SOURCE_UNAVAILABLE` is diagnosable without reading logs.
- Does the endpoint fail at *source connect* (this error) vs *query parse*
  (should be a 4xx)? See §4 — distinguish the two.

## 3. Reference data (what `get_user` returns)

Mongo-shaped user documents, e.g.:

```json
{
  "_id": 28,
  "userId": "U09dd915f2b6b1a3379dc7cac556a517b",
  "displayName": "penpisut.pra",
  "pictureUrl": "https://profile.line-scdn.net/…",
  "role": "staff",
  "kiosk_manage": [7, 10],
  "external_record_id": "28"
}
```

`kiosk_ids` values are members of `kiosk_manage` — ints, not strings.

## 4. Param contract the CRM relies on

| param | format | meaning |
|---|---|---|
| `kiosk_ids` | `7,10` — comma-separated ints in ONE param | filter products to these kiosks |
| `user_id` | `28` — the user doc's `_id`/`external_record_id` | (already used; keep supporting) |
| `company_id` | never sent upstream | CRM routing-only param |

Robustness (nice-to-have): also accept `kiosk_ids[]=7&kiosk_ids[]=10` and
`kiosk_ids[0]=7&kiosk_ids[1]=10` — treat all three identically.

**Do not** treat a comma-joined `kiosk_ids` as one giant kiosk id — split on
`,`, trim, cast to int.

## 5. Response contract

- Success: `{"status":"success","data":[ …products… ]}` — or a bare JSON array;
  the CRM accepts either.
- Unknown/empty `kiosk_ids` → `200` with `data: []`, not an error.
- Bad input (non-numeric, missing required param) → `4xx` with
  `{"status":"error","error":{"code":"VALIDATION","message":"…"}}` — NOT
  `SOURCE_UNAVAILABLE`, so the CRM debug view shows the real cause.
- Genuine source outage → keep 502/503 `SOURCE_UNAVAILABLE`.

## 6. Acceptance test

```bash
# must return 200 + product list for kiosks 7 and 10
curl 'https://adapter-crm.advws.com/api/v1/advancevending-get-user-products?apikey=***&kiosk_ids=7,10'

# must return 200 + empty list (or filtered list), not 5xx
curl 'https://adapter-crm.advws.com/api/v1/advancevending-get-user-products?apikey=***&kiosk_ids=9999'

# must return 4xx VALIDATION error
curl 'https://adapter-crm.advws.com/api/v1/advancevending-get-user-products?apikey=***&kiosk_ids=abc'
```

CRM-side verification after fix: Integrations → External Data → Test on
`get_user_products` with `user_id=28` (link resolves `kiosk_ids` server-side)
and with explicit `kiosk_ids=7,10` — both should return records.
