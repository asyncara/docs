---
title: "API reference"
---

Find the classes, method signatures, parameters and return types available in Asyncara 0.10.0. Use the practical guides alongside this reference for complete examples, error handling and resource cleanup.

| Capability | Reference | Practical guide |
|---|---|---|
| Async, Task, Future, cancellation, Context, Trace | [Runtime](runtime.md) | [Tasks](../runtime/tasks.md) |
| Queues, locks, semaphores, streams | [Concurrency](concurrency.md) | [Synchronization](../runtime/synchronization.md) |
| App, server requests/responses | [Server](server.md) | [Routing](../server/routing.md) |
| HttpClient and PSR adapter | [HTTP](http.md) | [Client](../http/client.md) |
| SQL, Redis, connection pools | [Databases](databases.md) | [Transactions](../data/transactions-pools.md) |
| Files, sockets, WebSocket, processes | [I/O](io.md) | [Files](../io/filesystem.md) |
| Error types | [Errors](errors.md) | [Cancellation](../runtime/cancellation.md) |

## Runnable examples

The documentation repository contains complete scripts under [examples](https://github.com/asyncara/docs/tree/main/examples). Run them with `asyncara examples/tasks.php`, or supply a matching extension and set ASYNCARA_AUTOLOAD when using PHP directly. Examples cover Tasks, shared-waiter cancellation, Queue backpressure, Context/streams, SQLite, bounded files, local TCP, ProcessPool and a local App/HTTP pair.

Follow the [runnable examples guide](examples.md) for setup, commands and expected output. Replace `example.net` URLs in service-specific recipes with your own endpoints and configuration.

## Public interfaces and integrations

The actual server namespace is `Asyncara\Server`; the HTTP client namespace is `Asyncara\HTTP`. Their Request/Response models are different. Internal `asyncara_*` functions are transport implementation details. The generated list omits internal classes, destructors and copy-control magic methods; resource policies are covered in the lifecycle guides.

PSR-18 integration is in `Asyncara\HTTP\Psr\Client`. Supply PSR-17 response/stream factories and optionally an HttpClient. `sendRequest` starts a runtime when needed or cooperates with an existing one. It materializes the PSR request body and returns a buffered PSR response; arbitrary third-party stream reads may block. Do not describe this adapter as streaming or as transparent interception of other HTTP libraries.

```php
$factory = new Nyholm\Psr7\Factory\Psr17Factory();
$client = new Asyncara\HTTP\Psr\Client($factory, $factory);
$request = $factory->createRequest('GET', 'https://api.example.net/status');
$response = $client->sendRequest($request);
```

Install compatible PSR dependencies and Nyholm PSR-7 through your application dependency policy. Package contents vary; Android preview bundles do not automatically supply every optional integration dependency.
