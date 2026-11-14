---
title: "Redis والأوامر والاشتراكات"
---

يستخدم Redis مقبس TCP/TLS غير حاجب ومحلل RESP الخاص بـAsyncara، وليس ext-redis. RESP2 افتراضي، وRESP3 خيار وقت الاتصال. ملكية الأوامر والردود متسلسلة حتى لا يستهلك مستدعٍ رد غيره.

```php
use Asyncara\Async;
use Asyncara\Redis\Connection;
Async::run(function () {
    $redis = Connection::connect('127.0.0.1', 6379, password: getenv('REDIS_PASSWORD') ?: null, user: 'default', resp3: true)->await();
    try {
        $redis->command(['SET', 'example:message', 'hello', 'EX', '60'])->await();
        echo $redis->command(['GET', 'example:message'])->await(), "\n";
    } finally { $redis->close(); }
});
```

للشبكة البعيدة فعّل tls وحدد caFile، واستعمل ACL بصلاحيات مناسبة. pipeline مجموعة محدودة حتى 10,000 أمر مع حدود بايتات؛ قسّم الأحجام الأكبر. أخطاء الخادم ضمن pipeline قيم ServerException في مواضعها، بينما command يرميها. افحص كل نتيجة.

transaction تستعمل MULTI/EXEC ولا تملك rollback الخاص بـSQL. انقطاع الشبكة قد يترك نتيجة بعيدة مجهولة؛ لا تعد تشغيل أوامر غير idempotent تلقائياً.

## الاشتراك

```php
$subscriber = Connection::connect('127.0.0.1', 6379, resp3: true)->await();
try {
    $subscriber->subscribe(['app.events'])->await();
    $message = $subscriber->nextMessage();
} finally { $subscriber->close(); }
```

الرسالة المعتادة `['message', channel, payload]`. استخدم اتصالاً مستقلاً للنشر. حدود الإشعارات الافتراضية 1024 رسالة و1MiB؛ تجاوزها خطأ وليس إسقاطاً صامتاً. statistics للمراقبة وdrainNotifications للاستهلاك الصريح. Pub/Sub ليس تسليماً دائماً.

Cluster وSentinel وRESP streaming aggregates ليست ميزات عميل مكتملة. لا توجد إعادة اتصال وإعادة إرسال شفافة؛ استبدل الاتصال بسياسة تطبيق واضحة.
