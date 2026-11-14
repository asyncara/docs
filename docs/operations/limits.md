---
title: "Resource limits and cancellation ownership"
---

Use limits at several layers. Asyncara-managed native memory, PHP's allocator, external libraries, kernel buffers and child processes are different budgets. A single memory limit cannot truthfully cover all of them.

```php
use Asyncara\Runtime;
Runtime::configure([
    'taskLimit' => 10000,
    'futureLimit' => 20000,
    'nativeMemoryLimit' => 128 * 1024 * 1024,
    'httpMaxConnections' => 64,
    'httpMaxHostConnections' => 8,
]);
```

Configure before starting a Scheduler. Task/Future admission failures are explicit; they do not silently drop work. Choose capacity from peak simultaneous operations, not total lifetime request count. The native memory ledger does not include all libcurl, OpenSSL, libpq, Connector/C or SQLite allocations.

| Layer | Capacity policy | Application action |
|---|---|---|
| Queue / producer stream | Block producer when full | Await admission; close abandoned streams |
| Tasks / Futures / managed allocations | Reject with capacity/error | Bound submission and handle overload |
| ConnectionPool | Bounded connections and queued acquisitions | Keep leases short; do not leak borrowers |
| Redis / PostgreSQL notifications | Bounded count and bytes; fail on overflow | Consume promptly; use durable messaging when needed |
| HTTP / WebSocket bodies | Bounded buffers/messages | Stream large data; respect chunk/message limits |
| ProcessPool | Bounded jobs, IPC bytes and workers | Back off on admission and configure OS limits |

## A waiter is not always the operation owner

`Future::await($token)` cancels that wait without automatically destroying a shared operation. `Async::await($future)` is the stronger owning await form: it cancels/joins its unfinished operation on exit. A Task's cancellation token is cooperative. Database cancellation does not prove remote acknowledgement; file cancellation does not undo a write; HTTP cancellation cannot retract sent bytes.

Resource-bearing objects generally reject cloning and serialization. Assignment shares the same PHP object, not an independently owned descriptor. Close resources in finally and await asynchronous close where required. Normal request shutdown drains/cancels native ownership, but SIGKILL, OOM termination and host failure cannot promise application-level cleanup or remote rollback.

## Deployment budgets

Set PHP memory_limit, process/container limits, descriptor limits, connection limits and request-body limits consistently. A multiworker App multiplies per-process pools and memory. Monitor steady-state trends after warmup rather than declaring no leak from one RSS reading. Use [diagnostics](diagnostics.md) to inspect backend and pending work; diagnostic coverage is intentionally bounded.
