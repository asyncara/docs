---
title: "Troubleshooting by symptom"
---

Start with `asyncara --check`, `php -v` and `php --ini`. Inspect `Runtime::information()` from the affected application as well. The launcher may use a different PHP executable or configuration from your shell or web server.

| Symptom | What to check | What to do |
|---|---|---|
| Extension cannot load | PHP version and module API, architecture, NTS/ZTS and shared libraries | Install a matching package and its dependencies; renaming an incompatible binary cannot make it load |
| Class not found | Library autoloader as well as the native extension | Use the `asyncara` launcher, or explicitly load both the extension and library autoloader |
| No matching package | The [platform matrix](compatibility.md) | Choose a listed package; other configurations need a custom build |
| Download returns 404 | [Download availability](../installation.md) and the exact release filename | Use a published matching package when available; do not run an empty or failed download |
| TLS verification fails | CA file, certificate chain, hostname or IP SAN, system time | Correct the trust and identity configuration while keeping verification enabled |
| Timers or unrelated requests stall | CPU loops, blocking PHP APIs or synchronous logging | Use Asyncara I/O APIs and move independent CPU work to [ProcessPool](../io/processes.md) |
| Transaction never finishes | A child Task waiting for a connection held by its waiting parent | Keep the transaction's queries in the owning Task |
| Pool capacity is exhausted | Leases held beyond their scope and long transactions | Release connections with `withConnection` or `finally`; size the pool within the database connection budget |
| Output file is partial | Interrupted direct download or write | Use atomic replacement when readers must see only complete files |
| Remote work continues after a wait is cancelled | The distinction between waiter and operation ownership | Follow the [cancellation contract](../runtime/cancellation.md); local cancellation cannot undo a remote write |

## Isolate a failure

Reduce the affected application to one operation and its cleanup. Keep the same PHP version, backend, timeout and input. For network problems, compare a local endpoint with the affected service; this helps separate application behavior from DNS, TLS, proxy and upstream failures.

Preserve the complete exception class, message and stack trace in a restricted log. Enable [bounded tracing](diagnostics.md) for a separate reproduction if timing or resource ownership is involved. Check completed requests and timer progress as well as whether the process is alive. Compare memory and descriptor counts after work has finished, rather than during different levels of concurrency.

## Ask for help

Include a minimal complete script, expected and actual output, package version, operating system, architecture, PHP build mode and backend. State whether TLS, a proxy or optional tracing is involved. Replace credentials, tokens, private URLs and application data before sharing the example. Do not attach your full production configuration.
