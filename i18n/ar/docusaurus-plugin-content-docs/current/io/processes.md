---
title: "ProcessPool للأعمال كثيفة المعالج"
---

Fibers تتعاون على خيط مفسر واحد؛ حلقة PHP كثيفة الحساب تحجب الآخرين إن لم تتنازل. ProcessPool ينفّذ وظائف مسجلة في عمليات OS منفصلة، دون مشاركة كائنات Zend بين خيوط.

```php
<?php
// jobs.php
return ['digest' => static fn(string $text): string => hash('sha256', $text)];
```

```php
use Asyncara\Async;
use Asyncara\Pool\ProcessPool;
Async::run(function () {
    $pool = new ProcessPool(2, __DIR__.'/jobs.php', queueLimit: 32);
    try {
        $results = Async::gather([$pool->submit('digest', ['first'], timeout: 5), $pool->submit('digest', ['second'], timeout: 5)]);
        echo json_encode($results), "\n";
    } finally { $pool->close(); }
});
```

bootstrap كود موثوق؛ لا تسمح للطلب باختيار مساره. القيم تعبر IPC محدوداً ومتوافقاً مع JSON، وليس موارد أو closures أو اتصالات قواعد بيانات حية. لكل طفل إعداداته واعتمادياته، فحمّلها صراحةً.

العمال1..64 والطابور محدود عدداً وبايتات؛ الافتراضي256 وظيفة/64MiB وذاكرة عامل256MiB. هذه ليست حدوداً لكل تخصيص خارجي أو kernel buffer. راقب statistics وحدود النشر.

تعطل أو إلغاء أو انتهاء مهلة عامل قد يستبدله؛ الوظيفة الفاشلة لا تعاد تلقائياً لأنها قد أحدثت آثاراً خارجية. اختر retry/idempotency صراحةً. close يلغي وينظف الأبناء، فاستعمل finally. لا تنسخ pool أو تحمله عبر fork. هذا ليس ThreadPool؛ توافر تشغيل العمليات وملف العامل يحتاج تحققاً منفصلاً في البيئات المعزولة وAndroid.

:::caution قيد الحزمة الأولى
حزمة Linux0.10.0 الأولية لا تتضمن bin/worker.php. يحتاج ProcessPool تثبيت runtime/مصدر كامل يحتويه؛ نجاح اختبار المصدر لا يثبت وجود الملف في الحزمة.
:::
