Using Linux `flock` at the cron level is an excellent, lightweight sysadmin approach to prevent process overlap. Combining this with a database-level `processing` status gives you a bulletproof, two-tier locking system that handles concurrency beautifully.

## Worker Execution Flow

Your background script (whether in PHP or another language) should execute the following sequence of queries to safely process the queue.

### 1. Claiming the Batch

Even with `flock` preventing script overlap, it is best practice to explicitly lock the rows in MySQL while you update them to `processing`. Using `FOR UPDATE SKIP LOCKED` (available in MySQL 8.0+) ensures maximum safety.

```sql
START TRANSACTION;

-- 1. Grab up to 50 pending emails (both 'new' and scheduled retries)
SELECT id, recipient, subject, body 
FROM email_outbox 
WHERE status IN ('new', 'error') 
  AND (next_retry_at IS NULL OR next_retry_at <= NOW())
LIMIT 50 
FOR UPDATE SKIP LOCKED;

-- 2. Immediately mark those specific IDs as processing 
UPDATE email_outbox 
SET status = 'processing' 
WHERE id IN (?, ?, ?); -- Inject the IDs fetched from the previous query

COMMIT;

```

### 2. Handling a Successful Send

When the API returns a 200 OK, update the row to `success`.

```sql
UPDATE email_outbox 
SET status = 'success', 
    error_message = NULL 
WHERE id = ?;

```

### 3. Handling a Failed Send (Exponential Backoff)

If the API fails, you evaluate the current `retry_count`. If it is under your limit of 2, increment it and set the `next_retry_at` timestamp.

If this is the first failure, wait 1 minute. If it's the second failure, wait 5 minutes.

```sql
-- Example: 1st failure (wait 1 minute)
UPDATE email_outbox 
SET status = 'error', 
    retry_count = retry_count + 1,
    next_retry_at = NOW() + INTERVAL 1 MINUTE,
    error_message = 'Connection timed out'
WHERE id = ?;

```

If the API fails and `retry_count` is already at 2, mark it as permanently failed so the worker stops trying to send it:

```sql
-- Example: Max retries reached
UPDATE email_outbox 
SET status = 'failed', 
    error_message = 'Rate limit exceeded - Max retries reached'
WHERE id = ?;

```

---

## Maintenance and Retention

To keep the table performant, you can append a quick cleanup query to the end of your cronjob, or run it as a separate daily task. This safely purges successful emails older than 7 days, leaving your failures in the table for debugging.

```sql
DELETE FROM email_outbox 
WHERE status = 'success' 
  AND updated_at < NOW() - INTERVAL 7 DAY;

```

If you are running this on an Amazon Linux server, your crontab entry using `flock` to enforce the single-worker rule will look like this:

```bash
* * * * * /usr/bin/flock -n /tmp/email_worker.lock php /path/to/your/worker.php

```