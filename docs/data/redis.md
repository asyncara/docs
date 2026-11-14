---
title: "Redis commands, pipelines and subscriptions"
---

Redis uses nonblocking TCP/TLS and Asyncara's RESP parser. It does not call ext-redis. RESP 2 is the default; select RESP 3 at connection time. The connection serializes command/response ownership so one caller cannot consume another caller's reply.

```php
use Asyncara\Async;
use Asyncara\Redis\Connection;
Async::run(function () {
    $redis = Connection::connect('127.0.0.1', 6379,
        password: getenv('REDIS_PASSWORD') ?: null, user: 'default', resp3: true)->await();
    try {
        $redis->command(['SET', 'example:message', 'hello', 'EX', '60'])->await();
        echo $redis->command(['GET', 'example:message'])->await(), "\n";
        $results = $redis->pipeline([['INCR', 'example:counter'], ['TTL', 'example:message']])->await();
    } finally { $redis->close(); }
});
```

Use ACL credentials appropriate to the commands your service needs. For remote connections enable `tls: true` and provide the intended `caFile`; do not send credentials across an untrusted plaintext network.

## Pipelines are bounded batches, not transactions

A pipeline writes a batch and collects ordered results. It accepts at most 10,000 commands and bounds request/result buffering. Chunk larger workloads; issuing a huge PHP array first still consumes PHP memory before native admission. A server error inside a pipeline is represented by a ServerException result at that position, so inspect every result. A single `command` raises a server error instead.

`transaction($commands)` uses MULTI/EXEC on one connection. Redis transaction semantics do not provide SQL-style rollback of commands already executed. A network interruption can leave the remote outcome unknown. Do not replay non-idempotent batches blindly.

## Dedicated subscriptions

```php
$subscriber = Connection::connect('127.0.0.1', 6379, resp3: true)->await();
try {
    $subscriber->subscribe(['app.events'])->await();
    $message = $subscriber->nextMessage();
    // A normal message is ['message', channel, payload].
} finally { $subscriber->close(); }
```

Use another connection to publish. The subscriber has an owner and bounded notifications: `notificationLimit` defaults to 1,024 and `notificationByteLimit` to 1 MiB. Overflow fails rather than silently discarding application messages. Process promptly, inspect `statistics`, and use a durable Redis Streams/application design when Pub/Sub's delivery semantics are insufficient. `drainNotifications` is an explicit consumption operation, not a background loss policy.

Cluster redirection, Sentinel failover and RESP streaming aggregates are not implemented as full client features. There is no transparent reconnect/replay guarantee. A closed or uncertain connection must be replaced by application policy. RESP 3 support is not a claim that every Redis deployment topology or module-specific push format is certified.
