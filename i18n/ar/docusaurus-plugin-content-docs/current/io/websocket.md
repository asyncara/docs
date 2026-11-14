---
title: "WebSocket والترقية من TCP"
---

يوفر المحرك عميل ws/wss وترقية خادم فوق TcpStream مقبول. يدعم framing وmasking وfragmentation وUTF-8 وping/pong والإغلاق بحدود ذاكرة. هذا ليس اعتماداً لكل متصفح أو extension أو حالة Autobahn.

```php
use Asyncara\Async;
use Asyncara\WebSocket\Client;
Async::run(function () {
    $socket = Client::connect('wss://echo.example.net/socket', protocols: ['example.v1'], caFile: '/run/secrets/ca.pem')->await();
    try {
        $socket->send('hello');
        $message = $socket->receive();
        if ($message !== null) echo $message->data;
        $socket->ping(timeout: 2)->await();
    } finally { Async::shield(fn() => $socket->close()); }
});
```

يتطلب المثال endpoint حقيقياً يدعم البروتوكول. receive يعيد Message بحقلَي data/binary أو null للإغلاق. لإرسال ثنائي استخدم send(binary:true). اجعل مستهلكاً واحداً للقراءة ثم وزّع عبر Queue عند الحاجة.

الحدود الافتراضية: رسالة1MiB وطابور16 رسالة/4MiB. بطء المستهلك لا يسبب تراكمًا بلا حد؛ راقب statistics. ping ينتظر pong المطابق، وclose مصافحة محدودة بمهلة؛ abort للإغلاق الفوري.

## الخادم والقيود

استقبل TCP ثم استعمل `WebSocket\Server::accept` مع origins/protocols/authorize. يتلقى authorize هدف الطلب ومصفوفة ترويسات المصافحة. Origin ليس مصادقة؛ يمكن لعميل غير متصفح تزويره أو حذفه. حد عدد Tasks وأغلق المستمع عند الإنهاء.

الترقية منفصلة عن App؛ لا توجد استجابة WebSocket تلقائية من route ولا اعتماد WSS مباشر عبر مستمع App. TLS reverse proxy يحتاج اختبار نشر منفصلاً. إعادة الاتصال واسترجاع الرسائل مسؤولية التطبيق، ولا توجد إعادة إرسال تلقائية أو exactly-once أو extensions ضغط.
