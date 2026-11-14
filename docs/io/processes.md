---
title: "ProcessPool for CPU-bound work"
---

Fibers cooperate on one interpreter thread. A CPU-heavy PHP loop blocks other Fibers unless it yields. ProcessPool runs registered jobs in separate OS processes, providing genuine parallel execution without sharing Zend objects between threads.

## Register trusted jobs

Create a bootstrap file that returns a registry:

```php
<?php
// jobs.php
return ['digest' => static fn(string $text): string => hash('sha256', $text)];
```

Then submit bounded jobs from the runtime:

```php
use Asyncara\Async;
use Asyncara\Pool\ProcessPool;
Async::run(function () {
    $pool = new ProcessPool(2, __DIR__.'/jobs.php', queueLimit: 32);
    try {
        $results = Async::gather([
            $pool->submit('digest', ['first'], timeout: 5),
            $pool->submit('digest', ['second'], timeout: 5),
        ]);
        echo json_encode($results, JSON_THROW_ON_ERROR), "\n";
    } finally { $pool->close(); }
});
```

The bootstrap is trusted executable PHP. Never accept its path or job registry from an HTTP request. Arguments and results cross a bounded JSON-compatible IPC protocol; resources, arbitrary object graphs and closures are not shared. Each child starts a PHP process with its own configuration/bootstrap, not an inherited live database connection. Load required dependencies explicitly in that bootstrap.

## Capacity and failure

Worker count is 1..64, with bounded queued-job count/bytes and worker memory policy. The default worker memory setting is 256 MiB and the queue defaults to 256 jobs/64 MiB. These do not cap every child library allocation or OS buffer; apply deployment resource limits as well. Inspect `statistics()` instead of assuming every queued job has started.

Timeout/cancellation or a crashed worker can cause that child to be discarded and replaced. The failed job is not automatically replayed: it may already have external side effects. Choose idempotency and retry policy explicitly. `close()` cancels pending work and reaps children; always call it from a finally block. Do not serialize/clone pools or carry them across a fork.

This is not a userland ThreadPool. ZTS interpreter-thread execution remains outside the supported model. ProcessPool requires process-launch facilities and the bundled worker entry point; sandboxed hosts and Android packages need separate availability checks. See `examples/jobs.php` and `examples/processes.php` for the runnable pair.

:::caution Initial prebuilt package limitation
The initial Linux 0.10.0 binary bundle omits `bin/worker.php`. ProcessPool therefore requires a complete source/runtime installation containing that entry point; the standalone bundle alone is insufficient.
:::
