---
title: "SQLite: serialized worker operations"
---

SQLite uses bounded native workers. A query suspends its caller while a worker executes the blocking SQLite API; it is not socket-based nonblocking I/O. Operations on the same connection are serialized. Independent connections can make progress concurrently, subject to SQLite's own database locking.

## Open, bind, read and close

```php
use Asyncara\Async;
use Asyncara\SQLite\Connection;
Async::run(function () {
    $db = Connection::open(':memory:')->await();
    try {
        $db->execute('CREATE TABLE notes (id INTEGER PRIMARY KEY, title TEXT)')->await();
        $insert = $db->prepare('INSERT INTO notes(title) VALUES (?)');
        $insert->execute(['hello'])->await();
        $rows = $db->query('SELECT id, title FROM notes WHERE id = ?', [1])->await();
        echo json_encode($rows, JSON_THROW_ON_ERROR), "\n";
    } finally {
        Async::shield(fn() => $db->close()->await());
    }
});
```

The result is an array of associative rows. `execute` shares the row-result model; it is not an affected-row-count API. Bind values instead of interpolating SQL. A Statement retains its Connection and SQL; this public wrapper does not promise a persistent compiled-statement cache.

`:memory:` belongs to one connection. Opening another connection creates a different database. For a shared on-disk database, choose a private application directory with suitable permissions. WAL and busy-timeout configuration should follow your workload and SQLite deployment requirements; asynchronous waiting does not remove database lock contention.

## Transactions and cancellation

Use [transactions and pools](transactions-pools.md) for callback transactions or explicit begin/commit/rollback. Statements in a transaction must execute from its owning Task. A child Task trying to use that same connection may wait for the parent transaction and deadlock if the parent joins it.

A cancelled waiter does not roll back an already executed statement. Native work must complete or drain before its memory and handle can be released. Always close explicitly, awaiting SQLite's asynchronous close in a cancellation shield. Do not clone or serialize the connection or statement. Sharing the same object reference is supported through its serialization gate; it does not create independent transaction ownership.

## Bound results

Results are materialized before PHP conversion. Use pagination and projections for large tables rather than assuming query results are streamed. Native accounting bounds Asyncara-managed buffers, while SQLite's own allocations and PHP result arrays are separate. The runnable `examples/sqlite.php` demonstrates transaction/savepoint cleanup with a disposable in-memory database; it requires no database service.
