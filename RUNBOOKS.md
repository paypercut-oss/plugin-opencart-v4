# Paypercut OpenCart v4 — Runbooks

Operational runbooks for installing, configuring, operating, and troubleshooting
the Paypercut payment module for OpenCart 4.

> Audience: merchants and integrators running the module in staging / production.
> For API reference, see the [Paypercut Dashboard](https://dashboard.paypercut.io).

---

## 1. Overview

The module consists of three logical components:

| Component          | Location                                                       | Purpose                                               |
| ------------------ | -------------------------------------------------------------- | ----------------------------------------------------- |
| Admin controller   | `admin/controller/extension/paypercut/payment/`                | Settings, webhook management, logs UI, refunds        |
| Catalog controller | `catalog/controller/extension/paypercut/payment/paypercut.php` | Checkout session creation, callback, webhook receiver |
| Language packs     | `admin/language/*`, `catalog/language/*`                       | 13 locales                                            |

Runtime dependencies: PHP 8.0+ with cURL, an OpenCart 4.x store served over HTTPS,
and a Paypercut account with an API key (`sk_test_…` or `sk_live_…`).

---

## 2. Prerequisites

- OpenCart **4.0.2.0** or later
- PHP 8.0+ with `curl`, `json`, `openssl` extensions
- Public HTTPS URL for the storefront (required for webhooks and Apple Pay / Google Pay)
- Write access to the OpenCart `storage/logs/` directory
- MySQL user with `CREATE TABLE` privileges (first webhook event creates
  `oc_paypercut_webhook_log` and `oc_paypercut_transaction`)

---

## 3. Install / Upgrade / Uninstall

### 3.1 Install

1. Download `paypercut-opencartv4-<version>.ocmod.zip` from
   [Releases](https://github.com/paypercut-oss/plugin-opencart-v4/releases).
2. In admin, go to **Extensions → Installer** and upload the zip.
3. Go to **Extensions → Extensions → Payments**, find **Paypercut Payments**
   and click **Install**, then **Edit**.
4. Complete [Configuration](#4-configuration).

### 3.2 Upgrade

1. Install the new `.ocmod.zip` over the existing version (OpenCart replaces files).
2. Clear admin caches: **Dashboard → Developer Settings → Theme / SASS → Refresh**.
3. Verify the module still shows **Enabled** under Payments.
4. Run the [post-deploy smoke test](#71-post-deploy-smoke-test).

### 3.3 Uninstall

1. **Extensions → Payments → Paypercut Payments → Uninstall**.
2. Delete the webhook from the settings page **before** uninstalling (otherwise
   delete it manually in the Paypercut Dashboard → Developers → Webhooks).
3. Optional: drop module tables
    ```sql
    DROP TABLE oc_paypercut_webhook_log;
    DROP TABLE oc_paypercut_transaction;
    DROP TABLE oc_paypercut_pending_checkout;
    ```

---

## 4. Configuration

Settings live in `oc_setting` under the code `payment_paypercut`.

| Setting                      | Required | Notes                                                            |
| ---------------------------- | -------- | ---------------------------------------------------------------- |
| API Key                      | ✅       | `sk_test_…` = test mode, `sk_live_…` = live mode (auto-detected) |
| Operating Account ID         | ✅       | From Paypercut Dashboard                                         |
| Statement Descriptor         |          | Max 22 chars, shown on customer bank statements                  |
| Checkout Mode                | ✅       | `hosted` (redirect) or `embedded` (on-site)                      |
| Google Pay / Apple Pay       |          | Requires registered payment method domain                        |
| Payment Method Configuration |          | Paypercut profile id to restrict methods                         |
| Order Status                 |          | Defaults to Processing                                           |
| Enable Logging               |          | **Disable in production** unless debugging                       |

### 4.1 Verify API key

Settings page → **Test Connection** button. A green banner confirms the key
is valid and reachable.

### 4.2 Currency

Supported: `BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON`.
The settings page shows a red banner if the store currency is unsupported;
the module will refuse to create checkouts in unsupported currencies.

### 4.3 Payment method domain (Apple Pay / Google Pay)

Saving settings with a valid API key automatically calls
`POST /v1/payment_method_domains` and registers the store's domain.
If registration fails you will see a yellow warning on save. Re-verify
ownership in **Paypercut Dashboard → Settings → Payment Method Domains**.

---

## 5. Webhooks

The receiver lives at:

```
https://<your-store>/index.php?route=extension/paypercut/payment/paypercut|webhook
```

### 5.1 Create / delete via UI

Go to the **Webhooks** tab on the settings page and click
**Create Webhook Automatically**. The module:

1. Calls `POST /v1/webhooks` with `enabled_events = [checkout_session.completed]`.
2. Stores the returned `id` and `secret` in `payment_paypercut_webhook_id` /
   `payment_paypercut_webhook_secret`.

**Delete Webhook** calls `DELETE /v1/webhooks/{id}` and clears the stored ids.

### 5.2 Events consumed

- `checkout_session.completed` — the only event the module acts on. It marks
  the order as paid and transitions it to the configured order status.

### 5.3 Signature verification

Every webhook request is verified using the stored `webhook_secret` against the
`Paypercut-Signature` header. Requests with missing or invalid signatures are
rejected with HTTP 400 and logged to `paypercut_error.log`.

---

## 6. Logs & Diagnostics

| Source             | Location                            | Cleared by                        |
| ------------------ | ----------------------------------- | --------------------------------- |
| Webhook events     | DB table `oc_paypercut_webhook_log` | **Clear Logs** button in admin    |
| Error log          | `storage/logs/paypercut_error.log`  | **Clear Log** button / `unlink()` |
| OpenCart error log | `storage/logs/error.log`            | OpenCart admin                    |

Admin UI: **Extensions → Payments → Paypercut Payments → View Logs**
(route `extension/paypercut/payment/paypercut_logs`). Supports filtering
webhook events by type and date range.

Enable verbose logging only while debugging — payloads may contain customer
email addresses and billing metadata.

---

## 7. Playbooks

### 7.1 Post-deploy smoke test

1. Place a test order using card `4242 4242 4242 4242` (test mode).
2. Confirm the order reaches the configured **Order Status** in admin.
3. Confirm a row appears in `oc_paypercut_webhook_log` with
   `event_type = checkout_session.completed` and `processed = 1`.
4. Confirm `paypercut_error.log` contains no new `ERROR` entries.

### 7.2 Payment succeeded but order stuck in "Pending"

Symptom: customer paid (visible in Paypercut Dashboard) but the OpenCart order
history has no "Payment completed" entry.

1. Open **View Logs** → Webhook Logs, filter by today.
2. If the event is missing → webhook delivery failed (see [7.3](#73-webhook-not-firing)).
3. If the event is present with `processed = 0` → inspect `error_log` for the
   corresponding timestamp; common causes:
    - Order id not found in `oc_paypercut_pending_checkout` (session dropped
      before callback stored it)
    - Signature mismatch (secret was rotated in dashboard)
4. Manual resolution: add order history in admin with the correct status, then
   record transaction details from Paypercut Dashboard in order comments.

### 7.3 Webhook not firing

1. Verify the webhook URL in **Paypercut Dashboard → Developers → Webhooks**
   matches the one shown on the settings page.
2. Re-send a recent event from the dashboard and watch the Paypercut Logs
   page — a new row should appear within a few seconds.
3. If nothing arrives:
    - Confirm the storefront is reachable over public HTTPS (no basic-auth,
      no IP allowlist blocking Paypercut).
    - Check web-server logs for 4xx/5xx on the webhook route.
    - Temporarily enable module logging and replay.
4. Recreate the webhook: **Delete Webhook** then **Create Webhook Automatically**.

### 7.4 Apple Pay / Google Pay button missing

1. Confirm the wallet toggle is enabled on the settings page.
2. Confirm the store domain is registered and **enabled** in
   **Paypercut Dashboard → Payment Method Domains**. Save the settings page
   once more to re-trigger auto-registration.
3. Apple Pay requires the `.well-known/apple-developer-merchantid-domain-association`
   file to be served by Paypercut's domain registration flow — re-verify.
4. Google Pay only renders on HTTPS and on supported browsers/devices.

### 7.5 "Unsupported currency" warning

The module disables itself during `validate()` if the store currency is not in
the supported list. Either:

- Switch the store default currency to a supported one, **or**
- Create a dedicated store view with a supported currency for Paypercut.

### 7.6 Refund failed

Refunds are initiated from the **Paypercut Order** admin page.

1. Confirm the payment status is `succeeded` (pending/failed payments cannot be refunded).
2. Confirm the requested amount ≤ remaining refundable amount.
3. Inspect `paypercut_error.log` for the API error body returned by
   `POST /v1/refunds`.
4. For timeouts, retry; for `insufficient_funds` on the operating account,
   top up in the dashboard.

### 7.7 Rollback

1. Tag the currently-deployed version (e.g. `v1.0.3`).
2. Download the previous `.ocmod.zip` from the Releases page.
3. Install it via **Extensions → Installer** (this overwrites files).
4. If a DB migration was introduced in the bad version, restore the affected
   tables from backup. The module does not alter `oc_order` schema.

---

## 8. Release process

Releases are automated via `.github/workflows/release-zip.yml`:

1. Bump version on `main` (optional — the workflow rewrites `install.json`).
2. Tag: `git tag vX.Y.Z && git push origin vX.Y.Z`.
3. The workflow syncs `install.json.version` to the tag, builds
   `paypercut-opencartv4-X.Y.Z.ocmod.zip` with module contents at the ZIP
   root, and attaches it to a GitHub Release with auto-generated notes.

Verification:

- The workflow asserts `admin/`, `catalog/`, and `install.json` exist at
  zip root; a failure here blocks the release.

---

## 9. Escalation

| Tier | Contact                                    | Use when                                          |
| ---- | ------------------------------------------ | ------------------------------------------------- |
| L1   | Merchant's OpenCart admin                  | UI / configuration issues                         |
| L2   | Integrator running this plugin             | Webhook, logs, order-state issues                 |
| L3   | Paypercut Support (`support@paypercut.io`) | API errors, dashboard issues, domain verification |

When escalating to Paypercut Support include:

- API key mode (test / live) — **never** the key itself
- Checkout ID or Payment ID from order history
- Relevant entries from `paypercut_error.log`
- Webhook event ID from the webhook logs page
