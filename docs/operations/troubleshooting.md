---
title: "Troubleshooting by symptom"
---

Collect `php -v`, `php --ini`, `asyncara --check` and `Runtime::information()` first. A CLI wrapper may use a different PHP executable/configuration from a web server or your shell alias.

| Symptom | Check | Corrective action |
|---|---|---|
| Extension cannot load | PHP module API, architecture, NTS/ZTS, missing shared libraries | Install the matching artifact; do not rename a mismatched binary |
| Class not found | Runtime autoloader versus extension | Use the asyncara launcher or load Composer's autoloader and the extension |
| No matching prebuilt package | Published version/platform/PHP matrix | Choose an available supported tuple or build explicitly; do not expect silent fallback |
| TLS verification failure | CA, hostname/IP SAN, time, certificate chain | Correct trust/identity; keep verification enabled |
| Timers stall | CPU loops, ordinary blocking PHP APIs, synchronous logging | Use explicit async APIs and move CPU work to appropriate processes |
| Deadlock inside transaction | Child Task waiting for its parent's connection | Keep transaction queries in the owning Task |
| Pool capacity exceeded | Escaped leases, long transactions, connection budget | Release scopes correctly before increasing limits |
| Partial output file | Failed direct download/write | Use atomic publication if consumers must never see partial output |
| Cancelled wait but remote work continues | Waiter versus operation ownership | Apply the documented owner cancellation policy; do not claim remote rollback |

## Reduce failures safely

Reproduce against a local deterministic endpoint and the same PHP/backend combination. Keep the failing input and error output. Add diagnostics in a separate run so a passing instrumented run is not confused with ordinary acceptance. Never fix a failing timer-progress test by reducing its tick requirement.

If a process remains alive, verify actual request completion and timer progress; liveness is not reliability. Compare resource counts after cleanup boundaries and warmup. A memory plateau does not prove every ownership path is leak-free, and a dependency allocation report is not automatically an Asyncara allocation defect.

## Getting help

Include a minimal complete script, expected/actual result, exact package version, OS/architecture, PHP build mode and backend. State whether the endpoint is local, whether TLS/proxies are involved, and whether the failure survives disabling optional diagnostics. Redact credentials and private data. Do not paste an entire production environment or a private source archive into a public issue.
