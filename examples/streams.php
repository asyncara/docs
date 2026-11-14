<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Asyncara\{Async, Context};
use Asyncara\Stream\{AsyncStream, Emitter};
Async::run(function (): void {
    Context::set('requestId', 'demo');
    $stream = AsyncStream::fromProducer(function (Emitter $emit): void {
        for ($i = 0; $i < 5; $i++) { $emit->emit(Context::get('requestId').':'.$i); Async::sleep(0); }
    }, capacity: 2);
    $stream->consume(fn(string $item) => print($item."\n"));
});
