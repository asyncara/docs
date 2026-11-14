---
title: "Tasks, Futures and structured work"
---

`Async::run()` creates a Scheduler, runs the root task, and joins scheduled work before returning the root's value. Nested runtimes are rejected. A repeating task left alive can prevent shutdown; keep its handle and cancel it deliberately.

## Task versus Future

A Task runs PHP inside a Fiber and is also a Future. Other Futures represent native operations. Both provide `await()`, `isCompleted()`, `result()` and `exception()`. A completed result can be awaited repeatedly; returned objects remain shared references. `result()` before completion throws, and a rejected Future rethrows its stored exception.

Submission is eager, either immediately or when its submission Task runs. Not awaiting a Future does not mean a request was never sent. The Scheduler retains pending native work through completion collection. Public infrastructure methods marked internal, including completion mutation and Task bookkeeping, are not normal application extension points.

## Collecting work

```php
use Asyncara\{Async, TaskResult};
Async::run(function (): void {
    $jobs = [];
    foreach ([1, 2, 3] as $id) {
        $jobs[$id] = Async::spawn(function () use ($id): int {
            Async::sleep(0.001 * $id);
            return $id * 10;
        });
    }
    foreach (Async::asCompleted($jobs) as $id => $finished) {
        $result = TaskResult::from($finished);
        if ($result->error) { echo "failed $id\n"; continue; }
        echo $id.':'.$result->value."\n";
    }
});
```

`gather` preserves keys and awaits in input order. If it throws, it does not automatically cancel every other entry. `asCompleted` yields Futures in observed completion order, not a precise network-packet chronology. `wait($jobs, timeout: 0.1, all: false)` returns `WaitResult` with `completed` and `pending` arrays. Its timeout leaves pending work alive; the owner decides what happens next.

## Fail as a group

```php
use Asyncara\{Async, TaskGroup};
Async::run(function (): void {
    Async::taskGroup(function (TaskGroup $group): void {
        $group->spawn(function (): void { Async::sleep(0.01); echo "A\n"; });
        $group->spawn(function (): void { Async::sleep(0.02); echo "B\n"; });
    });
    echo "Both children have finished\n";
});
```

A failing child cancels siblings and joins cleanup. Failure in the group body does the same. Keep a transaction, borrowed connection or open stream in the scope that owns its cleanup; do not let a child outlive a borrowed resource.

## Fairness and admission

An infinite CPU loop does not yield. Split small CPU batches with `Async::sleep(0)` at sensible boundaries or move substantial CPU work to ProcessPool. Avoid creating a million Tasks that immediately wait on a semaphore: their object allocation has already happened. A bounded Queue and fixed consumers constrain both admitted and running work.

Call `Runtime::configure(['taskLimit'=>2000,'futureLimit'=>4000])` before entering the runtime. Capacity failures are explicit exceptions, not automatic memory expansion. See [limits](../operations/limits.md) and [cancellation](cancellation.md).
