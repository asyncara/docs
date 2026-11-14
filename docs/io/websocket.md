---
title: "WebSocket clients and TCP upgrades"
---

WebSocket supports ws/wss clients and a server upgrade over an accepted TcpStream. It implements bounded RFC 6455 messages, masking, fragmentation, UTF-8 checks, ping/pong and close handling. It is not a claim of every browser, extension or Autobahn case being certified.

## Client lifecycle

```php
use Asyncara\Async;
use Asyncara\WebSocket\Client;
Async::run(function () {
    $socket = Client::connect('wss://echo.example.net/socket',
        protocols: ['example.v1'], caFile: '/run/secrets/ca.pem')->await();
    try {
        $socket->send('hello');
        $message = $socket->receive();
        if ($message !== null) echo $message->data;
        $socket->ping(timeout: 2)->await();
    } finally { Async::shield(fn() => $socket->close()); }
});
```

Use a real endpoint that supports the requested subprotocol. `receive()` returns a Message or null on closure; Message has data and binary fields. Use `send($bytes, binary: true)` for binary data. A connection has a single receive stream; distribute messages through an application Queue if multiple consumers need work.

The defaults bound message size to 1 MiB, queued messages to 16 and queued bytes to 4 MiB. Slow consumers fail/close rather than accumulating unlimited messages. `statistics()` exposes queue and close state. `ping` resolves on its matching pong or times out. Graceful close waits within its timeout; `abort` closes immediately when graceful exchange is no longer appropriate.

## Server upgrade

Accept raw TCP first, then call `Asyncara\WebSocket\Server::accept($stream, origins: [...], protocols: [...], authorize: $callback)->await()`. The callback receives the request target and parsed handshake header array and must explicitly authorize the peer. Validate authentication independently of Origin; non-browser clients can omit or forge Origin. Use a bounded number of connection Tasks and close the listener during shutdown.

This upgrade API is separate from App routing. Do not assume an App route can return a WebSocket object, or that App's HTTPS listener supplies WSS upgrade support. A direct WSS server backend is not certified by this raw-TCP interface. A separately configured TLS reverse proxy can terminate transport, but that deployment must be tested.

Reconnection is application policy: use bounded backoff, authenticate again and decide how to recover missed messages. Automatic replay of application messages, compression extensions and exactly-once delivery are not provided. Cancellation cannot undo a frame already sent.
