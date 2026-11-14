---
title: "TLS and multiple workers"
---

The default server is one interpreter. Use verified TLS for protected endpoints and multiple processes for CPU capacity when your platform supports the prefork supervisor. Neither setting makes arbitrary PHP globals safe to share.

## Native TLS listener

```php
$app->listen('0.0.0.0:8443', [
    'tls' => [
        'certificate' => '/etc/asyncara/server-chain.pem',
        'privateKey' => '/etc/asyncara/server-key.pem',
    ],
]);
```

These are local PEM paths read during startup, before accepting requests. Protect key permissions and use a certificate valid for the hostname clients will verify. Supply `clientCa` to require client certificate verification for mTLS. Do not put key material in route closures, query strings or logs. Certificate replacement requires a new listener/worker lifecycle; there is no automatic hot-reload contract.

Native TLS does not add an HTTP/2 or HTTP/3 server. Put a carefully configured reverse proxy in front if the public edge needs capabilities outside the listener's scope.

## Multiple worker processes

```php
$app->listen('127.0.0.1:8080', ['workers' => 4]);
```

Workers require CLI, pcntl, posix and the supported Linux prefork lifecycle. Use a fixed port; `:0` is rejected for multiple workers. Start the supervisor **before** opening native engines, threads or connections. Create connections lazily inside each child. A parent-created live database/socket handle must not be copied across fork.

Each worker has independent PHP state, pools and per-process limits. Four workers with a pool of 8 can create 32 connections. Plan the sum against database/server limits. A shared in-memory Queue is not shared between worker processes.

The supervisor owns its child PIDs and signal handling. A worker failure shuts down the group instead of silently replaying work or pretending a reduced pool is healthy. Use an external service manager to restart the application according to your failure policy. `onReady` receives the bound address when startup succeeds; it is useful for readiness reporting, not a reason to open resources in the parent before fork.

## Graceful stop

`$app->stop()` stops admission and lets in-flight work finish within the configured shutdown behavior. SIGTERM/SIGINT integration is installed when pcntl is available and prior handlers are restored on exit. Do not use a remote unauthenticated `/shutdown` route in a real service. The supervisor can ultimately terminate workers that fail to leave; a forced kill cannot provide ordinary PHP cleanup guarantees.
