---
title: "Downloads, uploads and multipart"
---

Streaming keeps body retention bounded and lets a slow consumer apply backpressure. It does not make a blocking application generator asynchronous, and it does not limit memory that you deliberately retain after reading chunks.

## Consume a response safely

```php
$stream = $http->getStream('https://service.example/export');
try {
    $head = $stream->headers()->await();
    if ($head->status !== 200) throw new RuntimeException('Unexpected status');
    foreach ($stream as $chunk) {
        // Process the chunk here; do not accumulate the entire export.
        echo strlen($chunk), "\n";
    }
} finally { $stream->close(); }
```

The native body slot is 64 KiB and the PHP stream queue holds two bounded chunks, with additional transient producer/consumer storage. Headers have a separate 64 KiB bound. Dependency allocations are not completely covered by the native ledger. Streaming requests identity encoding; if the peer sends encoded content anyway, chunks remain encoded. Inspect Content-Encoding instead of treating them as decoded text. Transfer framing is handled by the transport.

## Atomic downloads

```php
$bytes = $http->download(
    'https://service.example/archive', '/srv/data/archive.bin',
    atomic: true, durable: true, failOnHttpError: true,
)->await();
```

Default downloads can truncate the destination and leave partial output on failure. Atomic mode stages in the destination directory and publishes a complete body. `failOnHttpError` rejects non-2xx before publishing. Durability additionally synchronizes the file and parent directory. A directory-sync failure after rename can report `FilesystemException::committed === true`: visibility has already changed. Cancellation cannot reverse it. Use trusted destination directories and do not assume replacement preserves all old metadata.

## Files, iterables and multipart uploads

```php
$reply = $http->upload('https://service.example/file', '/srv/data/input.bin')->await();
$reply = $http->requestStream('PUT', $url, (function (): Generator {
    yield 'first';
    yield 'second';
})(), contentLength: 11)->await();
$reply = $http->multipartFiles($url, ['description' => 'report'], [
    ['field' => 'files', 'path' => '/srv/data/a.txt', 'filename' => 'a.txt', 'contentType' => 'text/plain'],
    ['field' => 'files', 'path' => '/srv/data/b.bin'],
])->await();
```

`upload` defaults to PUT and uses worker-backed file reads. Iterable chunks must be strings no larger than 64 KiB. A known contentLength must match bytes produced while the request remains active; unknown length uses the selected protocol's streaming framing. An early server response can stop the producer before EOF. HEAD is bodyless: nonempty bodies and upload-style HEAD calls are rejected.

`multipart` is the single-file convenience API. `multipartFiles` accepts 1..16 file descriptors with `field`, `path`, optional `filename` and `contentType`; repeated field names are permitted. Up to 64 scalar string fields are supported, bounded individually and in aggregate. Do not supply your own multipart boundary header. Nested MIME, automatic redirect replay and resumable upload sessions are not provided.

A caller-created ReadableStream remains your responsibility until the upload producer owns it. Close it in caller finally as well. Transfer cancellation joins owned work but cannot retract bytes already delivered to the peer.
