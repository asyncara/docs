<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Asyncara\Async;
use Asyncara\Pool\ProcessPool;
Async::run(function (): void {
    $pool = new ProcessPool(2, __DIR__.'/jobs.php');
    try { echo json_encode(Async::gather([$pool->submit('digest', ['one']), $pool->submit('digest', ['two'])]), JSON_THROW_ON_ERROR), "\n"; }
    finally { $pool->close(); }
});
