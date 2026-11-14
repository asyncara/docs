---
title: "مرجع API والتحقق من الأمثلة"
---

استُخرج المرجع بالـreflection من runtime المنشور0.10.0. التوقيعات لا تستبدل شرح الملكية والإلغاء في الأدلة ولا تثبت اعتماد كل بيئة.

| القدرة | المرجع |
|---|---|
| Async وTasks وFutures والتتبّع | [التشغيل](runtime.md) |
| Queue وLock وStreams | [التزامن](concurrency.md) |
| App والطلبات والاستجابات | [الخادم](server.md) |
| HTTP وPSR | [العميل](http.md) |
| SQL وRedis وPools | [قواعد البيانات](databases.md) |
| ملفات وشبكات وعمليات | [I/O](io.md) |
| الاستثناءات | [الأخطاء](errors.md) |

## الأمثلة

توجد سكربتات كاملة في [examples](https://github.com/asyncara/docs/tree/main/examples). شغّل `asyncara examples/tasks.php`. تغطي Tasks وإلغاء منتظر مشترك وQueue وContext وSQLite والملفات وTCP وProcessPool وApp/HTTP محلياً. فحص lint-only يتحقق من الصياغة فقط؛ فحص التشغيل يحتاج الامتداد واعتمادياته. نطاقات example.net إعدادات توضيحية وليست شهادة لخوادمك.

الخادم في Asyncara\Server والعميل في Asyncara\HTTP. دوال asyncara_* تفاصيل داخلية. المرجع يحذف الأصناف الداخلية وmagic الخاصة بالإغلاق والنسخ؛ راجع أدلة دورة الحياة.

## PSR-18

```php
$factory = new Nyholm\Psr7\Factory\Psr17Factory();
$client = new Asyncara\HTTP\Psr\Client($factory, $factory);
$request = $factory->createRequest('GET', 'https://api.example.net/status');
$response = $client->sendRequest($request);
```

وفّر PSR17 factories واعتماديات Nyholm. sendRequest يتعاون مع التشغيل الموجود أو يبدأ نطاقاً جديداً، لكنه يجمع جسم الطلب والرد؛ stream تابع لمكتبة أخرى قد يحجب. ليس streaming ولا اعتراضاً شفافاً للمكتبات. حزم Android لا تتضمن تلقائياً كل اعتماديات PSR الاختيارية.
