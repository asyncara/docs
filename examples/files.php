<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Asyncara\Async;
use Asyncara\IO\Files;
Async::run(function (): void {
    $path = sys_get_temp_dir().'/asyncara-docs-'.bin2hex(random_bytes(8)).'.txt';
    try {
        Files::replaceAtomic($path, str_repeat('abc', 30000))->await();
        $stream = Files::readStream($path);
        $bytes = 0;
        try { foreach ($stream as $chunk) $bytes += strlen($chunk); }
        finally { $stream->close(); }
        echo $bytes, "\n";
    } finally {
        Async::shield(function () use ($path): void { if (Files::exists($path)->await()) Files::delete($path)->await(); });
    }
});
