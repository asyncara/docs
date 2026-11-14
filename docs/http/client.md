---
title: "HTTP requests with curl and Hyper"
---

`Asyncara\HTTP\HttpClient` provides the same explicit API for the default curl multi transport and optional Rust Hyper transport. Both integrate network readiness with Asyncara; file and DNS preparation use workers where required. No PHP curl hook is needed.

## Submit concurrently, inspect status explicitly

```php
use Asyncara\Async;
use Asyncara\HTTP\HttpClient;
Async::run(function (): void {
    $http = new HttpClient(concurrency: 8, timeout: 10, connectTimeout: 3);
    $pending = [
        'status' => $http->get('http://127.0.0.1:8080/status'),
        'data' => $http->post('http://127.0.0.1:8080/data', json: ['name' => 'Asyncara']),
    ];
    foreach (Async::gather($pending) as $name => $response) {
        if ($response->status >= 400) throw new RuntimeException($name.' returned '.$response->status);
        echo $response->body, "\n";
    }
});
```

Headers are a list of complete lines, for example `['Accept: application/json']`. JSON, form and raw body are mutually exclusive `post` arguments. Use `request('PATCH', $url, $json, ['Content-Type: application/json'])` for other methods. Responses expose status, body, lowercased header arrays and `json()`; JSON decoding can fail independently of transport success.

A 404/500 is a normal HTTP response. Transport failures throw. Do not automatically retry POST or transaction-like operations after a timeout: the remote service may already have acted. Client concurrency 1..256 bounds its admitted transfers; runtime connection limits independently constrain sockets and hosts.

## Select and verify the transport

```bash
ASYNCARA_HTTP_BACKEND=hyper-prototype asyncara client.php
ASYNCARA_BACKEND=poll asyncara client.php
```

`hyper-prototype` is the actual compatibility selector despite the feature's later development. It requires a Hyper-enabled build. Verify `Runtime::information()->httpBackend` instead of assuming selection succeeded. `ASYNCARA_BACKEND` chooses epoll/poll readiness, not the HTTP protocol. Select environment options before the process/runtime starts. There is no silent promise that missing Hyper falls back safely to curl.

Plain HTTP uses HTTP/1.1. Hyper negotiates HTTP/2 over verified TLS ALPN when the peer supports it; h2c and HTTP/3 are unavailable. curl buffered HTTP/2 depends on the compiled libcurl and negotiation. curl response streaming deliberately uses HTTP/1.1. A server's outbound HTTP/2 client does not make Native Server an HTTP/2 listener.

## Redirects and lifecycle

Following redirects is opt-in: `get($url, followRedirects: true)`. Hyper/identity-owned chains bound hops, reject unsafe downgrades and remove standard sensitive headers across origins. Custom secret headers still require application policy. mTLS redirects cannot present your identity to a new origin. Iterable uploads are not implicitly replayed.

Buffered responses are bounded, currently 16 MiB for the body. Choose [streaming](streaming.md) for larger data. Client permits remain owned until transfer cleanup; abandoning a Future does not undo submission. Use `Future::await` for a shared wait and `Async::await` only when your scope owns cancellation, as explained in [cancellation](../runtime/cancellation.md).
