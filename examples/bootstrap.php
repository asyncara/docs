<?php
declare(strict_types=1);
if (!class_exists(Asyncara\Async::class)) {
    $autoload = getenv('ASYNCARA_AUTOLOAD') ?: __DIR__.'/../vendor/autoload.php';
    if (is_file($autoload)) require $autoload;
}
if (!extension_loaded('asyncara') || !class_exists(Asyncara\Async::class)) {
    throw new RuntimeException('Run with asyncara examples/FILE.php, or load ext-asyncara and set ASYNCARA_AUTOLOAD.');
}
