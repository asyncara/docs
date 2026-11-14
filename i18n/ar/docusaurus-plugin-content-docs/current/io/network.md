---
title: "TCP وUDP وDNS وTLS"
---

TCP/UDP يعتمدان على مقابس غير حاجبة وجاهزية epoll/poll. DNS ينفّذ getaddrinfo في عمال أصليين. TLS الصريح يتقدم عبر حالات handshake/read/write غير الحاجبة.

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

read قد يعيد أقل من المطلوب و'' يعني EOF. readExactly يرمي إن انتهى الاتصال مبكراً. write يعالج الكتابات الجزئية. اجعل للقارئ ملكية واضحة ولا تعامل قرّاء متعددين كآلية توزيع رسائل. يمكن اجتماع قارئ وكاتب.

اتصال TLS يكون عبر `TcpStream::connect('api.example.net',443,tls:true,caFile:'/run/secrets/ca.pem')`. لا تغلق raw resource ثم تعاود استعمال غلافه. TcpServer ليس مستمع TLS تلقائياً؛ HTTPS متاح عبر App.

## UDP وDNS

```php
$receiver = new Asyncara\IO\UdpSocket('127.0.0.1', 0);
$sender = new Asyncara\IO\UdpSocket('127.0.0.1', 0);
try {
    $sender->send('hello', $receiver->address());
    $packet = $receiver->receive();
    echo $packet['data'];
} finally { $sender->close(); $receiver->close(); }
```

نفّذ المثال داخل Async::run. وجهة UDP عنوان رقمي ومنفذ صالح؛ استخدم Dns::resolve للاسم منفصلاً. UDP لا يضمن التسليم أو الترتيب. ضع timeout عندما قد لا يأتي الرد. عناوين IPv6 مع منفذ تستعمل الأقواس. الإلغاء لا يثبت وصول رسالة للتطبيق البعيد.
