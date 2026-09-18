This is a pivotal architectural design moment for the ADS PPC Center. Storing the rules as JSON in the `condition_logic` column of the `audit_rules` table  is a brilliant move. It allows you to build a highly flexible, UI-driven rule builder on the front end while keeping the database schema rigid and performant.

Because your `audit_rules` table already defines the `target_entity` (e.g., `'account', 'campaign', 'ad_group', 'keyword', 'ad'`), your background worker already knows which core Domain 2 structural table acts as the "base" for its SQL query.

Therefore, the `condition_logic` JSON only needs to supply three things: the **timeframe**, the **data domains** to join, and the **mathematical conditions**.

Here is the recommended JSON schema structure and how your Phalcon engine will process it.

---

### **1. The standard `condition_logic` JSON Schema**

To ensure your Phalcon background worker can predictably build raw SQL, every JSON object should follow a strict, standardized format utilizing an array of condition objects linked by a logical operator.

**Schema Breakdown:**

| JSON Key | Type | Description |
| --- | --- | --- |
| `timeframe` | String | Defines the lookback window (e.g., `"last_7_days"`, `"last_30_days"`, `"yesterday"`, `"all_time"`). Tells the engine how to filter the Domain 3 date partitions.

 |
| `logical_operator` | String | `"AND"` or `"OR"`. Dictates how multiple conditions interact. |
| `conditions` | Array | A list of individual rule constraints. |
| ↳ `domain` | String | Indicates which table to join (`"performance"` = Domain 3, `"structure"` = Domain 2, `"health"` = Domain 6). |
| ↳ `metric` | String | The specific database column to evaluate (e.g., `"cost"`, `"conversions"`, `"http_status_code"`). |
| ↳ `operator` | String | The SQL operator (`">"`, `"<"`, `"="`, `"!="`, `"IN"`, `"IS NULL"`). |
| ↳ `value` | Mixed | The threshold required to trigger the alert (Integer, String, Array, or Boolean). |

---

### **2. Concrete JSON Examples in Practice**

Let's look at how this schema translates your core business logic into database-ready JSON parameters.

#### **Example A: Zero-Conversion Waste (Keyword Level)**

* 
**The Goal:** Flag keywords that have spent more than $50 but generated 0 conversions in the last 30 days.


* **The JSON:**

```json
{
  "timeframe": "last_30_days",
  "logical_operator": "AND",
  "conditions": [
    {
      "domain": "performance",
      "metric": "cost",
      "operator": ">",
      "value": 50.00
    },
    {
      "domain": "performance",
      "metric": "conversions",
      "operator": "=",
      "value": 0
    }
  ]
}

```

#### **Example B: Critical Health / Broken URLs (Ad Level)**

* 
**The Goal:** Flag ads where the automated external checker found a 404 Not Found or 500 Server Error.


* **The JSON:**

```json
{
  "timeframe": "last_24_hours",
  "logical_operator": "OR",
  "conditions": [
    {
      "domain": "health",
      "metric": "http_status_code",
      "operator": "IN",
      "value": [404, 500]
    }
  ]
}

```

#### **Example C: Structural Gaps (Ad Group Level)**

* 
**The Goal:** Flag active ad groups that contain exactly 0 active ads inside them.


* **The JSON:**

```json
{
  "timeframe": "real_time",
  "logical_operator": "AND",
  "conditions": [
    {
      "domain": "structure",
      "metric": "active_ad_count",
      "operator": "=",
      "value": 0
    }
  ]
}

```

---

### **3. How the Phalcon Engine Reads This (The Logic Flow)**

When your background worker runs, it will select active rules from the `audit_rules` table. Here is the logical sequence the PHP script uses to transform the JSON into a SQL query:

1. **Establish the Base:** It looks at `target_entity`. If it says `'keyword'`, it starts building: `SELECT k.id FROM keywords k`.
2. **Determine JOINs:** It parses the JSON `conditions`.
* If it sees `"domain": "performance"`, it appends a `JOIN` to `keyword_performance_daily`.


* If it sees `"domain": "health"`, it appends a `JOIN` to `url_crawl_logs`.




