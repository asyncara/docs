---
title: "What is Asyncara?"
---

Asyncara is a native extension and PHP library for applications that need to wait on many I/O operations without blocking every other task. You write explicit APIs, suspend the calling Fiber at an asynchronous wait, and resume when the operation can make progress. This documentation is for **application developers**: each guide explains setup, results, ownership, failure handling and practical limits.

Start with [installation](installation.md) and [your first application](quickstart.md). Build a service with [Native Server](server/routing.md), call upstream services with the [HTTP client](http/client.md), or process bounded batches with the [pipeline recipe](recipes/pipeline.md). The [API reference](reference/overview.md) lists actual source signatures rather than proposed interfaces.

## Explicit APIs come first

Use `HttpClient`, `Files`, Asyncara database connections and TCP/UDP objects. Loading the extension does not make PDO, `curl_exec()` or `file_get_contents()` nonblocking. Timer hooks are optional compatibility tools, not a dependency of the core APIs.

| Capability | Execution model |
|---|---|
| TCP, UDP, Redis | Nonblocking sockets and epoll/poll readiness |
| Default HTTP transport | Event-driven libcurl multi integration |
| Optional Hyper HTTP transport | Rust Hyper/rustls driven by Asyncara readiness, not a Tokio thread pool |
| PostgreSQL | Nonblocking libpq |
| MySQL/MariaDB | Connector/C asynchronous start/continue state machine |
| Filesystem, SQLite, DNS | Bounded native workers outside the Event Loop thread |
| ProcessPool | Separate PHP interpreter processes |

A Native Asyncara API does **not** mean every operation is kernel-native asynchronous I/O. Regular files use workers; io_uring is not an available filesystem backend.

## Concurrency and parallelism

One interpreter executes one PHP task at a time. Other tasks progress when that task suspends. A long CPU loop, blocking extension call or synchronous third-party callback can therefore delay the whole loop. Use [ProcessPool](io/processes.md) for independent CPU work. More Tasks do not automatically mean more cores.

## Version and support scope

These guides describe 0.10.0 APIs and explicitly identify later Android preview packages. The release is **EXPERIMENTAL**. A tested local protocol path is not certification of every server, proxy, device or deployment. See [compatibility](operations/compatibility.md) before selecting a platform.

Namespace spelling matters with PSR-4 on case-sensitive systems. The client is `Asyncara\HTTP\HttpClient`; server responses are `Asyncara\Server\Response`, a different class from `Asyncara\HTTP\Response`. Examples consistently use the source's real names.
