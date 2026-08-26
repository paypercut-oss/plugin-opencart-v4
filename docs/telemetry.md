# Debug sessions (client telemetry)

A merchant-started, time-boxed diagnostic feed. Off until someone presses
**Start debug session** on the module's **Debug Session** tab; ends by itself
after about an hour.

Nothing is sent when no session is running. `EventRecorder::record()` reads one
already-loaded setting and returns, so call sites on the checkout path are free
to report unconditionally.

## Shape

```
Start  →  POST {api}/v1/telemetry/tokens   (the store's API key, once)
          →  short-lived RS256 token
Events →  POST {edge}/v1/telemetry         (the token; never the API key)
```

The edge verifies the token offline against Ory's published JWKS and never calls
back into the platform, so telemetry cannot block a payment.

**Both hosts come from one value.** `payment_paypercut_environment` resolves the
mint host *and* the edge host in the same call sequence. A token minted for one
environment is rejected by every other environment's edge with a 401 that is
indistinguishable from a forged token, so they are never resolved independently.

| Environment | API base | Telemetry edge |
|---|---|---|
| `production` (default) | `https://api.paypercut.io/` | `https://telemetry.paypercut.io/` |
| `stage` | `https://api.stage.paypercut.net/` | `https://telemetry.stage.paypercut.net/` |
| `dev` | `https://api.dev.paypercut.net/` | `https://telemetry.dev.paypercut.net/` |

An unset or unrecognised environment falls back to production for the **API**
base, so a store that has never saved the setting keeps taking payments, but
yields **no** telemetry edge at all — a debug session is refused rather than
started against the wrong environment. Both bases are validated: only `https` on
a host ending in `paypercut.net` or `paypercut.io` is accepted, because the
store's API key travels on the mint request.

There is no override for either host. Both come from the one stored environment
value in one call sequence, because a token minted for one environment is a
forgery to every other environment's edge — and the 401 that follows ends the
session for good.

## Pieces

| Piece | Role |
|---|---|
| `Event` | Named constructors, the privacy boundary, the wire envelope |
| `EventRecorder` | Buffers events; one queue write per request, at shutdown |
| `EventQueue` | Capped store; the deny assertion; batch splitting |
| `TelemetrySession` | Session record, token custody, locks, teardown |
| `TokenMinter` | Exchanges the API key for a token, on the mint host |
| `EdgeClient` | POSTs a batch; reads `accepted`/`dropped` off a 202 |
| `Flusher` | Delivers from admin requests only; handles 413 by splitting |
| `SentLog` | The tail of delivered envelopes, for the merchant to read |
| `FatalErrorWatch` | Shutdown handler for fatals that reach no catch block |
| `Store` | The OpenCart adapter: settings row, module table, atomic lock |

Everything lives under `system/library/paypercut/`, loaded by explicit
`require_once` from `bootstrap.php` rather than by the OpenCart autoloader — an
extension's namespaces are only registered while an extension list page renders,
so a storefront or webhook request cannot rely on them.

Because nothing autoloads, **every controller that reports boots the library in
its constructor**, not in `report()`. PHP evaluates a
`\Paypercut\Telemetry\Event::...` argument before entering `report()`, so a
guard that reports before the request's first API call would raise an uncaught
`Error` that no `catch (\Exception)` rescues — a PHP 500 where the shopper used
to get a redirect to the failure page.

## Storage

| Need | Where |
|---|---|
| Session record (read on every request) | `oc_setting` row, code `paypercut_telemetry`, key `paypercut_telemetry_session` |
| Runtime counters, sent log | `oc_paypercut_telemetry` (module-owned key/value table) |
| Token, queue, inflight batch | the same table, with an `expires_at` column |
| Start / flush locks | the same table, claimed with `INSERT IGNORE` |
| Audit log | `paypercut_telemetry.log`, written whatever the logging preference says |

The record deliberately lives under its **own setting code**. Saving the module
settings runs `editSetting('payment_paypercut', …)`, which deletes and rewrites
every `payment_paypercut` row — a record stored there would be silently lost on
every save. It is still read for free, because OpenCart loads every setting row
into the config on every request.

The queue is never cache-only: a cache flush mid-session would silently lose the
merchant's diagnostics, which is the one failure mode a debug session cannot
tolerate.

## Events

### Checkout

| Event | When |
|---|---|
| `checkout.hosted.redirected` | Shopper sent to the hosted page |
| `checkout.hosted.create_failed` | The API refused the checkout session |
| `checkout.hosted.redirect_missing` | The request succeeded but carried no URL |
| `checkout.embedded.session_created` | The browser got a session |
| `checkout.embedded.create_failed` | Session creation threw, or returned no id |
| `checkout.embedded.order_created` | Order written after an embedded payment |
| `checkout.session_create_failed` | The request never completed, or the currency is unsupported |
| `checkout.session_unverifiable` | The session could not be read back |
| `checkout.return.pending` | Shopper is back and the payment status was read |
| `checkout.return.unverifiable` | The return could not be checked |

