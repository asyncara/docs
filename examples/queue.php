<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Asyncara\Async;
use Asyncara\Concurrency\Queue;
use Asyncara\Exception\QueueClosedException;
Async::run(function (): void {
    $queue = new Queue(2);
    $producer = Async::spawn(function () use ($queue): void {
        try { for ($n = 1; $n <= 10; $n++) $queue->put($n); }
        finally { $queue->close(); }
    });
    $consumer = Async::spawn(function () use ($queue): int {
        $sum = 0;
        while (true) {
            try { $item = $queue->get(); } catch (QueueClosedException) { break; }
            try { $sum += $item; Async::sleep(0); }
            finally { $queue->taskDone(); }
        }
        return $sum;
    });
    $producer->await();
    echo $consumer->await(), "\n";
    $queue->join();
});
