---
title: "Files, networking and processes"
---

Signatures generated from the installed 0.10.0 runtime. Read the [usage guide](../io/filesystem.md) for ownership, error and backend semantics. Inherited methods are listed under their declaring class. Low-level asyncara_* transport functions and internal scheduler machinery are not application APIs.

## `Asyncara\IO\Files`

```text
static open(string $path, string $mode = 'r'): Asyncara\Future
static read(string $path): Asyncara\Future
static write(string $path, string $data): Asyncara\Future
static replaceAtomic(string $path, string $data, bool $durable = false, int $permissions = 384): Asyncara\Future
static append(string $path, string $data): Asyncara\Future
static delete(string $path): Asyncara\Future
static rename(string $from, string $to): Asyncara\Future
static mkdir(string $path): Asyncara\Future
static rmdir(string $path): Asyncara\Future
static stat(string $path): Asyncara\Future
static entries(string $path): Asyncara\Future
static copy(string $from, string $to): Asyncara\Future
static exists(string $path): Asyncara\Future
static readStream(string $path, int $chunkSize = 65536): Asyncara\Stream\AsyncStream
static writeStream(string $path, iterable $chunks): Asyncara\Future
```

## `Asyncara\IO\File`

```text
static open(string $path, string $mode = 'r'): Asyncara\Future
static stage(string $destination): Asyncara\Future
publish(bool $durable = false): Asyncara\Future
read(int $length = 65536): Asyncara\Future
write(string $data): Asyncara\Future
seek(int $offset, int $origin = 0): Asyncara\Future
stat(): Asyncara\Future
sync(): Asyncara\Future
isClosed(): bool
close(): Asyncara\Future
```

## `Asyncara\IO\TcpStream`

```text
__construct(mixed $stream)
static connect(string $host, int $port, float $timeout = 30.0, bool $tls = false, ?string $caFile = NULL): Asyncara\Future
read(int $maxBytes = 65536): string
readExactly(int $bytes): string
write(string $data): void
resource(): mixed
close(): void
```

## `Asyncara\IO\TcpServer`

```text
__construct(string $address = '127.0.0.1', int $port = 0, int $backlog = 128)
address(): string
accept(): Asyncara\IO\TcpStream
close(): void
```

## `Asyncara\IO\UdpSocket`

```text
__construct(string $address = '127.0.0.1', int $port = 0)
address(): string
send(string $data, string $address): int
receive(): array
close(): void
```

## `Asyncara\IO\Dns`

```text
static resolve(string $host): Asyncara\Future
```

## `Asyncara\WebSocket\Client`

```text
static connect(string $url, array $protocols = [], array $headers = [], ?string $caFile = NULL, float $timeout = 10.0, int $messageLimit = 1048576, int $queueLimit = 16, int $queueBytes = 4194304, float $idleTimeout = 30.0): Asyncara\Future
```

## `Asyncara\WebSocket\Server`

```text
static accept(Asyncara\IO\TcpStream $stream, array $origins = [], array $protocols = [], ?callable $authorize = NULL, float $timeout = 5.0, int $messageLimit = 1048576, int $queueLimit = 16, int $queueBytes = 4194304, float $idleTimeout = 30.0): Asyncara\Future
```

## `Asyncara\WebSocket\Connection`

```text
readonly ?string $protocol;
receive(): ?Asyncara\WebSocket\Message
send(string $data, bool $binary = false): void
ping(?string $payload = NULL, float $timeout = 5.0): Asyncara\Future
close(int $code = 1000, string $reason = '', float $timeout = 5.0): void
abort(): void
exception(): ?Throwable
statistics(): array
```

## `Asyncara\WebSocket\Message`

```text
readonly string $data;
readonly bool $binary;
__construct(string $data, bool $binary = false)
```

## `Asyncara\Pool\ProcessPool`

```text
__construct(int $workers, string $bootstrap, int $queueLimit = 256, int $queueBytes = 67108864, int $workerMemoryBytes = 268435456)
submit(array|string $job, array $arguments = [], float $timeout = 30.0): Asyncara\Future
statistics(): array
close(): void
```
