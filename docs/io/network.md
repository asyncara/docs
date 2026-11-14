---
title: "TCP, UDP, DNS and TLS"
---

TCP/UDP use nonblocking sockets and epoll/poll readiness. DNS uses native resolver workers; a name lookup does not turn getaddrinfo into a kernel-async resolver. Explicit TLS progresses through nonblocking handshake/read/write states.

## TCP round trip

```php
use Asyncara\Async;
use Asyncara\IO\{TcpServer, TcpStream};
Async::run(function () {
    $server = new TcpServer('127.0.0.1', 0);
    $peer = Async::spawn(function () use ($server) {
        $stream = $server->accept();
        try { $stream->write(strtoupper($stream->readExactly(4))); }
        finally { $stream->close(); }
    });
    $port = (int)substr(strrchr($server->address(), ':'), 1);
    try {
        $client = TcpStream::connect('127.0.0.1', $port)->await();
        try { $client->write('ping'); echo $client->readExactly(4), "\n"; }
        finally { $client->close(); }
        $peer->await();
    } finally { $server->close(); }
});
```

`read($maximum)` may return fewer bytes, and an empty string means EOF. `readExactly` throws if the peer closes before the requested length. `write` handles partial writes internally. One read and one write may coexist; concurrent readers on the same byte stream are not a message-routing mechanism. Define explicit framing, length bounds and a single read owner.

`TcpStream::connect('api.example.net', 443, tls: true, caFile: '/run/secrets/ca.pem')` resolves the name, validates the peer and drives TLS. Do not manually close a raw resource obtained from a wrapper and then reuse the wrapper; the canonical object owns watcher/descriptor cleanup. TcpServer is a raw TCP listener, not an implicit TLS server. Use App's TLS listener for HTTPS.

## UDP datagrams

```php
$receiver = new Asyncara\IO\UdpSocket('127.0.0.1', 0);
$sender = new Asyncara\IO\UdpSocket('127.0.0.1', 0);
try {
    $sender->send('hello', $receiver->address());
    $packet = $receiver->receive();
    echo $packet['data'];
} finally { $sender->close(); $receiver->close(); }
```

Run this inside Async::run. Destinations must be numeric IPv4/IPv6 with a valid port; resolve separately if needed. Datagram size is bounded and a partial send is an error. UDP provides no reliable delivery, ordering or application authentication. Bound the enclosing operation with `Async::timeout` when a peer may never reply.

DNS is available as `Dns::resolve($hostname)->await()`. Cache policy, retries and service discovery belong to your application. IPv6 addresses need bracket syntax when combined with a port. Always close sockets and listeners; cooperative cancellation releases waits but does not invent an application-level delivery acknowledgement.
