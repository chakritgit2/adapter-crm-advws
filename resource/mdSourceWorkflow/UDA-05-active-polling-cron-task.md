# UDA-05: Active Polling & Cron Task Synchronization

## 1. System Objective

The active synchronization workflow is implemented in `app/tasks/SyncTask.php`. It polls enabled endpoints from the Phalcon CLI runtime, transforms the returned records, delivers them to HTTPS webhooks, and advances a durable checkpoint only after successful delivery. Scheduling still belongs to deployment configuration.

## 2. Existing CLI Foundation

The repository already has `app/console.php`, which creates a Phalcon CLI DI container, registers a shared configuration and CRM database service, registers `app/tasks/`, models, services, and helpers, and dispatches the task/action supplied on the command line.

This is a suitable bootstrap pattern for a future task, but the current CLI database service connects to the CRM MariaDB schema. A sync task must not use that connection as a shortcut for vendor access. Add a dedicated connection factory/service with driver allow-listing, timeouts, credential decryption, and safe cleanup.

Sync state is stored in `adapter_sync_checkpoints`; the task uses a lease token to prevent overlapping workers. Webhook delivery is implemented with PHP cURL and enforces HTTPS. No production cron entry is committed, so deployment must schedule `php app/console.php sync run` after configuring the required environment and endpoint metadata.

## 3. High-Water Mark Design

Prefer durable database state over an unmanaged `sync_state.json` file when multiple workers or deployments may run concurrently. A possible `sync_checkpoints` table should include:

- endpoint or connection ID;
- tenant/company scope;
- last successful source cursor or timestamp;
- last attempted timestamp;
- status and error summary;
- lease/lock information;
- created/updated timestamps.

If a file is used for a single-host prototype, store it outside the public directory, write atomically, restrict permissions, and lock it during read/modify/write. Store the exact source cursor when a vendor supports one; timestamps alone can miss records with equal timestamps or suffer from clock skew.

## 4. Delta Extraction

A sync definition must state which source field or cursor is used for incremental extraction. A timestamp-based query might use:

```sql
SELECT *
FROM subsidiary_products
WHERE updated_at > :last_sync_timestamp
  AND updated_at <= :run_upper_bound
ORDER BY updated_at ASC, id ASC
```

Use a run upper bound so records changed during the run are picked up next time. Add a stable tie-breaker such as the source ID, persist both parts of the cursor, and batch results to a bounded size. Never append a `WHERE` clause by string concatenation to an arbitrary query template without parsing and validating its shape.

When a source cannot provide a trustworthy delta field, document whether the task performs a full scan, uses vendor change streams, or marks the integration unsupported. Do not claim reliable at-least-once delivery from a timestamp-only prototype.

## 5. Delivery and Retry Semantics

The task should:

1. acquire a per-endpoint lease so two workers do not advance the same cursor concurrently;
2. read the checkpoint and establish a run upper bound;
3. fetch and transform bounded batches;
4. POST to the CRM ingestion endpoint over TLS with an idempotency key;
5. advance the checkpoint only after the batch is durably accepted;
6. release the lease and record metrics.

Use bounded connect/read timeouts, exponential backoff with a maximum retry count, and a dead-letter or quarantine path for permanently invalid records. A failed delivery must not advance the high-water mark. A retry may deliver a record twice, so the receiver must support idempotency; “the CRM silently rejects duplicates” is not currently implemented or verified in this codebase and should not be treated as a guarantee.

The HTTP client should be a reviewed dependency already present in the project, or a narrowly scoped client built on the existing `CurlService`. Do not add a new dependency solely by editing a manifest without installing and locking it.

## 6. Scheduling and Operations

No cron schedule is currently checked into the repository. Once implemented, document the exact command, environment, working directory, lock strategy, log destination, and alert thresholds. A typical command shape would be:

```text
php app/console.php sync run --endpoint=<id>
```

The actual task/action names must match the implemented Phalcon task class. Run synchronization under a service account with the minimum permissions needed to read the vendor source and call the CRM receiver. Do not expose CLI credentials in process listings, shell history, or logs.

## 7. Acceptance Criteria

- A restart or retry cannot lose the last successfully accepted batch.
- Concurrent workers cannot corrupt or independently advance one checkpoint.
- Cursor advancement is atomic with the delivery decision.
- Remote outages, malformed records, and rate limits are observable and retry-safe.
- The task reuses the same translator as passive fetches.
- A dry-run mode and bounded test fixture exist before enabling production scheduling.
- The repository contains the task implementation; automated task/integration tests and the production scheduler entry remain follow-up work. Before enabling it, configure `ADAPTER_CREDENTIALS_KEY`, apply `uda_adapter.sql`, and create an endpoint with a webhook URL.
