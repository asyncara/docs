---
title: "Locks, semaphores, events and queues"
---

These primitives coordinate Fibers in one interpreter. They do not provide cross-process exclusion for multiple server workers or ProcessPool children. Use a database or another shared mechanism when the protected state spans processes.

## Protect a short critical section

```php
use Asyncara\Concurrency\Lock;
$lock = new Lock();
$counter = 0;
$increment = function () use ($lock, &$counter): int {
    return $lock->synchronized(function () use (&$counter): int { return ++$counter; });
};
```

Lock is non-reentrant and task-owned. Only the acquiring task may release it. Do not wait for a child that needs the lock you are holding; that creates an application deadlock even when the Event Loop is working. Prefer `synchronized` to manual acquire/release, or use finally yourself.

## Bound active work

```php
use Asyncara\{Async, TaskGroup};
use Asyncara\Concurrency\Semaphore;
Async::run(function (): void {
    $slots = new Semaphore(4);
    Async::taskGroup(function (TaskGroup $group) use ($slots): void {
        for ($n = 0; $n < 20; $n++) {
            $group->spawn(fn() => $slots->withPermit(fn() => Async::sleep(0.01)));
        }
    });
});
```

`withPermit` returns the permit even if its callback throws. `tryAcquire` never suspends; false lets your application reject or defer work. A semaphore bounds active work, not the number of Tasks you allocated before entering it.

## Queue completion accounting

The default capacity is 100 items. `Queue(0)` explicitly requests unbounded buffering; choose a positive bound for normal services. `put` suspends when full, `tryPut` returns false without suspending, and `get` waits when empty. Call `taskDone` exactly once for each accepted item, including failed processing. `join` waits for that accounting to reach zero, not merely for an empty buffer.

```php
use Asyncara\Async;
use Asyncara\Concurrency\Queue;
use Asyncara\Exception\QueueClosedException;
Async::run(function (): void {
    $queue = new Queue(8);
    $consumer = Async::spawn(function () use ($queue): void {
        while (true) {
            try { $item = $queue->get(); } catch (QueueClosedException) { return; }
            try { echo $item, "\n"; } finally { $queue->taskDone(); }
        }
    });
    foreach (range(1, 20) as $item) $queue->put($item);
    $queue->close();
    $consumer->await();
    $queue->join();
});
```

Closing rejects new/pending puts but preserves buffered items for draining. Once drained, get throws `QueueClosedException`. Closing does not forgive unfinished accounting. Cancelling a consumer after delivery does not automatically requeue its item; retries and dead-letter policy belong to your application.

## Event as a shared condition

Event is manual-reset: `set()` releases current waiters and allows future waits until `reset()`. Reset does not retract an already delivered notification. Use it for a startup barrier or a shared initialization condition. Use Queue when you need individually counted messages. Queue `statistics()` reports buffered items, waiting readers/writers and unfinished work for diagnosing stalls.
