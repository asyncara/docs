---
title: "Cancellation, deadlines and ownership"
---

Cancellation is cooperative. It is observed at waits, timers, I/O readiness and synchronization points; it does not preempt arbitrary PHP computation or roll back bytes already sent. Before adding a deadline, identify who owns the operation and who only waits for it.

## A waiter is not necessarily an owner

`$future->await($token)` associates cancellation with that wait. Cancelling one waiter does not implicitly cancel shared work. `Async::await($future)` is different: use it for an **owned** operation. If its scope exits while the Future is pending, it cancels and joins that operation. Do not use the ownership form indiscriminately for a Future another consumer still needs.

```php
use Asyncara\{Async, CancellationSource};
use Asyncara\Exception\CancelledException;
Async::run(function (): void {
    $operation = Async::spawn(function (): string { Async::sleep(0.02); return 'result'; });
    $source = new CancellationSource();
    $waiter = Async::spawn(fn() => $operation->await($source->token));
    $source->cancel();
    try { $waiter->await(); } catch (CancelledException) { /* This wait ended. */ }
    echo $operation->await();
});
```

`Task::cancel()` requests task cancellation. `Future::cancel()` invokes its operation-specific canceller, if any. Not every Future has cancellable native work. Neither call is a server-side PostgreSQL/MySQL cancellation acknowledgement.

## Deadlines around complete units of work

```php
use Asyncara\Async;
use Asyncara\Exception\TimeoutException;
Async::run(function (): void {
    try { Async::timeout(0.05, fn() => Async::sleep(1)); }
    catch (TimeoutException) { echo "deadline exceeded\n"; }
});
```

Durations in `Async` are seconds. The separate global helper `Asyncara::sleep(int)` takes **milliseconds**. Prefer `Asyncara\Async::sleep(float)` when you want uniform units.

`timeout` cancels its child and joins cleanup. Returning can therefore take longer than the deadline when native work or cleanup cannot be interrupted immediately. It is not a hard real-time completion guarantee. A transport connect timeout, total HTTP timeout and pool-admission timeout are different contracts; wrap a complete operation in `Async::timeout` when admission must be included.

## Cleanup without leaking an acquired resource

`shield` defers the current task's default cancellation while its callback runs. Use it for bounded cleanup. `using` shields acquisition until ownership is established, checks cancellation before the body, and always releases:

```php
use Asyncara\Async;
use Asyncara\IO\{File, Files};
Async::run(fn() => Async::using(
    fn() => Files::open('/tmp/data.txt', 'w'),
    fn(File $file) => $file->write('saved')->await(),
    fn(File $file) => $file->close(),
));
```

A cancelled filesystem write may already have modified the file. A cancelled HTTP POST may already have changed remote state. Retries require application-level idempotency, not a guess based on a timeout exception. Keep the Task returned by `repeat` and cancel/join it in finally; cancel unused one-shot `Timer` objects returned by `callLater` as well.
