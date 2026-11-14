---
title: "Your first application"
---

Run `asyncara --check` before starting. The examples below assume the launcher supplies the library autoloader. For direct PHP invocation use the explicit extension/autoload setup in [installation](installation.md).

## Two tasks waiting concurrently

Save as `tasks.php` and run `asyncara tasks.php`:

```php
<?php
use Asyncara\Async;
$result = Async::run(function (): array {
    $first = Async::spawn(function (): string { Async::sleep(0.02); return 'first'; });
    $second = Async::spawn(function (): string { Async::sleep(0.01); return 'second'; });
    return Async::gather(['first' => $first, 'second' => $second]);
});
echo json_encode($result, JSON_THROW_ON_ERROR), "\n";
```

The output preserves the input keys even when the second task completes first. `spawn` schedules work, `sleep` suspends its caller, and `gather` waits for values. Creating and immediately awaiting each task inside the same loop would serialize the work. Submit first, then collect, within sensible capacity limits.

## A JSON HTTP server

```php
<?php
use Asyncara\App;
use Asyncara\Server\{Request, Response};
$app = new App();
$app->get('/status', fn() => Response::json(['status' => 'OK']));
$app->get('/hello/{name}', fn(Request $request) => Response::json(['hello' => $request->param('name')]));
$app->post('/data', fn(Request $request) => Response::json(['received' => $request->json()], 201));
$app->listen('127.0.0.1:8080');
```

```bash
asyncara server.php
# In a second terminal:
curl http://127.0.0.1:8080/status
curl http://127.0.0.1:8080/hello/Ajmal
curl -H 'Content-Type: application/json' -d '{"name":"Asyncara"}' http://127.0.0.1:8080/data
```

`listen` owns the top-level runtime: do not wrap it in `Async::run`. The server requires CLI and a Hyper-enabled extension. A missing `asyncara_server_listen` means the build lacks that feature. It serves HTTP/1.1; outbound HTTP/2 client support does not imply an HTTP/2 listener. See [server deployment](server/deployment.md) before running a persistent service.

## Call it asynchronously

```php
<?php
use Asyncara\Async;
use Asyncara\HTTP\HttpClient;
Async::run(function (): void {
    $client = new HttpClient(timeout: 5);
    $response = $client->get('http://127.0.0.1:8080/status')->await();
    if ($response->status !== 200) throw new RuntimeException('HTTP '.$response->status);
    echo $response->json()['status'], "\n";
});
```

A client response exposes `status`, `body`, `headers` and `json()`. A 404 is not automatically a transport exception; inspect status explicitly. Connection, TLS and timeout failures use exceptions.

The [examples directory](https://github.com/asyncara/docs/tree/main/examples) contains complete programs for tasks, shared waiter cancellation, queues, SQLite, files, streams, TCP, processes, the server and its HTTP client. Start with `asyncara examples/tasks.php`. Remote SQL/TLS examples in later guides require your own service configuration; they are not silently treated as executed local examples.