3. **Apply the Timeframe:** It translates `"last_30_days"` into a `WHERE perf_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)` clause.
4. **Build the Constraints:** It loops through the `conditions` array. If it's a performance metric (which requires aggregation across multiple days), it appends to a `HAVING` clause (e.g., `HAVING SUM(cost) > 50 AND SUM(conversions) = 0`). If it is a structural metric, it appends to the `WHERE` clause.
5. **Execute and Insert:** It executes the built query. For every ID returned, it runs an `INSERT INTO keyword_audit_alerts` (or the respective table), setting `execution_status` to `'manual_review'`.



---

To move forward with the engine, would you like me to write the actual Phalcon PHP helper class that parses this JSON schema and dynamically generates the raw SQL query, or would you prefer to map out the JSON configurations for the remaining audit rules first?

---

Mapping out the advanced logic and planning the user interface to build them dynamically is exactly how we scale this platform from a basic tool into an enterprise-grade engine.

By designing these rules mathematically in JSON, we maintain a strict database schema while allowing infinite flexibility. Here are the JSON schemas for the most critical advanced conditions, followed by the specific HTML inputs you will need to build the front-end UI.

---

### **Part 1: Advanced JSON Configurations**

#### **1. Keyword Level: Sub-Component Quality Score Warnings**

* 
**The Goal:** Trigger an alert when a keyword's `expected_ctr` or `landing_page_exp` drops to `'BELOW_AVERAGE'`, even if the overall 1-10 Quality Score hasn't completely crashed yet. This catches early-stage degradation.


* **The JSON:**

```json
{
  "timeframe": "real_time",
  "logical_operator": "OR",
  "conditions": [
    {
      "domain": "structure",
      "metric": "expected_ctr",
      "operator": "=",
      "value": "BELOW_AVERAGE"
    },
    {
      "domain": "structure",
      "metric": "landing_page_exp",
      "operator": "=",
      "value": "BELOW_AVERAGE"
    }
  ]
}

```

#### **2. Ad Group Level: Keyword Bloat (Poor Grouping)**

* 
**The Goal:** Flag ad groups that contain more than 30 active keywords. Best practices dictate tightly themed groups; bloated groups destroy ad relevance and drive up costs.


* **The JSON:**

```json
{
  "timeframe": "real_time",
  "logical_operator": "AND",
  "conditions": [
    {
      "domain": "structure",
      "metric": "active_keyword_count",
      "operator": ">",
      "value": 30
    },
    {
      "domain": "structure",
      "metric": "status",
      "operator": "=",
      "value": "ENABLED"
    }
  ]
}

```

#### **3. Campaign Level: Lost Impression Share (Budget vs. Rank)**

* 
**The Goal:** Trigger an alert when a highly profitable campaign (one that is successfully driving conversions) is losing a massive percentage of its Impression Share specifically due to budget caps, indicating you are leaving guaranteed money on the table.


* **The JSON:**

```json
{
  "timeframe": "last_30_days",
  "logical_operator": "AND",
  "conditions": [
    {
      "domain": "performance",
      "metric": "search_lost_is_budget_percentage",
      "operator": ">",
      "value": 50.00
    },
    {
      "domain": "performance",
      "metric": "conversions",
      "operator": ">",
      "value": 10
    }
  ]
}

```

#### **4. Campaign Level: Sudden Conversion Tracking Drops**

* 
**The Goal:** Flag a campaign that historically averages high conversion volume but suddenly reports 0 conversions for recent days, which usually indicates a broken tracking pixel or broken checkout page rather than a sudden drop in actual user interest.


* **The JSON:**

```json
{
  "timeframe": "last_3_days",
  "logical_operator": "AND",
  "conditions": [
    {
      "domain": "performance",
      "metric": "conversions",
      "operator": "=",
      "value": 0
    },
    {
      "domain": "performance",
      "metric": "historical_avg_daily_conversions",
      "operator": ">",
      "value": 5
    }
  ]
}

```

---

### **Part 2: The HTML Form UI Blueprint**

To allow marketers to build these complex JSON logic blocks without writing code, you need a dynamic "Rule Builder" interface. You will capture the standard SQL column data first, and then use a JavaScript-driven repeatable block to generate the `condition_logic` JSON.

Here are the specific HTML inputs needed to power this interface:

#### **A. Base Rule Settings (Standard Columns)**

These inputs map directly to the root columns in your `audit_rules` table.

