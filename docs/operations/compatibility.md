---
title: "Compatibility"
---

Choose a package for your PHP version and platform, then check the features available in that installation with `asyncara --check` or `Runtime::information()`.

## Platforms and packages

| Platform | Package requirements | Availability |
|---|---|---|
| Linux x86_64 (amd64) | glibc 2.38+, PHP 8.3 / 8.4 / 8.5 NTS; versions below | Public packages; [automatic installer](../installation.md) |
| Linux aarch64 (ARM64) | glibc 2.38+, PHP 8.3 / 8.4 / 8.5 NTS; versions below | Public packages; same automatic installer |
| Android / Termux | Bionic, aarch64 or x86_64, PHP 8.5.12 NTS | Preview packages; manual package installation |
| Windows | — | No supported package |
| macOS / FreeBSD | — | No supported prebuilt package |
| 32-bit targets | — | No supported package |

Linux package selection does not depend on the distribution name or release number. Ubuntu, Debian and Kali installations, including GNU/Linux environments inside Android proot, can use the same architecture-specific bundle when PHP and the native library requirements match. The installer checks glibc and runs native loading, task, file and SQLite checks before activating the command. Builds and installation tests run on Ubuntu 24.04 for both architectures; this does not certify every distribution or device.

The PHP library targets PHP 8.3–8.5. A prebuilt extension requires the exact PHP build it was packaged for, including NTS/ZTS, architecture and debug mode. Do not use a Linux/glibc package in native Termux/Bionic. ZTS packages and concurrent PHP interpreter threads are not supported installation options.

## Published PHP versions

The v0.10.0 Linux release provides **146 tested bundles for 73 PHP versions**, covering both x86_64 and aarch64. These are the stable versions listed by PHP on November 14, 2026. Each bundle uses the matching PHP SDK; it does not replace or upgrade your PHP installation.

| PHP branch | Published patch versions |
|---|---|
| 8.3 | `8.3.0`, `8.3.1`, `8.3.2`, `8.3.3`, `8.3.4`, `8.3.6`, `8.3.7`, `8.3.8`, `8.3.9`, `8.3.10`, `8.3.11`, `8.3.12`, `8.3.13`, `8.3.14`, `8.3.15`, `8.3.16`, `8.3.17`, `8.3.19`, `8.3.20`, `8.3.21`, `8.3.22`, `8.3.23`, `8.3.24`, `8.3.25`, `8.3.26`, `8.3.27`, `8.3.28`, `8.3.29`, `8.3.30`, `8.3.31`, `8.3.32`, `8.3.33`, `8.3.35`, `8.3.36` |
| 8.4 | `8.4.1`, `8.4.2`, `8.4.3`, `8.4.4`, `8.4.5`, `8.4.6`, `8.4.7`, `8.4.8`, `8.4.10`, `8.4.11`, `8.4.12`, `8.4.13`, `8.4.14`, `8.4.15`, `8.4.16`, `8.4.17`, `8.4.18`, `8.4.19`, `8.4.20`, `8.4.21`, `8.4.22`, `8.4.23`, `8.4.24`, `8.4.25`, `8.4.26`, `8.4.27` |
| 8.5 | `8.5.0`, `8.5.1`, `8.5.2`, `8.5.3`, `8.5.4`, `8.5.5`, `8.5.6`, `8.5.7`, `8.5.8`, `8.5.9`, `8.5.10`, `8.5.11`, `8.5.12` |

The [download catalog](https://github.com/asyncara/asyncara/releases/download/v0.10.0/linux-packages.json) includes package names, sizes and SHA-256 checksums. The installer selects the correct file automatically. New PHP releases require a separately built and tested matching package; an older binary is never substituted merely because the major/minor version matches.

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
