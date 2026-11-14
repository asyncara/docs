<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Asyncara\Async;
use Asyncara\HTTP\HttpClient;
$base = rtrim(getenv('ASYNCARA_DEMO_URL') ?: 'http://127.0.0.1:8080', '/');
Async::run(function () use ($base): void {
    $http = new HttpClient(concurrency: 4, timeout: 5);
    $requests = [];
    for ($i = 1; $i <= 4; $i++) $requests[$i] = $http->get($base.'/work/'.$i.'?delay_ms=10');
    foreach (Async::asCompleted($requests) as $id => $future) {
        $response = $future->await();
        if ($response->status !== 200) throw new RuntimeException('HTTP '.$response->status);
        echo json_encode(['id' => $id, 'body' => $response->json()], JSON_THROW_ON_ERROR), "\n";
    }
});
