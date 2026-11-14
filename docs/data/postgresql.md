---
title: "PostgreSQL: libpq and connection affinity"
---

The PostgreSQL driver uses libpq's nonblocking connect, submission, flush and result-consumption state machines. Socket readiness is integrated with the scheduler. Do not substitute ext-pgsql or PDO calls and assume those calls become asynchronous.

## Verified TLS and parameters

```php
use Asyncara\Async;
use Asyncara\PostgreSQL\Connection;
Async::run(function () {
    $db = Connection::connect([
        'host' => 'db.example.net', 'port' => '5432',
        'dbname' => 'app', 'user' => 'app',
        'password' => getenv('DB_PASSWORD'),
        'sslmode' => 'verify-full', 'sslrootcert' => '/run/secrets/db-ca.pem',
    ], timeout: 10, preparedCacheLimit: 32)->await();
    try {
        $statement = $db->prepare('SELECT id, title FROM notes WHERE id = $1');
        $rows = $statement->execute([42])->await();
    } finally { $db->close(); }
});
```

This requires a real server, matching hostname certificate, CA and credentials. DNS uses Asyncara's resolver path before libpq connection establishment. Keep the logical hostname for certificate verification. Add `sslcert` and `sslkey` for client-certificate authentication when your server requires it. Do not downgrade to `sslmode=disable` to solve a verification error. SCRAM behavior depends on the installed libpq and server policy.

Results are buffered associative rows. Values follow libpq conversion semantics; validate application types rather than assuming SQL integers always arrive as PHP integers. Prepared caching is disabled by default. `preparedCacheLimit` and `preparedCacheByteLimit` bound the connection-local cache; `prepare()->execute()` uses that cache when enabled, whereas direct `query` is a distinct path. Server statement state belongs to that connection, not a global pool.

## LISTEN / NOTIFY

Use a dedicated connection for notification consumption:

```php
$db->execute('LISTEN app_events')->await();
$notification = $db->nextNotification();
```

`nextNotification` suspends until an event is available. Inspect its returned fields for your installed version before exposing payloads outside your application. Notification count and byte limits are configured at connect time; overflow is an error, not permission to silently lose business events. Use an external durable queue for delivery guarantees beyond PostgreSQL notifications.

## Cancellation and recovery

One connection serializes operations and preserves transaction affinity. A local timeout or cancellation cannot prove the server stopped a query. The driver may close the connection to abandon unsafe pending protocol state; replace that connection rather than returning it to a healthy pool. Do not automatically retry a write whose remote outcome is unknown. Use application idempotency keys where retries are required. See [transactions and pools](transactions-pools.md) for ownership and cleanup.
