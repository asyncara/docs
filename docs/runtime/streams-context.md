---
title: "Streams and explicit context"
---

A stream processes results incrementally instead of retaining the complete dataset. `AsyncStream` owns one producer and a bounded queue, and accepts one consumer. When the queue fills, `Emitter::emit` suspends the producer. That is real backpressure, not chunking an already buffered array.

```php
use Asyncara\Async;
use Asyncara\Stream\{AsyncStream, Emitter};
Async::run(function (): void {
    $stream = AsyncStream::fromProducer(function (Emitter $emit): void {
        for ($i = 0; $i < 100; $i++) { $emit->emit(['id' => $i]); Async::sleep(0); }
    }, capacity: 4);
    $stream->consume(fn(array $row) => print($row['id']."\n"));
});
```

`consume` closes the stream and joins producer cleanup even if the consumer throws. With foreach, use finally and explicit `close`, especially after an early break. A stream cannot be consumed twice. Do not rely on destructors to suspend and join native work; explicit closure inside the runtime is the lifecycle boundary.

## Compose bounded streams

```php
use Asyncara\Async;
use Asyncara\IO\Files;
Async::run(function (): void {
    $source = Files::readStream('/srv/data/input.bin');
    try { echo Files::writeStream('/srv/data/output.bin', $source)->await(); }
    finally { $source->close(); }
});
```

Default file chunks are 64 KiB with a small producer queue. Total process memory is larger: workers, producer, consumer and PHP values can each retain data. Collecting every chunk into an array defeats the streaming contract.

## Context is not a replacement for process isolation

```php
use Asyncara\{Async, Context};
Async::run(function (): void {
    Context::set('requestId', 'r-123');
    $child = Async::spawn(fn() => Context::get('requestId'));
    echo $child->await();
    Context::isolated(function (): void { echo Context::get('requestId'); }, ['requestId' => 'isolated']);
});
```

A child receives a shallow copy of the explicit context map at spawn time; object values remain shared. `inheritContext:false` disables inheritance. `isolated` restores the previous map in finally. Globals, static properties, sessions and extension configuration are not isolated by this API.

Native Server clears the task Context between requests on a reused connection, but the interpreter remains long-lived. Do not store user identity in static state expecting request teardown to erase it. Use Context for bounded request metadata and explicit ownership for resource objects.
