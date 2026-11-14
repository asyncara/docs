<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Asyncara\Async;
use Asyncara\SQLite\Connection;
Async::run(function (): void {
    $db = Connection::open(':memory:')->await();
    try {
        $db->query('CREATE TABLE notes(id INTEGER PRIMARY KEY, title TEXT)')->await();
        $db->transaction(function (Connection $tx): void {
            $tx->prepare('INSERT INTO notes(title) VALUES (?)')->execute(['hello'])->await();
            $tx->savepoint('draft')->await();
            $tx->query('INSERT INTO notes(title) VALUES (?)', ['discard'])->await();
            $tx->rollbackTo('draft')->await();
            $tx->releaseSavepoint('draft')->await();
        })->await();
        echo json_encode($db->query('SELECT id,title FROM notes')->await(), JSON_THROW_ON_ERROR), "\n";
    } finally { Async::shield(fn() => $db->close()->await()); }
});
