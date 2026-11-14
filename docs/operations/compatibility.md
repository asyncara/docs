---
title: "Compatibility and implementation boundaries"
---

The documentation targets the 0.10 API family. Release artifacts and later development source are not interchangeable. Check the manifest of the exact package you installed and inspect runtime capabilities before choosing an optional backend.

| Capability | Execution model | Boundary |
|---|---|---|
| TCP / UDP | Native nonblocking readiness | epoll/poll on supported targets |
| PostgreSQL | Native nonblocking libpq | Linked library/server/TLS configuration matters |
| MySQL / MariaDB | Connector/C async state machine | Independent server tests; verified TLS currently numeric IP |
| Redis | Nonblocking TCP + RESP | No complete Cluster/Sentinel client |
| HTTP curl | Event-driven curl multi | Protocol support depends on build and API path |
| HTTP Hyper | Rust event-driven integration | Optional backend; HTTP/1.1 and verified HTTPS HTTP/2 scope |
| App | Native HTTP/1.1 server + Fiber handlers | CLI; not an HTTP/2 or HTTP/3 server |
| SQLite / files / DNS | Native worker-backed async | Blocking native calls execute outside the loop |
| ProcessPool | Multiprocess parallelism | Requires process-launch support |

## Platforms and PHP

Linux NTS PHP 8.3,8.4 and 8.5 has the main validation history. Published Linux packages cover specific PHP patch versions and x 86_64 tuples, not every distribution/libc ABI. Android 64 preview packages cover aarch 64/x 86_64 with PHP 8.5.12 NTS and a later source checkpoint; they require the matching environment and do not certify every Android app sandbox. The owner's earlier Android tests are distinct from the published artifact matrix.

Windows and 32-bit packaging are deferred. macOS and FreeBSD do not have a verified prebuilt release matrix here. A poll backend does not by itself certify another operating system. Do not force a Linux binary onto a different ABI.

ZTS has limited investigation, not concurrent-interpreter certification. Userland ThreadPool is not implemented. Native workers avoid Zend access; ProcessPool is the supported parallel PHP model. io_uring filesystem, kqueue and IOCP are not implemented backends.

## Runtime modes and integrations

CLI and Native Server are primary modes. PHP-FPM compatibility is retained, but historical FPM reliability failures remain and it is not the current primary deployment-hardening target. Composer integration and PSR-17/PSR-18 adapters exist; that is not certification of every Laravel, Symfony, WordPress, AMPHP, Revolt or ReactPHP lifecycle.

Optional hooks cover sleep/usleep with documented signal limitations. Ordinary filesystem, cURL, PDO, MySQLi and ext-redis calls do not become asynchronous. Use the explicit Asyncara APIs. Short fast gates and example tests do not certify 24-hour endurance. The release remains EXPERIMENTAL.
