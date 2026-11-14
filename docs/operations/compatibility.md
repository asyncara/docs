---
title: "Compatibility"
---

Choose a package for your PHP version and platform, then check the features available in that installation with `asyncara --check` or `Runtime::information()`.

## Platforms and packages

| Platform | Package requirements | Availability |
|---|---|---|
| Linux | Ubuntu 24.04, x86_64, PHP 8.3.36 / 8.4.27 / 8.5.12 NTS | Public packages; [automatic installer](../installation.md) |
| Linux aarch64 (Ubuntu / Kali, including Android proot) | No matching Linux package in v0.10.0 | Automatic prebuilt installation unavailable |
| Android / Termux | Bionic, aarch64 or x86_64, PHP 8.5.12 NTS | Preview packages; manual package installation |
| Windows | — | No supported package |
| macOS / FreeBSD | — | No supported prebuilt package |
| 32-bit targets | — | No supported package |

The PHP library targets PHP 8.3–8.5. A prebuilt extension requires the exact PHP build it was packaged for, including NTS/ZTS, architecture and debug mode. Do not use a Linux/glibc package in native Termux/Bionic. ZTS packages and concurrent PHP interpreter threads are not supported installation options.

## Features and backends

| Feature | Backend | What to account for |
|---|---|---|
| TCP / UDP | Nonblocking sockets with epoll/poll | Use one read owner per byte stream |
| PostgreSQL | Nonblocking libpq | Configure server authentication and certificate verification |
| MySQL / MariaDB | Connector/C asynchronous API | Verified TLS requires a numeric IP and matching IP SAN |
| Redis | Nonblocking TCP and RESP | Cluster and Sentinel client routing are unavailable |
| HTTP client | curl multi or optional Hyper | Hyper requires a package built with that backend |
| Native Server | HTTP/1.1 with PHP Fiber handlers | CLI only; no HTTP/2 or HTTP/3 listener |
| SQLite / files / DNS | Native workers | Native calls may need to finish before cancellation cleanup completes |
| ProcessPool | Separate PHP processes | Requires process-launch support and the complete worker entry point |

Hyper supports HTTPS HTTP/2 negotiation; HTTP/3 is unavailable. For streaming, upload and proxy details, use the [HTTP guide](../http/client.md). Filesystem operations use pthread workers; io_uring, kqueue and IOCP are unavailable.

## Application integration

Use CLI for scripts and background jobs, and Native Server for a standalone HTTP service. `App::listen()` cannot run inside a PHP-FPM request. PHP-FPM support is experimental; validate your application's request cleanup before choosing that deployment mode.

The PSR-18 HTTP adapter accepts PSR-17 factories. Integrating a framework requires its own application bootstrap and request lifecycle; there is no automatic adapter that runs Laravel, Symfony or WordPress inside Asyncara. Do not nest Asyncara and another library's top-level event loop.

Optional sleep/usleep hooks do not affect ordinary filesystem functions, cURL, PDO, MySQLi or ext-redis. Use Asyncara's explicit APIs for those operations. CPU-bound PHP work can use ProcessPool; userland ThreadPool is unavailable.

Version 0.10.0 is **EXPERIMENTAL**. Exercise the features your application needs before deployment.
