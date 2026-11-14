---
title: "Security and trust boundaries"
---

Asyncara is an experimental runtime, not an application security framework. Use explicit trust boundaries even when the underlying transport is asynchronous.

## Network inputs

Do not allow an untrusted request to choose arbitrary upstream URLs, proxy settings, CA paths or local file paths. Validate scheme, destination, port and redirects to prevent SSRF; DNS and redirect changes can cross a boundary after initial validation. Keep TLS verification enabled. Client certificates are credentials and may be sent to an authenticated server, so scope clients to intended origins.

App handlers must validate decoded JSON shapes, body sizes, route parameters and query values. Origin checks are not authentication. Configure authentication, authorization, CORS, CSRF/session policy and rate limits appropriate to your application. Restrict the listener to loopback behind a trusted proxy unless direct exposure is intentional.

## Local files and process execution

Use trusted private directories for uploads, staging and diagnostics. A path validation check is not a complete defense against concurrent symlink replacement by another local actor. Do not build shell commands, SQL, process-bootstrap paths or job registries from untrusted input. Bind SQL parameters and use a fixed ProcessPool registry.

Keep secrets in a deployment secret store or restricted configuration. Error responses must not expose stack traces, SQL values, DSNs or Authorization headers. Redact private paths and deployment metadata from logs before sharing them. Diagnostic events omit payloads, but deployment metadata can still be sensitive.

## Memory, shutdown and dependencies

Apply process/container limits alongside Asyncara budgets. Third-party allocators, kernel buffers and children are not fully covered by the native ledger. An abrupt kill can leave partial files or a remote write with unknown outcome. Design retries using idempotency and atomic publication where appropriate.

Update PHP and native libraries according to their security advisories. Use a package matching your PHP ABI and thread-safety mode; never force-load an incompatible extension. Download checksums alongside release artifacts and verify the expected release source. A checksum detects corruption but is not an independent publisher signature.

PolyForm Strict limits use, modification and distribution; see [license](license.md). Report a suspected vulnerability privately to the project owner through the organization's available security contact mechanisms. Do not put exploitable secrets in a public issue.
