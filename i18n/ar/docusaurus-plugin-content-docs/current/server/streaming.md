---
title: "بث الطلبات والاستجابات"
---

التدفق مناسب للتصدير والملفات الكبيرة، لكنه يغيّر معالجة الفشل: بعد إرسال الترويسات لا يمكن تحويل الرد إلى JSON500 جديد.

```php
use Asyncara\IO\Files;
use Asyncara\Server\Response;
$app->get('/export', function () {
    return Response::stream(Files::readStream('/srv/data/export.csv'), headers: [
        'content-type' => 'text/csv; charset=utf-8',
        'content-disposition' => 'attachment; filename="export.csv"',
    ]);
});
```

تُقرأ الملفات في العمال الأصليين، ويطلب النقل القطع عندما يستطيع إرسالها. كل قطعة نصية بحد64KiB؛ الحجم الافتراضي لـFiles يناسبه. StreamResponse أحادي الاستخدام ويملك منتجه، ويغلقه App عند النهاية والفشل وHEAD وانقطاع العميل. يمكنك إنتاج NDJSON أو framing لـSSE عبر generator كسول، لكن لا تجمع كل البيانات أولاً، ولا تعتبر ذلك اعتماداً لكل متصفح أو ضماناً لإعادة الاتصال.

## استقبال جسم متدفق

```php
use Asyncara\Async;
use Asyncara\IO\Files;
use Asyncara\Server\{Request, Response};
$app->post('/upload', function (Request $request): Response {
    $path = '/srv/uploads/'.bin2hex(random_bytes(16));
    $complete = false;
    try {
        $bytes = Async::await(Files::writeStream($path, $request->stream()));
        $complete = true;
        return Response::json(['bytes' => $bytes], 201);
    } finally {
        if (!$complete) Async::shield(function () use ($path): void {
            if (Files::exists($path)->await()) Files::delete($path)->await();
        });
    }
});
$app->listen('127.0.0.1:8080', ['streamRequests' => true, 'bodyBytes' => 8388608]);
```

اختر مجلداً خاصاً واسماً مولداً، لا مساراً يقدمه المستخدم. المثال يحذف الملف الجزئي عند الفشل؛ ليس محلل multipart ولا نشراً ذرياً لاسم عام. دون streamRequests يكون الجسم مخزناً أصلاً؛ ومعه يكون التدفق أحادي المستهلك ومرتبطاً بعمر المعالج. `json()` يجمع الجسم قبل فكّه، فاستخدمه لطلبات JSON محدودة.

انقطاع العميل يلغي المعالج. نظّف في finally ولا تنقل تدفق الطلب إلى عمل يعيش بعده. يغلق App المدخل المتبقي عند انتهاء dispatch. إذا فشل إرسال الرد بعد بدايته يُنهى النقل؛ لا يضاف رد خطأ ثانٍ إلى نفس الجسم.
