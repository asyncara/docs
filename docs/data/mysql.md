---
title: "Oracle MySQL and MariaDB"
---

Both server families use Asyncara's MySQL API backed by MariaDB Connector/C asynchronous start/continue calls. This is a native socket-readiness state machine, not a PDO worker wrapper. Oracle MySQL and MariaDB remain separate compatibility targets; success against one does not certify every version of the other.

## Connect and bind

```php
use Asyncara\Async;
use Asyncara\MySQL\Connection;
Async::run(function () {
    $db = Connection::connect([
        'host' => '127.0.0.1', 'port' => 3306,
        'user' => 'app', 'password' => getenv('DB_PASSWORD'), 'database' => 'app',
        'tls' => true, 'caFile' => '/run/secrets/db-ca.pem',
    ], timeout: 10)->await();
    try {
        $rows = $db->prepare('SELECT id, title FROM notes WHERE id = ?')->execute([42])->await();
    } finally { $db->close(); }
});
```

TLS is enabled by default. In the current verified-TLS implementation, use a numeric host and a certificate with the matching IP subject alternative name. Arbitrary hostname-based verified Connector/C connections are not certified by this API. Configure the server certificate correctly; do not casually disable TLS. `certFile` and `keyFile` support client credentials, with `keyPassword` when applicable to the connector. Actual availability depends on the linked Connector/C TLS implementation.

For an explicitly trusted local development service, you can deliberately select `tls => false`; this is a different security scope. Use a Unix socket through `socket` when the server and deployment support it. Never commit passwords to source control.

## Queries, transactions and results

Parameter binding uses the native statement path. The PHP Statement is a reusable SQL/connection wrapper; it is not a promise of persistent server-side statement caching. `query` and `execute` resolve to arrays of rows. Results are bounded but buffered, so page large queries. A connection serializes its operations. A pool supplies concurrency through independent connections, not simultaneous transactions on one connection.

Use `transaction`, manual begin/commit/rollback and savepoints according to the [shared SQL guide](transactions-pools.md). SQL errors and connection loss are distinct from knowing whether a remote write committed. Cancellation may close an uncertain connection; the client does not promise acknowledged server-side cancellation, automatic reconnection or automatic query replay.

## Compatibility boundaries

The project has separate integration coverage for Oracle MySQL 8.4 and MariaDB 11.8 configurations. That is not a universal authentication-plugin, proxy or managed-service certification. Verify your server version, authentication plugin, TLS CA/IP identity and Connector/C build together. When reporting a failure, include these versions and the error category, never credentials or SQL parameter values.
