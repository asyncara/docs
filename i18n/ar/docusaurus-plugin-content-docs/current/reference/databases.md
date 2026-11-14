---
title: "قواعد البيانات والمجمعات"
---

التوقيعات مستخرجة من runtime الإصدار0.10.0. راجع [دليل الاستخدام](../data/transactions-pools.md) للملكية والأخطاء والتنفيذ. الدوال الموروثة تحت صنفها الأصلي، ودوال asyncara_* وآليات Scheduler الداخلية ليست واجهة تطبيقات.

## `Asyncara\Database\Database`

```text
static connectAsync(array $config): Asyncara\Future
```

## `Asyncara\SQLite\Connection`

```text
const SAVEPOINT_QUOTE = '"';
static open(string $path): Asyncara\Future
query(string $sql, array $parameters = []): Asyncara\Future
execute(string $sql, array $parameters = []): Asyncara\Future
prepare(string $sql): Asyncara\SQLite\Statement
beginTransaction(): Asyncara\Future
inTransaction(): bool
commit(): Asyncara\Future
rollback(): Asyncara\Future
transaction(callable $body): Asyncara\Future
close(): Asyncara\Future
savepoint(string $name): Asyncara\Future
rollbackTo(string $name): Asyncara\Future
releaseSavepoint(string $name): Asyncara\Future
```

## `Asyncara\SQLite\Statement`

```text
__construct(Asyncara\SQLite\Connection $connection, string $sql)
execute(array $parameters = []): Asyncara\Future
```

## `Asyncara\PostgreSQL\Connection`

```text
const SAVEPOINT_QUOTE = '"';
static connect(array $options, float $timeout = 30.0, int $notificationLimit = 1024, int $notificationByteLimit = 1048576, int $preparedCacheLimit = 0, int $preparedCacheByteLimit = 262144): Asyncara\Future
query(string $sql, array $parameters = []): Asyncara\Future
execute(string $sql, array $parameters = []): Asyncara\Future
prepare(string $sql): Asyncara\PostgreSQL\Statement
transaction(callable $body): Asyncara\Future
nextNotification(): array
statistics(): array
close(): void
beginTransaction(): Asyncara\Future
commit(): Asyncara\Future
rollback(): Asyncara\Future
inTransaction(): bool
savepoint(string $name): Asyncara\Future
rollbackTo(string $name): Asyncara\Future
releaseSavepoint(string $name): Asyncara\Future
```

## `Asyncara\PostgreSQL\Statement`

```text
__construct(Asyncara\PostgreSQL\Connection $connection, string $sql)
execute(array $parameters = []): Asyncara\Future
```

## `Asyncara\MySQL\Connection`

```text
const SAVEPOINT_QUOTE = '`';
static connect(array $options, float $timeout = 30.0): Asyncara\Future
query(string $sql, array $parameters = []): Asyncara\Future
execute(string $sql, array $parameters = []): Asyncara\Future
prepare(string $sql): Asyncara\MySQL\Statement
transaction(callable $body): Asyncara\Future
close(): void
beginTransaction(): Asyncara\Future
commit(): Asyncara\Future
rollback(): Asyncara\Future
inTransaction(): bool
savepoint(string $name): Asyncara\Future
rollbackTo(string $name): Asyncara\Future
releaseSavepoint(string $name): Asyncara\Future
```

## `Asyncara\MySQL\Statement`

```text
__construct(Asyncara\MySQL\Connection $connection, string $sql)
execute(array $parameters = []): Asyncara\Future
```

## `Asyncara\Redis\Connection`

```text
static connect(string $host = '127.0.0.1', int $port = 6379, ?string $password = NULL, ?string $user = NULL, bool $resp3 = false, float $timeout = 30.0, bool $tls = false, ?string $caFile = NULL, int $notificationLimit = 1024, int $notificationByteLimit = 1048576): Asyncara\Future
command(array $command): Asyncara\Future
pipeline(array $commands): Asyncara\Future
transaction(array $commands): Asyncara\Future
subscribe(array $channels): Asyncara\Future
nextMessage(): array
drainNotifications(): array
statistics(): array
close(): void
```

## `Asyncara\Pool\ConnectionPool`

```text
__construct(Closure $factory, Closure $closer, int $size = 8, int $pendingLimit = 1024)
withConnection(callable $body): mixed
statistics(): array
close(): void
```
