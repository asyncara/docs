---
title: "Runtime"
---

Signatures generated from the installed 0.10.0 runtime. Read the [usage guide](../runtime/tasks.md) for ownership, error and backend semantics. Inherited methods are listed under their declaring class. Low-level asyncara_* transport functions and internal scheduler machinery are not application APIs.

## `Asyncara`

```text
static sleep(int $milliseconds): void
```

## `Asyncara\Async`

```text
static run(callable $body): mixed
static spawn(callable $body, bool $inheritContext = true): Asyncara\Task
static await(Asyncara\Future $future): mixed
static gather(array $tasks): array
static sleep(float $seconds): void
static cancel(Asyncara\Task|Asyncara\Future $task): void
static callLater(float $seconds, callable $body): Asyncara\Timer
static repeat(float $seconds, callable $body): Asyncara\Task
static shield(callable $body): mixed
static timeout(float $seconds, callable $body): mixed
static taskGroup(callable $body): mixed
static wait(array $tasks, ?float $timeout = NULL, bool $all = true): Asyncara\WaitResult
static asCompleted(array $tasks): Generator
static using(callable $acquire, callable $body, callable $release): mixed
```

## `Asyncara\Runtime`

```text
static configure(array $options): void
static configuration(): array
static information(): object
static supportsUserlandThreads(): bool
static enableHooks(int $flags = 1, string $policy = 'allow'): Asyncara\HookReport
static timeHooksEnabled(): bool
static strictBlockingDetection(bool $enabled): void
static diagnostics(): array
```

## `Asyncara\Future`

```text
__construct()
static pendingCount(): int
isCompleted(): bool
isCancelled(): bool
exception(): ?Throwable
result(): mixed
await(?Asyncara\CancellationToken $cancellation = NULL): mixed
cancel(): void
```

## `Asyncara\Task`

Extends `Asyncara\Future`.

```text
readonly Fiber $fiber;
readonly Asyncara\CancellationSource $cancellation;
readonly int $id;
isRunning(): bool
cancel(): void
```

## `Asyncara\Awaitable`

```text
await(?Asyncara\CancellationToken $cancellation = NULL): mixed
```

## `Asyncara\Timer`

```text
__construct(Closure $remove)
cancel(): void
isActive(): bool
```

## `Asyncara\TaskGroup`

```text
spawn(callable $body): Asyncara\Task
cancel(): void
```

## `Asyncara\CancellationSource`

```text
readonly Asyncara\CancellationToken $token;
__construct()
cancel(?Asyncara\Exception\CancelledException $reason = NULL): void
```

## `Asyncara\CancellationToken`

```text
isRequested(): bool
throwIfRequested(): void
```

## `Asyncara\WaitResult`

```text
readonly array $completed;
readonly array $pending;
__construct(array $completed, array $pending)
```

## `Asyncara\TaskResult`

```text
readonly mixed $value;
readonly ?Throwable $error;
__construct(mixed $value, ?Throwable $error)
static from(Asyncara\Future $future): Asyncara\TaskResult
```

## `Asyncara\Context`

```text
static set(string $key, mixed $value): void
static get(string $key, mixed $default = NULL): mixed
static all(): array
static isolated(callable $body, array $values = []): mixed
```

## `Asyncara\Trace`

```text
const WAIT = 1;
const JOB = 2;
const SCHEDULER = 4;
const TIMER = 8;
const IO = 16;
const LIFECYCLE = 32;
const MARK = 64;
const ALL = 127;
static enable(int $capacity = 1024, int $classes = 127): void
static disable(): void
static mark(int $reason, bool $freeze = false): void
static snapshot(): array
static writeTo(mixed $stream): int
static dump(string $path): int
```

## `Asyncara\Hook`

```text
const TIME = 1;
const HTTP = 2;
const STREAM = 4;
const FILESYSTEM = 8;
const DATABASE = 16;
const ALL = 1;
const ALLOW_BLOCKING = 'allow';
const WARN = 'warn';
const STRICT = 'strict';
```

## `Asyncara\HookReport`

```text
__construct(array $available, array $missing)
enabled(): array
unsupported(): array
reasons(): array
```
