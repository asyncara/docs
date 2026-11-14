---
title: "HTTP client"
---

Classes and method signatures for Asyncara 0.10.0. Read the [usage guide](../http/client.md) for examples, error handling and resource cleanup. Inherited methods are listed under their declaring class.

## `Asyncara\HTTP\HttpClient`

```text
__construct(int $concurrency = 32, float $timeout = 30.0, ?string $proxy = NULL, ?string $caFile = NULL, float $connectTimeout = 10.0, ?string $proxyCaFile = NULL, ?array $proxyCredentials = NULL, ?string $clientCertFile = NULL, ?string $clientKeyFile = NULL)
get(string $url, array $headers = [], bool $followRedirects = false): Asyncara\Future
getStream(string $url, array $headers = [], bool $followRedirects = false): Asyncara\HTTP\ResponseStream
download(string $url, string $path, array $headers = [], bool $followRedirects = false, bool $atomic = false, bool $durable = false, bool $failOnHttpError = false): Asyncara\Future
upload(string $url, string $path, string $method = 'PUT', array $headers = []): Asyncara\Future
requestStream(string $method, string $url, iterable $body, ?int $contentLength = NULL, array $headers = []): Asyncara\Future
multipart(string $url, array $fields, string $fileField, string $path, ?string $filename = NULL, string $contentType = 'application/octet-stream', array $headers = []): Asyncara\Future
multipartFiles(string $url, array $fields, array $files, array $headers = []): Asyncara\Future
post(string $url, mixed $json = NULL, ?array $form = NULL, ?string $body = NULL, array $headers = []): Asyncara\Future
request(string $method, string $url, ?string $body = NULL, array $headers = [], bool $followRedirects = false): Asyncara\Future
```

## `Asyncara\HTTP\Response`

```text
readonly array $headers;
readonly int $status;
readonly string $body;
__construct(int $status, string $body, string $rawHeaders)
json(bool $associative = true): mixed
```

## `Asyncara\HTTP\ResponseHead`

```text
readonly array $headers;
readonly int $status;
__construct(int $status, string $rawHeaders)
```

## `Asyncara\HTTP\ResponseStream`

```text
headers(): Asyncara\Future
getIterator(): Traversable
consume(callable $consumer): void
close(): void
```

## `Asyncara\HTTP\Psr\Client`

```text
__construct(Psr\Http\Message\ResponseFactoryInterface $responses, Psr\Http\Message\StreamFactoryInterface $streams, Asyncara\HTTP\HttpClient $http = new Asyncara\HTTP\HttpClient())
sendRequest(Psr\Http\Message\RequestInterface $request): Psr\Http\Message\ResponseInterface
```
