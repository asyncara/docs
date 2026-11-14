---
title: "التنزيل والرفع وMultipart"
---

التدفق يحد التخزين ويطبق backpressure عند بطء المستهلك. لا يجعل generator حاجباً غير حاجب، ولا يمنعك من استهلاك الذاكرة إذا خزنت كل القطع بنفسك.

```php
$stream = $http->getStream('https://service.example/export');
try {
    $head = $stream->headers()->await();
    if ($head->status !== 200) throw new RuntimeException('Unexpected status');
    foreach ($stream as $chunk) echo strlen($chunk), "\n";
} finally { $stream->close(); }
```

فتحة الجسم الأصلية64KiB والطابور قطعتان، مع ذاكرة مؤقتة إضافية للمنتج والمستهلك. للترويسات حد64KiB منفصل. التدفق يطلب identity؛ إذا أرسل الخادم ضغطاً رغم ذلك تبقى القطع مشفرة بالضغط، فافحص Content-Encoding. framing النقل يُعالج آلياً.

## تنزيل ذري

```php
$bytes = $http->download('https://service.example/archive', '/srv/data/archive.bin',
    atomic: true, durable: true, failOnHttpError: true)->await();
```

الوضع الافتراضي قد يقطع الوجهة ويترك جزءاً منها عند الفشل. atomic يكتب ملفاً مرحلياً في المجلد نفسه ثم ينشر الجسم المكتمل. durable يضيف مزامنة الملف والمجلد؛ إذا فشلت المزامنة بعد rename فقد تكون `FilesystemException::committed` صحيحة. الإلغاء لا يعكس النشر. failOnHttpError يرفض غير2xx قبل النشر.

## رفع ملفات أو iterable

```php
$reply = $http->upload($url, '/srv/data/input.bin')->await();
$reply = $http->requestStream('PUT', $url, (function (): Generator {
    yield 'first'; yield 'second';
})(), contentLength: 11)->await();
$reply = $http->multipartFiles($url, ['description' => 'report'], [
    ['field' => 'files', 'path' => '/srv/data/a.txt', 'filename' => 'a.txt', 'contentType' => 'text/plain'],
    ['field' => 'files', 'path' => '/srv/data/b.bin'],
])->await();
```

upload يستخدمPUT افتراضياً وقراءة عمال. قطع iterable نصية حتى64KiB. الرد المبكر قد يوقف المنتج قبلEOF؛ لا تفترض قراءة المصدر كاملاً. HEAD بجسم أو تدفق رفع مرفوض.

multipartFiles يقبل1..16 ملفاً وحقولاً نصية محدودة حتى64 حقلاً. لا تفرض boundary يدوياً. لا يوجد nested MIME أو replay تلقائي للرفع بعد redirect أو بروتوكول استئناف مدمج. أغلق ReadableStream الذي أنشأته في finally، والإلغاء لا يسحب بيانات وصلت إلى الطرف الآخر.
