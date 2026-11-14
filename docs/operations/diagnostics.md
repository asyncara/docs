---
title: "Monitor and debug your application"
---

Use `Runtime::information()` to inspect the loaded capabilities and selected backend. Run this with the same launcher and environment as your application:

```php
use Asyncara\Runtime;
echo json_encode(Runtime::information(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), "\n";
```

For example, selecting Hyper requires a Hyper-enabled extension. Filesystem operations use native workers, while ProcessPool uses separate PHP processes. The capability information helps you check that your application is using the installation you intended.

## Inspect scheduler activity

`Runtime::diagnostics()` returns a snapshot of the current scheduler, or the last scheduler after its scope finishes. Inspect it after a small unit of work:

```php
use Asyncara\{Async, Runtime};
Async::run(function (): void {
    Async::sleep(0.01);
});
echo json_encode(Runtime::diagnostics(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), "\n";
```

Compare snapshots from equivalent points in your application's lifecycle, after completed work has been collected. Monitor process memory and file descriptors alongside these counters: Asyncara cannot account for every allocation made by PHP, external libraries or the operating system. Slow-task measurements identify long interpreter steps; they do not profile all code inside other extensions.

## Capture a bounded trace

Tracing is disabled by default. Enable it before entering the runtime, then extract the result after the runtime finishes:

```php
use Asyncara\{Async, Trace};
Trace::enable(capacity: 1024, classes: Trace::WAIT | Trace::TIMER | Trace::SCHEDULER | Trace::MARK);
try {
    Async::run(function (): void {
        Async::sleep(0.01);
        Trace::mark(100);
    });
    $snapshot = Trace::snapshot();
    echo json_encode($snapshot, JSON_THROW_ON_ERROR), "\n";
} finally {
    Trace::disable();
}
```

Choose event classes for the problem you are investigating: waits, jobs, scheduler activity, timers, I/O, lifecycle events or application marks. Numeric marks let you identify application checkpoints without adding request bodies or other payloads to the trace. `Trace::mark(100, freeze: true)` stops collection at that checkpoint so later events cannot overwrite it.

The buffer accepts **16–4,096 events**. Each event has a sequence number and a monotonic timestamp. When the ring wraps, older events are overwritten and the counters record the loss; the remaining events are only a window of execution. Export is capped at **4 MiB**, and JSON conversion also uses PHP memory. Tracing adds work, so enable it for a bounded diagnostic run and compare application behavior with tracing disabled.

## Save and interpret a trace

After the runtime finishes, `Trace::dump($newPath)` creates a new file and rejects an existing destination. Choose a writable application directory and a unique filename. `Trace::writeTo($stream)` writes to an output stream you supply. Both operations may block; keep extraction outside request handlers and scheduler dispatch. Incomplete writes raise an error.

For delayed timers, follow the sequence from timer registration to wait entry, wait exit and timer dispatch. Compare the requested wait timeout with the elapsed duration and returned readiness events. A long elapsed wait can include time when the operating system did not schedule the process. Also check for ordinary blocking PHP calls, CPU-heavy loops and synchronous logging in your handlers; see [troubleshooting](troubleshooting.md).

Traces omit application payloads, but process and resource metadata can still be sensitive. Store output with restricted access and review it before sharing. For help, include a small reproducer, PHP and Asyncara versions, operating system, backend and whether tracing was enabled. Never include credentials or private request data.
