---
title: "Requests, JSON and responses"
---

Server handlers receive `Asyncara\Server\Request`. Its readonly fields include `method`, `target`, `path` and buffered `body`. Use `param`, `query`, `header` and `json` to access application input. Query values remain strings: bounds and type validation belong in your handler.

## Validate input at the boundary

```php
use Asyncara\Server\{Request, Response};
$app->post('/notes', function (Request $request): Response {
    if (!str_starts_with(strtolower($request->header('content-type', '')), 'application/json')) {
        return Response::text('Expected application/json', 415);
    }
    $data = $request->json();
    if (!is_array($data) || !isset($data['title']) || !is_string($data['title']) ||
        $data['title'] === '' || strlen($data['title']) > 200) {
        return Response::json(['error' => 'title must contain 1..200 bytes'], 422);
    }
    return Response::json(['title' => $data['title']], 201);
});
```

`json()` uses PHP's native ext-json parser and throws `JsonException` for malformed JSON; App turns that into 400. Valid JSON can still be a scalar or null, so validate the decoded shape. It does not automatically enforce Content-Type or a schema.

`query('key', $default)` uses URL-decoded key/value pairs, with the last duplicate key retained. It does not implement PHP-style nested query arrays. `header` is case-insensitive and joins repeated values with commas. Do not assume it is a lossless raw-header API for security-sensitive duplicate semantics.

## Choosing a response

`Response::json($value, $status, $headers)` encodes with exceptions and sets JSON Content-Type. `Response::text` supplies text/plain. For HTML or binary content construct the server Response directly:

```php
$app->get('/page', fn() => new Response(
    '<!doctype html><title>Asyncara</title><p>Hello</p>',
    200,
    ['content-type' => 'text/html; charset=utf-8'],
));
$app->delete('/notes/{id}', fn() => new Response('', 204));
```

Statuses must be 200..599; 204, 205 and 304 require an empty body. The transport owns framing: do not add conflicting Content-Length/Transfer-Encoding values. The server Response uses an associative header array; the client HttpClient uses a list of complete header lines. They are intentionally different APIs.

Unexpected handler exceptions produce a bounded 500 response without returning the exception trace. Catch domain errors explicitly to return a stable application error format. Log only safe context: request URLs, body values and exception arguments can contain credentials. If streaming already began, the status cannot be replaced; see [streaming](streaming.md).
