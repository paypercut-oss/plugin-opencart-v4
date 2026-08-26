# plugin-opencart-v4

Paypercut payment module for **OpenCart 4.x** (PHP, namespaced classes, Twig).
Hosted and embedded checkout, card + wallet payments, refunds, capture/void,
HMAC-signed webhooks, and merchant-started debug sessions.

> **This file is canonical.** `CLAUDE.md` and `AGENTS.md` are symlinks to it —
> always edit `README.md`.

## Layout

```
admin/                  Admin controllers, language files, Twig templates
catalog/                Storefront controller, model, language files, templates
system/library/paypercut/   Telemetry library (plain PHP, explicit requires)
docs/telemetry.md       Debug-session design and full event catalogue
tests/                  Dependency-free test scripts
install.json            Extension manifest; `version` is the single source of truth
```

The release zip ships `admin/`, `catalog/`, `system/`, `install.json` at its
root; OpenCart's installer unpacks it into `extension/paypercut/`.

Routes use OpenCart's pipe form in URLs
(`extension/paypercut/payment/paypercut|testConnection`) and the dot form in
event actions (`extension/paypercut/payment/paypercut_telemetry.notice`).

## Settings

Stored under the `payment_paypercut` setting code. The ones worth knowing:

| Key | Meaning |
|---|---|
| `payment_paypercut_api_key` | `sk_test_…` / `sk_live_…`; the mode badge is derived from the prefix |
| `payment_paypercut_environment` | `production` (default) / `stage` / `dev` — resolves **both** the API host and the telemetry edge host |
| `payment_paypercut_checkout_mode` | `hosted` or `embedded` |
| `payment_paypercut_order_status_id` | Status applied on a successful payment |
| `payment_paypercut_webhook_id` / `_webhook_secret` | Written by the Webhooks tab |
| `payment_paypercut_logging` | Gates the module's own log files (never the debug-session audit line) |

There is no hardcoded API host left in the module: every call goes through
`apiUrl()`, which resolves the base from `payment_paypercut_environment` and
accepts only `https` on a `paypercut.net` / `paypercut.io` host. An unset or
unrecognised environment falls back to production for the API, so existing
stores are unaffected.

`cdn.paypercut.io` (the Apple Pay domain-association file) and
`dashboard.paypercut.io` (the deep link on the order screen) are not part of
that map and remain fixed.

## Debug sessions

A merchant-started, self-expiring diagnostic feed, off by default, on the
module's **Debug Session** tab. Design, storage, budgets, the privacy contract
and the full event catalogue: [`docs/telemetry.md`](docs/telemetry.md).

Four things not to change without reading that document first:

- **The environment pairing.** The mint host and the edge host come from one
  setting, in one call sequence, with no override. A token minted for one
  environment is refused by every other environment's edge with a 401 that
  looks exactly like a forgery.
- **The deny assertion.** `EventQueue::append()` screens the **whole envelope**
  as it will be serialised — correlation ids included, because those are copied
  out of webhook bodies — and drops the **whole event**, never just the
  offending field.
- **Upstream text.** Neither `Event::apiFailure()` nor `Event::failure()` sends
  an upstream error message: the API quotes a rejected key back inside it, and
  OpenCart's database adapter puts the SQL and the database user@host inside it.
- **The constructor boot.** Every reporting controller loads the telemetry
  library in its constructor. Nothing autoloads that namespace, and PHP
  evaluates an `Event::...` argument before `report()` is entered.

## Tables

`oc_paypercut_customer`, `oc_paypercut_transaction`, `oc_paypercut_refund`,
`oc_paypercut_webhook_log`, and `oc_paypercut_telemetry` (the telemetry
key/value store: token, queue, inflight batch, runtime counters, locks).

Uninstalling drops only `oc_paypercut_telemetry` — the payment tables are kept
deliberately, so transaction history survives a reinstall.

## Tests

```bash
php tests/run.php        # environment pairing, privacy contract, queue, flusher
php tests/catalogue.php  # every emitted event name is documented
```

Both are plain PHP with no OpenCart bootstrap and no dependencies. There is no
wider automated suite; anything outside the telemetry library is smoke-tested by
hand on the hosted dev store.

## Dev store

<https://opencart-v4.dev.paypercut.net/manage> (admin path is `/manage`, not
`/admin`). Built from the `opencart-dev` repo's `v4/` workspace, which bakes
this module in via its `modules/` submodule pin. The box is ephemeral: it
re-seeds on every pod start, so run **Extensions → Installer** and reconnect the
module by hand after a redeploy.

## Releasing

Tag `vX.Y.Z`. The `release-zip` workflow refuses to build unless the tag matches
both `install.json`'s `version` and `PLUGIN_VERSION` in
`catalog/controller/extension/paypercut/payment/paypercut.php`.
