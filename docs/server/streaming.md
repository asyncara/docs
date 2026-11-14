---
title: "Streaming requests and responses"
---

Streaming is useful for large exports, downloads and request bodies that should not be collected before processing. It also changes failure handling: once response headers have been sent, an exception cannot become a fresh JSON 500 response.

## Stream a file with backpressure

```php
use Asyncara\IO\Files;
use Asyncara\Server\Response;
$app->get('/export', function () {
    $body = Files::readStream('/srv/data/export.csv');
    return Response::stream($body, headers: [
        'content-type' => 'text/csv; charset=utf-8',
        'content-disposition' => 'attachment; filename="export.csv"',
    ]);
});
```

The file is read through native workers. Response chunks are requested as the transport becomes ready; a slow consumer does not require buffering the entire file. A response chunk must be a string no larger than 64 KiB. The default Files stream chunk fits this bound. A StreamResponse is single-use and owns its producer; App closes it on completion, failure, HEAD handling or disconnect.

For generated NDJSON, use a lazy generator and yield one bounded line at a time. Do not materialize all rows first or allow one JSON line to exceed the chunk limit. This is also the basis for SSE framing (`data: ...\n\n`), but heartbeat, reconnection and application delivery guarantees remain your responsibility.

## Receive a streamed body

Enable `streamRequests` when listening:

```php
use Asyncara\Async;
use Asyncara\IO\Files;
use Asyncara\Server\{Request, Response};
$app->post('/upload', function (Request $request): Response {
    $path = '/srv/uploads/'.bin2hex(random_bytes(16));
    $complete = false;
    try {
        $bytes = Async::await(Files::writeStream($path, $request->stream()));
        $complete = true;
        return Response::json(['bytes' => $bytes], 201);
    } finally {
        if (!$complete) Async::shield(function () use ($path): void {
            if (Files::exists($path)->await()) Files::delete($path)->await();
        });
    }
});
$app->listen('127.0.0.1:8080', ['streamRequests' => true, 'bodyBytes' => 8388608]);
```

Use a private directory and an application-generated filename. This example removes partial uploads; it does not implement multipart parsing or atomic publication to a public name. In default buffered mode, `stream()` yields the already-buffered body. In native streaming mode it is a handler-scoped single consumer. Calling `json()` collects the body before decoding, so use it only for bounded JSON requests.

## Disconnect and cleanup

A disconnected client cancels its handler. Use try/finally and bounded shields to release resources. App closes any remaining request input after dispatch. Do not pass a request body stream to background work that outlives the request. Error after response streaming begins aborts that transfer rather than appending a different response. Configure connection, buffer and timeout limits for the number of concurrent streams your service can handle.
