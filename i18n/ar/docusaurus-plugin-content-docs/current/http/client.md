---
title: "طلبات HTTP عبر curl وHyper"
---

`Asyncara\HTTP\HttpClient` واجهة واحدة للنقل الافتراضي curl multi وللنقل الاختياري Rust Hyper. انتظار الشبكة يعتمد جاهزية حلقة الأحداث، بينما DNS والملفات يستخدمان العمال عند الحاجة. لا تحتاج hooks لدوال curl.

```php
use Asyncara\Async;
use Asyncara\HTTP\HttpClient;
Async::run(function (): void {
    $http = new HttpClient(concurrency: 8, timeout: 10, connectTimeout: 3);
    $pending = [
        'status' => $http->get('http://127.0.0.1:8080/status'),
        'data' => $http->post('http://127.0.0.1:8080/data', json: ['name' => 'Asyncara']),
    ];
    foreach (Async::gather($pending) as $name => $response) {
        if ($response->status >= 400) throw new RuntimeException($name.' returned '.$response->status);
        echo $response->body, "\n";
    }
});
```

الترويسات قائمة سطور مثل `['Accept: application/json']`. اختر واحداً من json/form/body في post. للطرق الأخرى استخدم request. الاستجابة تعرض status/body/headers وjson؛ فشل JSON مختلف عن فشل النقل. 404 و500 ردود HTTP عادية، وليست استثناءات اتصال تلقائية.

## اختيار النقل

```bash
ASYNCARA_HTTP_BACKEND=hyper-prototype asyncara client.php
ASYNCARA_BACKEND=poll asyncara client.php
```

`hyper-prototype` هو الاسم الفعلي للخيار، ويحتاج امتداداً يدعم Hyper. تحقق من `Runtime::information()->httpBackend`. متغير ASYNCARA_BACKEND يختار epoll/poll وليس بروتوكول HTTP. اضبطهما قبل بدء العملية.

HTTP الصريح يستخدم1.1؛ Hyper يتفاوض على HTTP/2 عبرTLS/ALPN. لا يوجد h2c أوHTTP/3. HTTP/2 في curl يعتمد بناء libcurl؛ تدفق ردوده يستخدم1.1 عمداً. هذه قدرات عميل، ولا تجعل Native Server خادمHTTP/2.

## الإعادة والحدود

التحويلات opt-in عبر followRedirects. يحد Hyper عدد القفزات ويرفض downgrade غير الآمن ويزيل الترويسات الحساسة المعروفة عند تغير origin؛ ترويساتك الخاصة تحتاج سياسة صريحة. هوية mTLS لا تنتقل إلى origin جديد، ولا يعاد بث iterable upload تلقائياً.

الجسم المخزن محدود حالياً بـ16MiB. استخدم [التدفق](streaming.md) للأكبر. تجاهل Future لا يعكس الإرسال، والمهلة لا تثبت أن POST لم يُنفذ. ميّز بين انتظار مشترك بـawait وإلغاء عملية مملوكة بـAsync::await.
