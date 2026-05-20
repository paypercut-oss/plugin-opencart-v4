# plugin-opencart-v4

Paypercut's **OpenCart 4.x payment module** (PHP). Distributed from the `paypercut-oss` GitHub org. Lets OpenCart 4.x merchants accept card / Google Pay / Apple Pay via Paypercut, using either a **hosted checkout** redirect or an **embedded** inline form. Sibling repos cover OpenCart v2 + v3.

**Audience**: OpenCart 4.x merchants and store integrators wiring Paypercut as a payment gateway.

## Layout

**Single OpenCart-4 extension** (no Composer / npm, no build step). Unlike v2/v3, files sit at the repo root (no `upload/` wrapper) and metadata is in `install.json` (not `install.xml`). CI/CD is one GitHub Actions workflow that packages a release zip.

## Architecture

OpenCart 4 changed the architecture significantly vs v2/v3 — this repo fully embraces it:

- **Namespaced classes**: `\Opencart\{Admin|Catalog}\Controller\Extension\Paypercut\Payment\*`, similarly for `Model`.
- **Twig templating** end-to-end (no `.tpl`).
- **Pipe routing**: OC4 routes use `|` instead of `/` for controller actions (e.g. `extension/paypercut/payment/paypercut|webhook`).
- **Event system** registered via `setting/event` model in install.

Directory shape:

- `install.json` — extension metadata (code `paypercut`, version rewritten by release workflow).
- `admin/controller/extension/paypercut/payment/paypercut.php` (~1.7k lines) — settings page, API config, Apple Pay domain file deployment, install/uninstall, webhook lifecycle.
- `admin/controller/extension/paypercut/payment/paypercut_order.php` — refund UI + transaction display.
- `admin/controller/extension/paypercut/payment/paypercut_logs.php` — debug-log viewer.
- `admin/view/template/extension/paypercut/payment/*.twig` — admin templates (Bootstrap 5 tabs).
- `admin/language/{locale}/extension/paypercut/payment/*.php` — admin translations (13 locales).
- `catalog/controller/extension/paypercut/payment/paypercut.php` (~1.7k lines) — `index`, `send`, `initEmbedded`, `confirm`, `callback` (→ `callbackHosted`/`callbackEmbedded`), `webhook`, `success`/`failure`/`pending`.
- `catalog/model/extension/paypercut/payment/paypercut.php` — `getMethods()` + currency validation.
- `catalog/view/theme/default/template/extension/paypercut/payment/*.twig` — checkout UI.

## Payment flow

Two modes via `payment_paypercut_checkout_mode`:

- **Hosted**: `send()` creates a checkout session (`POST /v1/checkouts`), buyer is redirected to `https://checkout.paypercut.io/{id}`, returns to `callback()` which verifies and marks the order. Session state held in `$this->session->data['paypercut_checkout_id']`.
- **Embedded**: `initEmbedded()` (AJAX) creates the session in the background, the Paypercut form is rendered inline; `callbackEmbedded()` records the transaction.

## API integration

- Base: `https://api.paypercut.io/v1`.
- Endpoints used: `POST /checkouts`, `GET /checkouts/{id}`, `GET /payments/{id}`, `POST /customers`, `GET /customers/{id}`, `POST /refunds`, `GET /payment-configs/{id}`, `GET /payment_method_domains`, `GET|POST|DELETE /webhooks`.
- Auth: `Authorization: Bearer <api_key>` (key in `oc_setting` as `payment_paypercut_api_key`). Mode auto-detected from key prefix (`sk_test_` / `sk_live_`).

## Database (4 custom tables)

Created dynamically in the admin `install()` method (`CREATE TABLE IF NOT EXISTS`). Prefix is OC4's `DB_PREFIX` constant. **Tables preserved on uninstall**.

| Table | Purpose |
|---|---|
| `paypercut_customer` | OpenCart customer ID ↔ Paypercut customer ID |
| `paypercut_transaction` | payment_id, amount, currency, status, payment_method_type, JSON payment_method_details |
| `paypercut_refund` | refund_id, amount, status, reason, created_at |
| `paypercut_webhook_log` | event_type, event_id, payload, processed flag (idempotency + debug audit) |

## Webhook

- Public URL: `https://<shop>/index.php?route=extension/paypercut/payment/paypercut|webhook` (note the OC4 pipe).
- Signature: HMAC-SHA256 with the `X-Paypercut-Signature` header against `payment_paypercut_webhook_secret`. **Skipped (non-fatal) if the secret isn't configured.**
- Events: `payment_intent.captured`, `checkout_session.completed`.
- Idempotency: `isWebhookProcessed(event_id, order_id, event_type)` guards duplicate updates.
- All events logged to `paypercut_webhook_log` when `payment_paypercut_logging` is enabled.

## Common commands

This module has **no build step and no test suite**.

```bash
# Package a release zip (mirrors .github/workflows/release-zip.yml on a v* tag)
git tag v1.0.2 && git push --tags  # → paypercut-opencartv4-<version>.ocmod.zip

# Install: Admin → Extensions → Installer → upload the .ocmod.zip,
# then Extensions → Extensions → Payments → Paypercut → Install + Edit.
# OC4 also needs `php cli/cli.php developer:di:compile` if you change DI / class wiring.
```

## Conventions

- **OpenCart 4 namespacing**: all classes extend `\Opencart\System\Engine\Controller` / `Model`. Breaking change from v2/v3 procedural style — don't copy-paste class shape across versions.
- **Twig pipe routes**: OC4's router uses `|` for controller actions (e.g. `paypercut|webhook`), not `/`. Update URLs and event hooks accordingly.
- **Configuration keys** are namespaced `payment_paypercut_*`, stored via OC4's setting model.
- **Supported currencies** (hardcoded): BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON. Out-of-list currencies silently disable the method.
- **Translations** are per-controller (12 locales × 3 admin controllers + the catalog one).
- **Apple Pay domain file** is base64-embedded in code (const `APPLEPAY_DOMAIN_FILE_BASE64`) and decoded into `/.well-known/...` on install/save.

## Gotchas

- **PCI scope**: Paypercut hosts the checkout (hosted mode) or the embedded form (embedded mode) — the merchant site never sees raw card data. Don't introduce a native card form.
- **Webhook signature is optional in code**: if `payment_paypercut_webhook_secret` isn't set, signature checks are skipped without throwing. Don't deploy to production without setting it.
- **OC4 architecture trap**: copying code from v2 (procedural, `.tpl`, no namespaces) or v3 (mixed) into v4 will fail without rewrites for namespacing + Twig + pipe routing.
- **Session order_id dependency**: both hosted and embedded require `$this->session->data['order_id']` from OC4's checkout flow. Lost sessions fail the AJAX init with a user-visible error.
- **Currency lock-in**: 12 supported currencies; the method **disables itself** outside that set — no warning at checkout, just absent.
- **Logging retention**: `paypercut_webhook_log` is never auto-purged. In production, schedule a periodic prune to avoid table bloat.
- **Uninstall preserves data**: the four `paypercut_*` tables are NOT dropped (intentional).
- **Refund UI gating**: admin refund button only renders when a `paypercut_transaction` exists with status `succeeded`. Partial refunds use enum reasons (`duplicate`, `fraudulent`, `requested_by_customer`).
- **DI compilation**: after upgrading or changing class wiring, run `php cli/cli.php developer:di:compile` or OC4 will keep using the old class map.
- **Payment handoff with paycore**: checkout, status, refunds all flow through `api.paypercut.io`. Non-2xx responses are user-visible — log them.