* **Rule Name:** `<input type="text" name="rule_name" required>`
* **Description:** `<textarea name="description"></textarea>`
* **Target Entity:** `<select name="target_entity">`
* `<option value="account">Account</option>`
* `<option value="campaign">Campaign</option>`
* `<option value="ad_group">Ad Group</option>`
* `<option value="keyword">Keyword</option>`
* `<option value="ad">Ad</option>`


* **Status:** `<input type="checkbox" name="is_active" checked>` (Enabled/Disabled)

#### **B. The JSON Payload Builder (The Logic)**

This section requires JavaScript to output the nested JSON format.

* **Evaluation Timeframe:** `<select name="timeframe">`
* `<option value="real_time">Real-Time (Structure Only)</option>`
* `<option value="yesterday">Yesterday</option>`
* `<option value="last_7_days">Last 7 Days</option>`
* `<option value="last_30_days">Last 30 Days</option>`
* `<option value="month_to_date">Month to Date</option>`


* **Logical Operator (Match Type):** `<select name="logical_operator">`
* `<option value="AND">Match ALL of the following conditions (AND)</option>`
* `<option value="OR">Match ANY of the following conditions (OR)</option>`



#### **C. Dynamic Condition Rows (The Array)**

You will need an "Add Condition" button (`<button type="button" id="add_condition_row">`) that appends a new row of the following four inputs to the DOM.

**Inside each dynamic row:**

1. **Domain:** `<select name="conditions[0][domain]" class="domain-selector">`
* `<option value="performance">Performance Data (Metrics)</option>`
* `<option value="structure">Structural Data (Settings)</option>`
* `<option value="health">External Health Logs</option>`


2. **Metric:** `<select name="conditions[0][metric]" class="metric-selector">`
* *Note: This dropdown's options should populate via JavaScript based on what the user selects in the Domain dropdown (e.g., if they pick "Performance", show Cost, Clicks, Conversions. If they pick "Structure", show Status, Expected CTR).*


3. **Operator:** `<select name="conditions[0][operator]">`
* `<option value=">">Greater Than (>)</option>`
* `<option value="<">Less Than (<)</option>`
* `<option value="=">Equals (=)</option>`
* `<option value="!=">Does Not Equal (!=)</option>`
* `<option value="IN">Is Contained In List (IN)</option>`


4. **Value:** `<input type="text" name="conditions[0][value]">`
* *Note: This acts as a catch-all input. A marketer can type `50` (for $50), `BELOW_AVERAGE` (for Quality Score strings), or `target_cpa` (to compare against a dynamic structural goal).*



When the HTML form is submitted, your Phalcon controller will simply take the `$_POST['conditions']` array, package it with the `timeframe` and `logical_operator`, run `json_encode()`, and insert it into the `audit_rules.condition_logic` database column.

---

Hardcoding the alert templates directly into your Phalcon engine is a highly practical and secure architectural choice. By keeping presentation logic out of the database, your database stays lean, and your alert message formats remain fully version-controlled in Git alongside the rest of your PHP codebase.

Now that we are combining the foundational MVP rules with the advanced checks we mapped out, here is the complete reference map for your hardcoded string templates and their corresponding JSON action payloads for all five audit tables.

### **1. keyword_audit_alerts**

These templates will live inside helper functions like `buildZeroConversionWasteQuery()` or `checkSubComponentQS()`.

| Audit Rule Condition | Hardcoded `alert_message` String Template | Hardcoded `action_payload` JSON |
| --- | --- | --- |
| **Zero-Conversion Waste** | `Keyword '{keyword_text}' has spent ${cost} with 0 conversions in the last {timeframe}.` | `{"action": "PAUSE"}` |
| **Negative Conflict** | `Keyword '{keyword_text}' is currently being blocked by negative keyword '{negative_text}'.` | `{"action": "PAUSE"}` |
| **Sub-Component QS** | `Keyword '{keyword_text}' is suffering from {qs_component} dropping to 'BELOW_AVERAGE'.` | `null` |
| **High CPA Anomalies** | `Keyword '{keyword_text}' CPA is ${keyword_cpa}, {multiplier}x higher than the ad group average.` | `{"action": "DECREASE_BID", "percentage": 20}` |
| **Duplicate Keywords** | `Keyword '{keyword_text}' is competing against itself in Ad Group '{competing_ad_group}'.` | `{"action": "PAUSE_LOWEST_QS"}` |