### Payment and order

| Event | When |
|---|---|
| `payment.succeeded` | Paypercut reported paid, with whether the order moved |
| `payment.failed` | The checkout session expired, or the payment was refused |
| `payment.closed_unpaid` | Session closed without payment — see the note below |
| `payment.captured` / `payment.capture_failed` | Manual capture from the order screen |
| `payment.canceled` / `payment.cancel_failed` | Void from the order screen |
| `order.marked_paid` | An order status actually changed to the paid status |
| `order.status_unhandled` | A payment status this module has no rule for |

### Refund

| Event | When |
|---|---|
| `refund.succeeded` | Refund accepted, with whether it was partial |
| `refund.rejected` | A local guard refused before anything left the store |
| `refund.failed` | The API refused it, or the request never completed |

`has_reason` is a **boolean**. The refund reason text is merchant-authored free
text and is on the "not shared" list.

### Webhook

The single most useful group a debug session carries: a merchant whose orders
never leave "pending" is almost always looking at one of these — a rotated
secret, a missing signature header, or a delivery that matched no order — and
none of it is visible from Paypercut's side.

| Event | When |
|---|---|
| `webhook.received` | A delivery arrived, duplicate or not |
| `webhook.rejected` | Signature refused, with which check failed |
| `webhook.payload_invalid` | The body was empty or unparsable |
| `webhook.order_updated` | An order was updated from a delivery |
| `webhook.unresolved` | No order matched the delivery |
| `webhook.skipped` | An event type this module does not handle |
| `webhook.registered` / `webhook.registration_failed` | Managing webhooks from settings |
| `webhook.deleted` / `webhook.delete_failed` | Managing webhooks from settings |

### Setup and administration

| Event | When |
|---|---|
| `connection.tested` | Test Connection ran, `ok` either way |
| `payment_domain.registered` / `payment_domain.registration_failed` | Wallet domain setup |
| `settings.payment_configs_unreadable` | The settings page could not list payment configurations |

### API and runtime

| Event | When |
|---|---|
| `api.request_failed` | A transport failure or a rejected request, with `duration_ms` |
| `api.request_slow` | A call that succeeded but took over 3s |
| `api.response_unparsable` | A body that was not JSON (byte count only) |
| `php.fatal` | A fatal that ended the request |

### Environment and lifecycle

| Event | When |
|---|---|
| `session.started` / `session.stopped` | Lifecycle |
| `environment.snapshot` | Module, OpenCart, PHP and theme versions |
| `environment.configuration` | How the module is configured; re-sent when settings are saved mid-session |
| `environment.plugins` | Installed extension codes and versions, chunked |

`environment.plugins` keeps the reference name rather than an OpenCart-flavoured
one, so a single query answers "which add-on broke this store" across every
Paypercut plugin. Likewise every failure carries `origin`
(`paypercut` / `plugin` / `theme` / `core`) and, for an extension,
`origin_plugin` — the code from the first stack frame outside our own directory.

`payment.closed_unpaid` is deliberately **not** called a failure. Paycore sets
`status=complete, payment_status=unpaid` on a successful authorisation awaiting
manual capture, so the state is ambiguous from the store's side. Reporting it as
a decline would put a false failure in front of a merchant whose payment worked.

## What never goes on the wire

Card data (screened with a Luhn check on every value), credentials of any kind,
refund reason text, customer names, email addresses, billing and shipping
addresses, order totals, line items, absolute filesystem paths, the admin user
id of whoever started the session, and upstream API prose.

That last one is the rule most easily lost in a port, and it is not only the
API's prose. **The Paypercut API quotes submitted input back** — a rejected key
appears inside the error message — so `Event::apiFailure()` always drops
`error.message` and carries the diagnosis in `api_code` / `api_param` /
`trace_id` / `error.type` instead. **OpenCart's own database adapter is worse**:
it puts the full SQL statement, and the database `user@host`, inside the
exception message, and the SQL on the checkout path contains the shopper's email
address. So `Event::failure()` never quotes an exception's message either —
`error.type`, `error.code` and the scrubbed stack carry the diagnosis.

`Event::fatal()` applies the same rule to `error_get_last()`. PHP's own fatal
text is quoted (`Call to a member function getId() on null`); the message of an
uncaught throwable is quoted only for the engine's global error classes
(`Error`, `TypeError`, `ArgumentCountError`, `ArithmeticError`,
`DivisionByZeroError`, `ParseError`), never for an `\Exception` and never for
`trigger_error()` prose. The throwable is still named in `error.type`.

