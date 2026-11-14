---
title: "Deployment and request lifecycle"
---

Native Server runs a long-lived CLI process. A PHP-FPM-style assumption that globals disappear after every request is unsafe. Keep immutable configuration at process scope, request data in Request/Context, and database leases in request-local try/finally scopes.

## Capacity configuration

```php
use Asyncara\Runtime;
Runtime::configure([
    'taskLimit' => 4000,
    'futureLimit' => 8000,
    'nativeMemoryLimit' => 268435456,
    'httpMaxConnections' => 64,
    'httpMaxHostConnections' => 8,
]);
$app->listen('127.0.0.1:8080', [
    'connections' => 1024,
    'bodyBytes' => 1048576,
    'responseBytes' => 8388608,
    'bufferBytes' => 67108864,
    'timeoutMs' => 30000,
    'requestsPerConnection' => 10000,
]);
```

These are defaults shown explicitly, except the deliberately smaller task/future limits. Server options use integers; `timeoutMs` is milliseconds. Limits are per process. `bodyBytes` is not a PHP memory_limit, and the native ledger does not include every Rust/TLS/library allocation. Budget worker count, connection buffers, PHP objects and operating-system limits together.

## Behind a reverse proxy

Bind the application to loopback or a private interface and make the proxy the public endpoint. Configure body size, buffering and timeouts on both sides. For streamed responses disable proxy response buffering on the relevant route if the application requires incremental delivery.

```nginx
location / {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header Connection "";
    proxy_buffering off;
}
```

This is a starting configuration, not universal proxy certification. App does not automatically trust Forwarded/X-Forwarded-* or implement a trusted-proxy policy. Do not use an attacker-controlled forwarded address for authorization. Configure TLS and rate controls at the edge according to the actual deployment.

## Shutdown and application behavior

Run under your normal process manager with a working directory, environment variables and a finite stop grace period. Send graceful termination before forced termination. Keep credentials outside source, and avoid synchronous log/file/HTTP operations in a handler. `Runtime::diagnostics()` and `$app->stats()` expose different scopes: scheduler resources versus server transport counters.

Session APIs, process-global cwd changes and static user state need deliberate treatment. FPM compatibility has not been removed, but it is no longer the primary development track. `App::listen` itself is CLI-only. Do not attempt to start a server listener inside an FPM request.
