---
title: "Transactions and connection pools"
---

SQL connections share useful lifecycle primitives, but they are not interchangeable protocol engines. The Database factory selects SQLite, PostgreSQL or MySQL/MariaDB. Redis retains its own command and MULTI/EXEC semantics.

## A transaction has one Task owner

```php
$db->transaction(function ($connection) {
    $connection->execute('INSERT INTO notes(title) VALUES (?)', ['draft'])->await();
    $connection->savepoint('optional_step')->await();
    try {
        $connection->execute('INSERT INTO notes(title) VALUES (?)', ['extra'])->await();
    } catch (Throwable $error) {
        $connection->rollbackTo('optional_step')->await();
        throw $error;
    }
    $connection->releaseSavepoint('optional_step')->await();
})->await();
```

This SQL uses SQLite/MySQL placeholders; PostgreSQL uses `$1`. A successful callback commits; a failing callback triggers rollback cleanup. Keep all transaction operations in the callback's owning Task. Spawning a child that waits on the same connection and then joining it can create a circular wait. For optional recovery, decide explicitly whether to rethrow after rolling back to a savepoint.

Manual control is available when callback scope does not fit:

```php
$db->beginTransaction()->await();
try {
    // Perform statements from this same Task.
    $db->commit()->await();
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        Asyncara\Async::shield(fn() => $db->rollback()->await());
    }
    throw $error;
}
```

`inTransaction` reflects the client's tracked ownership state; it is not an independent server-health probe. Do not mix raw BEGIN/COMMIT SQL with managed transaction APIs. Use named savepoints for nesting, not a nested begin call. Savepoint names must satisfy the API's identifier restrictions; never use untrusted SQL fragments.

## Borrow a connection within one scope

```php
use Asyncara\Pool\ConnectionPool;
use Asyncara\SQLite\Connection;
$pool = new ConnectionPool(
    fn() => Connection::open('/srv/app/notes.sqlite'),
    fn(Connection $connection) => $connection->close(),
    size: 4, pendingLimit: 128,
);
$rows = $pool->withConnection(fn(Connection $db) => $db->query('SELECT id, title FROM notes')->await());
```

Run borrowing inside Async::run or an App handler. The pool is lazy: creating it does not eagerly open every connection. It awaits asynchronous factories/closers. A borrower exception discards the connection rather than assuming it remains healthy. Do not retain a borrowed connection after the callback returns, close it yourself on the success path, or return an unresolved operation that still needs exclusive access. Pool capacity bounds connections, while pendingLimit bounds queued acquisitions.

After all borrowers finish, call `close`; closing with active borrowers is rejected. In a multiworker server, each process owns its own pool, so total connections scale with workers × pool size. Pooling does not make a transaction shareable across requests or guarantee transparent retries.