A message the module authored itself is the diagnosis and stays: that is what
`->because('confirm threw ' . Event::shortClassName($e))` is for.

`EventQueue::append()` is the last gate. It screens the **whole envelope as it
will be serialised** — `attrs`, `error` (including `error.stack`) and the
correlation ids `about()` writes as top-level siblings — against five rules:
denied key names, credential value shapes, a Luhn PAN check, a literal
comparison against the store's actual secrets (including a head of one left
behind by the 256-byte clamp), and two levels of recursion. It drops the
**whole event** rather than the offending field: a field that trips the
assertion means the event was assembled wrongly, so the rest of it cannot be
trusted either.

Screening the correlation ids is not optional. `payment_id` and `order_ref` are
copied straight out of a webhook body, and a store with no webhook secret
configured accepts unsigned deliveries — so those ids are an unauthenticated
caller's to write. `tests/run.php` pins this by poisoning **every** field of a
maximal envelope in turn, and fails if a field is added to `Event::envelope()`
without a decision about screening it.

`TelemetrySession::credentials()` must enumerate every credential-bearing
setting. It currently lists `payment_paypercut_api_key` and
`payment_paypercut_webhook_secret`; **a future gateway adding its own settings
row silently weakens the literal-secret screen until it is added here.**

The merchant-facing promise lives in
`admin/view/template/extension/paypercut/payment/paypercut_telemetry_disclosure.twig`
(one source for both the panel and the consent modal) and must stay in step with
any store listing that repeats it.

## Budgets

| Constant | Value | Why |
|---|---|---|
| `MAX_QUEUE_EVENTS` | 200 | Anonymous storefront requests append here; unbounded growth is a denial of service against the store |
| `MAX_QUEUE_BYTES` | 65536 | The same bound in the dimension that actually hurts |
| `MAX_BATCH_BYTES` | 16384 | Well under the edge's 64 KiB body cap: the edge does not deduplicate, so a bigger batch loses more per failed POST |
| `MAX_BATCH_EVENTS` | 50 | The edge's own limit — matching it means never provoking an avoidable 413 |
| `Event::MAX_ATTRS` | 16 | Half the edge's 32; the edge truncates in sorted key order and would take the version fields first |
| `SentLog::MAX_ENTRIES` | 100 | The log is a tail, not a transcript, and the panel says so |
| `SESSION_MAX_SECONDS` | 3600 | With no revocation anywhere, this ceiling **is** the consent |

## Delivery

Events are delivered only from authenticated admin requests: the panel's status
poll, the Stop button, and one backstop on every admin page render while a
session is live. Never from a storefront request, never from the webhook, never
from cron. `Flusher::flushOnce()` refuses outright unless
`Context::markAdmin()` was called, which only the admin controllers do.

The flusher's response handling is a pure function, `Flusher::decide()`, unit
tested without a network or a database:

| status | outcome |
|---|---|
| 202 | delivered; `accepted`/`dropped` counted off the body |
| 401 | end the session — **never re-mint**, because nothing can revoke a token |
| 413 | halve the batch; not a failure, does not advance the give-up ladder |
| 429 | wait `Retry-After` (clamped to 900s); never ends the session |
| 503 / 504 | wait 120s; a statement about the edge, not about this token |
| 400 | drop the batch, count it, give up at the end of the ladder |
| anything else | back off `30 / 120 / 300` seconds, give up on the fourth |

## Structural blind spots

1. **A store that has never saved an API key cannot start a session** — the
   token is minted from it. First-time setup failures are invisible by
   construction.
2. **A store that has never saved an environment cannot start a session
   either**, by design: an unset environment yields no edge rather than a
   session pointed at the wrong one.
3. **A credential or environment change ends the session mid-request**
   (`key_changed` / `environment_changed`), so anything after that point in
   those requests is not recorded. Both emit `session.stopped` before tearing
   down.
4. **A card refused inside the embedded iframe is not visible.** Closing that
   needs browser-side telemetry.
5. **The global admin notice is registered at install time.** A store that
   installed an earlier version needs to reinstall the extension to get it.

## Tests

`php tests/run.php` — dependency-free, no OpenCart bootstrap needed. It pins the
environment pairing, the destination allow-list, all five deny rules, the
snapshot constructors reading their own schema, the dropped `error.message` on
an API failure, string bounding, the queue caps, batch splitting, the flusher's
whole decision table, and origin attribution.

`php tests/catalogue.php` — asserts every `Event::of|failure|apiFailure` name in
the source appears in the tables above, so an event added at a call site nobody
documents fails the check.
