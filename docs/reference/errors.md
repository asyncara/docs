---
title: "Errors"
---

Signatures generated from the installed 0.10.0 runtime. Read the [usage guide](../runtime/cancellation.md) for ownership, error and backend semantics. Inherited methods are listed under their declaring class. Low-level asyncara_* transport functions and internal scheduler machinery are not application APIs.

## `Asyncara\Exception\OperationException`

Extends `RuntimeException`.

Standard inherited exception behavior.

## `Asyncara\Exception\CancelledException`

Extends `RuntimeException`.

Standard inherited exception behavior.

## `Asyncara\Exception\TimeoutException`

Extends `Asyncara\Exception\CancelledException`.

Standard inherited exception behavior.

## `Asyncara\Exception\WorkerException`

Extends `RuntimeException`.

Standard inherited exception behavior.

## `Asyncara\Exception\FilesystemException`

Extends `Asyncara\Exception\OperationException`.

```text
readonly int $errno;
readonly bool $committed;
__construct(string $message, int $errno, bool $committed = false)
isMissing(): bool
```

## `Asyncara\Exception\QueueClosedException`

Extends `RuntimeException`.

Standard inherited exception behavior.

## `Asyncara\Exception\CapacityException`

Extends `OverflowException`.

Standard inherited exception behavior.

## `Asyncara\Exception\DeadlockException`

Extends `RuntimeException`.

Standard inherited exception behavior.

## `Asyncara\Redis\ServerException`

Extends `RuntimeException`.

Standard inherited exception behavior.

## `Asyncara\WebSocket\ProtocolException`

Extends `Asyncara\Exception\OperationException`.

```text
readonly int $closeCode;
__construct(string $message, int $closeCode = 1002)
```

## `Asyncara\Server\RequestBodyException`

Extends `RuntimeException`.

```text
readonly int $status;
__construct(int $status)
```

## `Asyncara\HTTP\Psr\NetworkException`

Extends `RuntimeException`.

```text
__construct(Psr\Http\Message\RequestInterface $request, Throwable $previous)
getRequest(): Psr\Http\Message\RequestInterface
```

## `Asyncara\HTTP\Psr\RequestException`

Extends `RuntimeException`.

```text
__construct(Psr\Http\Message\RequestInterface $request, Throwable $previous)
getRequest(): Psr\Http\Message\RequestInterface
```
