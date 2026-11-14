<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Asyncara\Async;
$result = Async::run(function (): array {
    $a = Async::spawn(function (): string { Async::sleep(0.01); return 'first'; });
    $b = Async::spawn(function (): string { Async::sleep(0.005); return 'second'; });
    return Async::gather(['a' => $a, 'b' => $b]);
});
echo json_encode($result, JSON_THROW_ON_ERROR), "\n";
