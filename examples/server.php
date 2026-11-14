<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Asyncara\{App, Async};
use Asyncara\Server\{Request, Response};
$app = new App();
$app->get('/status', fn() => Response::json(['status' => 'OK']));
$app->get('/work/{id}', function (Request $request): Response {
    $delay = filter_var($request->query('delay_ms', '0'), FILTER_VALIDATE_INT);
    if ($delay === false || $delay < 0 || $delay > 1000) return Response::text('Invalid delay', 400);
    Async::sleep($delay / 1000);
    return Response::json(['id' => $request->param('id'), 'delay_ms' => $delay]);
});
$app->post('/data', fn(Request $request) => Response::json(['received' => $request->json()], 201));
$app->listen(getenv('ASYNCARA_LISTEN') ?: '127.0.0.1:8080', onReady: static function (string $address): void { echo "LISTEN $address\n"; flush(); });
