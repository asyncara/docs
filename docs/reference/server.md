---
title: "Native Server"
---

Signatures generated from the installed 0.10.0 runtime. Read the [usage guide](../server/routing.md) for ownership, error and backend semantics. Inherited methods are listed under their declaring class. Low-level asyncara_* transport functions and internal scheduler machinery are not application APIs.

## `Asyncara\App`

```text
route(string $method, string $path, callable $handler): Asyncara\Server\Route
get(string $path, callable $handler): Asyncara\Server\Route
post(string $path, callable $handler): Asyncara\Server\Route
put(string $path, callable $handler): Asyncara\Server\Route
patch(string $path, callable $handler): Asyncara\Server\Route
delete(string $path, callable $handler): Asyncara\Server\Route
group(string $prefix, callable $register): void
middleware(string $name, callable $handler): Asyncara\App
listen(string $address, array $options = [], ?callable $onReady = NULL): void
stop(): void
stats(): ?array
```

## `Asyncara\Server\Request`

```text
readonly string $path;
readonly string $method;
readonly string $target;
readonly string $body;
__construct(string $method, string $target, string $body = '', string $rawHeaders = '', array $params = [], ?Asyncara\Server\RequestBodyStream $input = NULL)
param(string $name, mixed $default = NULL): mixed
query(string $name, mixed $default = NULL): mixed
header(string $name, ?string $default = NULL): ?string
json(bool $associative = true): mixed
stream(): iterable
```

## `Asyncara\Server\Response`

```text
readonly string $body;
readonly int $status;
readonly array $headers;
__construct(string $body = '', int $status = 200, array $headers = [])
static json(mixed $value, int $status = 200, array $headers = []): Asyncara\Server\Response
static text(string $value, int $status = 200, array $headers = []): Asyncara\Server\Response
static stream(iterable $chunks, int $status = 200, array $headers = []): Asyncara\Server\StreamResponse
```

## `Asyncara\Server\StreamResponse`

```text
readonly int $status;
readonly array $headers;
__construct(iterable $chunks, int $status = 200, array $headers = [])
close(): void
```

## `Asyncara\Server\Route`

```text
readonly Closure $handler;
readonly string $method;
readonly string $path;
__construct(string $method, string $path, callable $handler)
middleware(callable|string $middleware): Asyncara\Server\Route
```

## `Asyncara\Server\RequestBodyStream`

```text
__construct(int $id, ?Asyncara\Server\Inbox $inbox)
getIterator(): Traversable
consume(callable $consumer): void
close(): void
```
