---
title: "Runnable examples"
---

The [examples directory](https://github.com/asyncara/docs/tree/main/examples) contains complete PHP scripts you can run with an installed Asyncara launcher. Download the documentation repository, open its directory in a terminal, and start with:

```bash
asyncara examples/tasks.php
```

The task example prints `{"a":"first","b":"second"}`. Both Tasks start before either result is collected; the shorter wait can finish first without changing the result keys.

## Choose an example

| File | What it demonstrates | Expected result |
|---|---|---|
| `tasks.php` | Concurrent tasks and keyed results | JSON with first/second values |
| `shared-waiter.php` | Cancelling one waiter without cancelling its shared operation | `waiter cancelled`, then `kept` |
| `queue.php` | A bounded producer/consumer queue | `55` |
| `streams.php` | Context values in a bounded stream | `demo:0` through `demo:4` |
| `sqlite.php` | Parameter binding, transactions and savepoints | One saved note |
| `files.php` | Atomic replacement and bounded reads | `90000` bytes |
| `network.php` | A local TCP request and reply | `PING` |
| `processes.php` with `jobs.php` | Parallel registered jobs | Two SHA-256 values |

SQLite uses an in-memory database. The file example creates and removes a temporary file, and the TCP example binds a local ephemeral port. ProcessPool needs the complete runtime installation described in the [process guide](../io/processes.md).

## Run the server and HTTP client together

In one terminal:

```bash
asyncara examples/server.php
```

In a second terminal:

```bash
asyncara examples/http.php
```

The client makes four requests to the local server. Each response includes an ID and `delay_ms: 10`; completion order may vary. Stop the server with Ctrl+C when finished.

For a different port, use matching settings in the two terminals:

```bash
# Server terminal
ASYNCARA_LISTEN=127.0.0.1:8081 asyncara examples/server.php
```

```bash
# Client terminal
ASYNCARA_DEMO_URL=http://127.0.0.1:8081 asyncara examples/http.php
```

Examples using `example.net` in other guides need your service URL, credentials and certificates. Keep those values in application configuration, not in a copied example committed with secrets.