---

### **2. ad_audit_alerts**

These templates handle creative health and statistical testing outcomes.

| Audit Rule Condition | Hardcoded `alert_message` String Template | Hardcoded `action_payload` JSON |
| --- | --- | --- |
| **Critical Health (404/500)** | `Ad '{ad_id}' is pointing to a broken final URL returning an HTTP {status_code} error.` | `{"action": "PAUSE"}` |
| **A/B Testing Loser** | `Ad '{ad_id}' has lost the A/B test for {target_metric} against the winning variation.` | `{"action": "PAUSE"}` |
| **Engine Disapprovals** | `Ad '{ad_id}' has been flagged with a status of '{api_status}' due to policy violations.` | `null` |
| **Ad Cannibalization** | `Ad '{ad_id}' has identical copy to another ad in this group, breaking statistical testing.` | `{"action": "PAUSE_NEWEST"}` |
| **Missing Extensions** | `Ad '{ad_id}' is missing {extension_type} assets, severely lowering overall Ad Rank.` | `null` |
| **Underperforming CTR** | `Ad '{ad_id}' has a CTR of {ad_ctr}%, drastically lower than the ad group average.` | `{"action": "PAUSE"}` |

---

### **3. ad_group_audit_alerts**

Because ad groups act as structural containers, they almost entirely result in `null` payloads, requiring a marketer to restructure themes or write new copy.

| Audit Rule Condition | Hardcoded `alert_message` String Template | Hardcoded `action_payload` JSON |
| --- | --- | --- |
| **Structural Gaps** | `Ad Group '{ad_group_name}' is currently ENABLED but contains 0 active ads to trigger.` | `null` |
| **Sudden Shifts** | `Ad Group '{ad_group_name}' experienced a {drop_percentage}% drop in {target_metric}.` | `null` |
| **Keyword Bloat** | `Ad Group '{ad_group_name}' contains {active_keyword_count} active keywords, destroying relevance.` | `null` |
| **Missing Keywords** | `Ad Group '{ad_group_name}' has active ads but 0 active keywords to trigger them.` | `null` |

---

### **4. campaign_audit_alerts**

These templates deal with the macro-level financial levers of the Google Ads API.

| Audit Rule Condition | Hardcoded `alert_message` String Template | Hardcoded `action_payload` JSON |
| --- | --- | --- |
| **Budget Mismanagement** | `Campaign '{campaign_name}' is pacing at {pacing_percentage}% and will exhaust its budget early.` | `{"action": "ADJUST_BUDGET", "suggested_value": {calculated_safe_budget}}` |
| **Efficiency Misses** | `Campaign '{campaign_name}' actual CPA is ${actual_cpa}, missing the target of ${target_cpa}.` | `{"action": "UPDATE_TARGET_CPA", "suggested_value": {new_target_cpa}}` |
| **Lost Impression Share** | `Profitable campaign '{campaign_name}' is losing {lost_is_percentage}% of IS due to budget caps.` | `{"action": "INCREASE_BUDGET", "percentage": 15}` |
| **Tracking Drops** | `Campaign '{campaign_name}' is reporting 0 conversions for {days} days, indicating broken tracking.` | `null` |

---

### **5. account_audit_alerts**

These are the highest-level pipeline alerts. They do not trigger API execution actions, but rather system-wide notifications.

| Audit Rule Condition | Hardcoded `alert_message` String Template | Hardcoded `action_payload` JSON |
| --- | --- | --- |
| **API Auth Failures** | `OAuth token for account '{account_name}' returned a '{token_status}' status. Sync halted.` | `null` |
| **QS Degradation** | `The aggregate Quality Score for account '{account_name}' has dropped below {threshold_value}.` | `null` |
| **Billing Errors** | `Account '{account_name}' has been suspended by Google. Reason: {suspension_reason}.` | `null` |

---

Before we begin writing the background worker that processes these alerts and pushes the mutations to Google Ads, I have a critical architectural question for you:

When an action is approved by a user but ultimately fails at the Google Ads API level (e.g., due to an API rate limit or a sudden Google policy block), the system will mark the row as `api_failed`. How do you want the system to handle these failures—should it automatically retry the execution on the next cron job loop, or should it immediately notify the user and require manual intervention?