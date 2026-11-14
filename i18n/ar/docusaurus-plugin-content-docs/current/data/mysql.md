---
title: "Oracle MySQL وMariaDB"
---

تستخدم الواجهتان MySQL API نفسها فوق عمليات start/continue غير المتزامنة في MariaDB Connector/C. هذا تكامل مع جاهزية المقابس، وليس تغليف PDO داخل عامل. اختبار Oracle MySQL لا يكفي لإثبات كل إصدارات MariaDB والعكس.

```php
use Asyncara\Async;
use Asyncara\MySQL\Connection;
Async::run(function () {
    $db = Connection::connect([
        'host' => '127.0.0.1', 'port' => 3306, 'user' => 'app',
        'password' => getenv('DB_PASSWORD'), 'database' => 'app',
        'tls' => true, 'caFile' => '/run/secrets/db-ca.pem',
    ], timeout: 10)->await();
    try { $rows = $db->prepare('SELECT id, title FROM notes WHERE id = ?')->execute([42])->await(); }
    finally { $db->close(); }
});
```

TLS مفعّل افتراضياً. التحقق الحالي يتطلب عنوان IP وشهادة ذات IP SAN مطابق؛ لا تدّعي الواجهة اعتماد اتصال TLS باسم نطاق كيفما كان. certFile/keyFile لإثبات هوية العميل حسب Connector/C، وkeyPassword عند انطباقه. لتعمد اتصال تطوير محلي غير مشفر اختر tls=false صراحةً، لا كحل عام لأخطاء الشهادة.

## النتائج والمعاملات

Statement غلاف قابل لإعادة الاستخدام، وليس وعداً بـcache دائم في الخادم. query/execute تعيدان صفوفاً مجمّعة ومحدودة؛ استعمل pagination. تتسلسل العمليات على الاتصال الواحد، ويأتي التزامن من اتصالات مستقلة داخل pool. راجع [المعاملات](transactions-pools.md).

إلغاء المستقبل لا يثبت توقف الاستعلام البعيد. لا توجد إعادة اتصال أو إعادة محاولة للكتابة تلقائياً؛ استبدل اتصالاً غير سليم واعتمد idempotency عند إعادة المحاولة. اختبارات Oracle MySQL 8.4 وMariaDB 11.8 منفصلة ولا تشمل كل plugin أو خدمة مُدارة. وثّق نسخة الخادم وConnector/C وTLS عند تشخيص مشكلة دون نشر كلمات المرور.
