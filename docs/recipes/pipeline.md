---
title: "Recipe: bounded file processing"
---

A bounded producer/consumer pipeline decouples disk reads from processing without retaining an entire input. The example processes chunks rather than lines; text formats need an incremental decoder for records spanning chunk boundaries.

```php
use Asyncara\Async;
use Asyncara\IO\Files;
Async::run(function () {
    $source = Files::readStream('/srv/data/input.bin');
    $output = null;
    try {
        $output = Files::open('/srv/data/output.bin', 'wb')->await();
        foreach ($source as $chunk) {
            $output->write(strtoupper($chunk))->await();
        }
        $output->sync()->await();
    } finally {
        $source->close();
        if ($output !== null) Async::shield(fn() => $output->close()->await());
    }
});
```

The source queue and chunk size bound read-ahead. Awaiting each write applies backpressure to the producer. The transformation here is small; CPU-intensive compression or parsing still runs on the interpreter thread and should be split into suitable ProcessPool jobs when safe. Do not submit millions of chunks into an unbounded job list.

This output is intentionally not atomic: a failure can leave a partial file. For publish-on-success workflows, use `File::stage($destination)->await()`, write through the staged handle, then `publish(durable: true)->await()` and close it in finally. Staging must share the destination filesystem for rename semantics. Do not confuse successful rename with crash durability when directory synchronization fails.

When processing untrusted input, bound the longest record as well as the chunk queue. A single unterminated line can defeat an otherwise bounded chunk design if your parser concatenates indefinitely. Cancellation stops further consumption and joins the producer, but cannot undo disk writes already executed.
