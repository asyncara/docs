---
title: "Diagnostics and bounded traces"
---

Start with capability inspection. The public API name does not identify its execution backend: HTTP may use curl multi or Hyper, files use workers, and ProcessPool uses separate processes.

```php
use Asyncara\Runtime;
echo json_encode(Runtime::information(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), "\n";
```

Use `Runtime::diagnostics()` inside or after a runtime scope to inspect the current/last scheduler snapshot. Treat field availability as versioned; do not assume it observes all third-party allocations or all blocking code. Slow-task measurements identify long interpreter steps, not an automatic profiler of arbitrary extensions.

## Opt-in bounded provenance

```php
use Asyncara\{Async, Trace};
Trace::enable(capacity: 1024, classes: Trace::WAIT | Trace::TIMER | Trace::SCHEDULER | Trace::MARK);
try {
    Async::run(function () { Async::sleep(0.01); Trace::mark(100); });
    $snapshot = Trace::snapshot();
    echo json_encode($snapshot, JSON_THROW_ON_ERROR), "\n";
} finally { Trace::disable(); }
```

Configure outside Async::run and extract after it finishes. The native ring stores numeric transition metadata, sequence numbers and monotonic timestamps, not application payloads. Overflow is visible in counters; a wrapped ring does not contain the complete request history. `mark($reason, freeze: true)` freezes collection for later extraction. Event classes select wait, job, scheduler, timer, I/O, lifecycle and application marks.

`dump($newPath)` creates a new file without overwriting an existing report. `writeTo($stream)` uses a caller-owned output stream. Extraction can block and is deliberately outside scheduler dispatch. Output is bounded; incomplete writes raise an error. Store traces in a private directory and still review metadata before sharing.

## Interpreting evidence

A long wall-clock wait may include host descheduling. Compare requested timeout, actual wait duration, ready events, timer deadlines and dispatch sequence before blaming a scheduler. An instrumented pass is diagnostic evidence, not an uninstrumented endurance pass. The historical zero-tick FPM failure is not retroactively explained by a new trace of a different run.

For actionable reports include package version, PHP ABI/NTS/ZTS, backend, OS/architecture, sanitized reproducer and whether diagnostics were enabled. Never send credentials, SQL values, Authorization headers or private application payloads.

The trace capacity is 16..4,096 events. Export has a separate 4 MiB cap; JSON conversion uses additional PHP memory.
