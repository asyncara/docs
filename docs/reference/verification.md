---
title: "Example verification"
---

The documentation examples were executed on Linux x86_64 with PHP 8.5.12 NTS, using the published0.10.0 module. Both epoll and poll passed local Task, waiter-cancellation, Queue, SQLite, file, stream and TCP examples. The local Native Server handled status, JSON, validation and404 checks; HTTP examples passed with curl multi and Hyper on both readiness backends.

105 PHP snippets and12 complete PHP files passed syntax checks. The ProcessPool example passed with the complete release source runtime. It was deliberately not executed against the incomplete binary-bundle runtime because that bundle omits its worker entry point; see [ProcessPool](../io/processes.md).

Download the sanitized [bundle result](pathname:///docs/evidence/examples-bundle-r1.json) and [complete-runtime result](pathname:///docs/evidence/examples-source-r1.json). Each records the module checksum and actual checks. These are developer-example tests, not full protocol certification, performance comparisons or endurance tests. Remote PostgreSQL/MySQL/Redis and example.net TLS/proxy recipes require your own service configuration and were not executed as part of this documentation validation.
