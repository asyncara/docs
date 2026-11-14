<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Asyncara\{Async, CancellationSource};
use Asyncara\Concurrency\Event;
use Asyncara\Exception\CancelledException;
Async::run(function (): void {
    $ready = new Event();
    $owner = Async::spawn(function () use ($ready): string { $ready->wait(); return 'kept'; });
    $source = new CancellationSource();
    $waiter = Async::spawn(fn() => $owner->await($source->token));
    Async::sleep(0);
    $source->cancel();
    try { $waiter->await(); } catch (CancelledException) { echo "waiter cancelled\n"; }
    if ($owner->isCompleted()) throw new RuntimeException('The owner must still be pending');
    $ready->set();
    echo $owner->await(), "\n";
});
