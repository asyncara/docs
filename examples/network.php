<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Asyncara\Async;
use Asyncara\IO\{TcpServer, TcpStream};
Async::run(function (): void {
    $server = new TcpServer('127.0.0.1', 0);
    $port = (int)substr(strrchr($server->address(), ':'), 1);
    $peer = Async::spawn(function () use ($server): void {
        $connection = $server->accept();
        try { $connection->write(strtoupper($connection->readExactly(4))); }
        finally { $connection->close(); }
    });
    try {
        $client = TcpStream::connect('127.0.0.1', $port)->await();
        try { $client->write('ping'); echo $client->readExactly(4), "\n"; }
        finally { $client->close(); }
        $peer->await();
    } finally { $server->close(); }
});
