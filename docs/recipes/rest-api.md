---
title: "Recipe: a pool-backed JSON API"
---

This service combines App routing, input validation and a lazy SQLite pool. Create the database schema once as a deployment step; do not run migrations for every request. Run the service as a user that can access the database directory.

```sql
CREATE TABLE notes (id INTEGER PRIMARY KEY, title TEXT NOT NULL);
```

```php
<?php
use Asyncara\App;
use Asyncara\Pool\ConnectionPool;
use Asyncara\SQLite\Connection;
use Asyncara\Server\{Request, Response};
$path = getenv('NOTES_DATABASE') ?: '/srv/app/notes.sqlite';
$pool = new ConnectionPool(fn() => Connection::open($path), fn($db) => $db->close(), size: 4, pendingLimit: 64);
$app = new App();
$app->get('/notes/{id}', function (Request $request) use ($pool) {
    $id = filter_var($request->param('id'), FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) return Response::text('Invalid id', 400);
    return $pool->withConnection(function ($db) use ($id) {
        $rows = $db->query('SELECT id, title FROM notes WHERE id = ?', [$id])->await();
        return $rows ? Response::json($rows[0]) : Response::text('Not found', 404);
    });
});
$app->post('/notes', function (Request $request) use ($pool) {
    $data = $request->json();
    if (!is_array($data) || !isset($data['title']) || !is_string($data['title']) ||
        $data['title'] === '' || strlen($data['title']) > 200) return Response::text('Invalid title', 422);
    return $pool->withConnection(function ($db) use ($data) {
        $db->execute('INSERT INTO notes(title) VALUES (?)', [$data['title']])->await();
        return Response::json(['created' => true], 201);
    });
});
try { $app->listen('127.0.0.1:8080'); }
finally { $pool->close(); }
```

A pool lease covers the complete operation; no Connection escapes the callback. App handles malformed JSON as a 400 response, but your handler validates the shape and size. In a public API, add authentication, Content-Type policy, rate limits and an idempotency strategy for writes. Do not expose raw SQL exception text to clients.

Try `curl -H 'Content-Type: application/json' -d '{"title":"first"}' http://127.0.0.1:8080/notes`, then GET `/notes/1`. Increasing server workers creates an independent pool per process; size your database connections and SQLite write contention accordingly. This recipe is intentionally one-process and does not claim distributed transaction safety.
