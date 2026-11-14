---
title: "Recipe: concurrent upstream aggregation"
---

Start independent requests together, then collect them with explicit ownership and error policy. This recipe uses fixed configured origins: accepting arbitrary user-supplied URLs would introduce SSRF risks.

```php
use Asyncara\Async;
use Asyncara\HTTP\HttpClient;
use Asyncara\Server\Response;
$client = new HttpClient(concurrency: 16, timeout: 5, connectTimeout: 2);
$app->get('/summary', function () use ($client) {
    $jobs = [
        'catalog' => $client->get('https://catalog.example.net/summary'),
        'stock' => $client->get('https://stock.example.net/summary'),
    ];
    try {
        $responses = Async::gather($jobs);
        $result = [];
        foreach ($responses as $name => $response) {
            if ($response->status !== 200) return Response::json(['error' => 'upstream_status'], 502);
            $result[$name] = $response->json();
        }
        return Response::json($result);
    } finally {
        foreach ($jobs as $job) if (!$job->isCompleted()) $job->cancel();
        Async::shield(function () use ($jobs) {
            foreach ($jobs as $job) { try { $job->await(); } catch (Throwable) {} }
        });
    }
});
```

These example hosts must be replaced with real configured services. `gather` preserves keys but does not cancel siblings on its own. The finally block requests cancellation of operations this handler owns and joins them, even if JSON decoding or one upstream fails. A shared request owned by another component would require a different cancellation policy.

A transport timeout raises an exception; an HTTP 500 is a valid response and needs application classification. Do not retry a POST just because a timeout occurred. A partial-results endpoint can collect errors per upstream instead, but document that response contract explicitly.

For hundreds of endpoints, bound both the submitted Task count and HttpClient concurrency. A semaphore limits active work, but allocating an unlimited list of pending Futures still consumes memory. Batch or use a bounded producer queue. Run `examples/server.php` and `examples/http.php` to exercise the same concurrent pattern against a deterministic local service.
