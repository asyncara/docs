---
title: "Concurrency and streams"
---

Signatures generated from the installed 0.10.0 runtime. Read the [usage guide](../runtime/synchronization.md) for ownership, error and backend semantics. Inherited methods are listed under their declaring class. Low-level asyncara_* transport functions and internal scheduler machinery are not application APIs.

## `Asyncara\Concurrency\Queue`

```text
__construct(int $capacity = 100)
put(mixed $value): void
tryPut(mixed $value): bool
get(): mixed
taskDone(): void
join(): void
count(): int
statistics(): array
close(): void
```

## `Asyncara\Concurrency\Lock`

```text
__construct()
acquire(): void
release(): void
synchronized(callable $body): mixed
```

## `Asyncara\Concurrency\Semaphore`

```text
__construct(int $permits)
acquire(): void
release(): void
withPermit(callable $body): mixed
```

## `Asyncara\Concurrency\Event`

```text
__construct(bool $set = false)
set(): void
reset(): void
isSet(): bool
wait(): void
```

## `Asyncara\Stream\AsyncStream`

```text
static fromProducer(callable $producer, int $capacity = 16): Asyncara\Stream\AsyncStream
getIterator(): Traversable
consume(callable $consumer): void
close(): void
```

## `Asyncara\Stream\ReadableStream`

```text
consume(callable $consumer): void
close(): void
```

## `Asyncara\Stream\Emitter`

```text
__construct(Asyncara\Concurrency\Queue $queue)
emit(mixed $item): void
```
