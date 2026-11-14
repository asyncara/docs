---
title: "PostgreSQL والاتصال المملوك للمعاملة"
---

يستخدم المحرك libpq بوضع غير حاجب للاتصال والإرسال وتصريف النتائج، مع مراقبة جاهزية المقبس في Scheduler. هذا لا يحول PDO أو ext-pgsql تلقائياً إلى واجهات غير متزامنة.

```php
use Asyncara\Async;
use Asyncara\PostgreSQL\Connection;
Async::run(function () {
    $db = Connection::connect([
        'host' => 'db.example.net', 'port' => '5432', 'dbname' => 'app',
        'user' => 'app', 'password' => getenv('DB_PASSWORD'),
        'sslmode' => 'verify-full', 'sslrootcert' => '/run/secrets/db-ca.pem',
    ], timeout: 10, preparedCacheLimit: 32)->await();
    try { $rows = $db->prepare('SELECT id, title FROM notes WHERE id = $1')->execute([42])->await(); }
    finally { $db->close(); }
});
```

يتطلب المثال خادماً وشهادة وCA وبيانات دخول صحيحة. أبق اسم المضيف للتحقق من الشهادة؛ أضف sslcert/sslkey لـmTLS عند الحاجة. لا تعطّل التحقق لتجاوز خطأ. دعم SCRAM يعتمد أيضاً على libpq وسياسة الخادم.

النتائج صفوف مجمّعة، وليس stream. cache العبارات المحضرة معطل افتراضياً؛ يحدّه preparedCacheLimit وpreparedCacheByteLimit ويتبع الاتصال. مسار prepare/execute يستفيد منه عند تمكينه، ولا يطابق query المباشر.

## الإشعارات والإلغاء

خصّص اتصالاً لـ`LISTEN app_events` ثم استدع `nextNotification()` داخل التشغيل. تنتهي الذاكرة المحدودة للإشعارات بخطأ عند التجاوز؛ لا يوجد وعد بتسليم دائم مثل queues الرسائل.

الإلغاء المحلي لا يثبت أن PostgreSQL أوقف الاستعلام. قد يغلق المحرك الاتصال لتجنب إعادة استخدام حالة بروتوكول غير سليمة. استبدله ولا تعد محاولة كتابة ذات نتيجة مجهولة دون idempotency. المعاملات والعبارات المحضرة مرتبطة بالاتصال؛ راجع [المعاملات](transactions-pools.md).
