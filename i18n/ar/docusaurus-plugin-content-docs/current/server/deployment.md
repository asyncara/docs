---
title: "النشر ودورة الطلب"
---

Native Server عملية CLI طويلة العمر. لا تفترض انتهاء globals بعد الطلب كما في دورة PHP-FPM. ضع الإعدادات الثابتة على مستوى العملية وبيانات الطلب في Request/Context، واحتفظ بقرض قاعدة البيانات داخل نطاق تنظيف واضح.

```php
use Asyncara\Runtime;
Runtime::configure(['taskLimit' => 4000, 'futureLimit' => 8000, 'nativeMemoryLimit' => 268435456]);
$app->listen('127.0.0.1:8080', [
    'connections' => 1024, 'bodyBytes' => 1048576,
    'responseBytes' => 8388608, 'bufferBytes' => 67108864,
    'timeoutMs' => 30000, 'requestsPerConnection' => 10000,
]);
```

خيارات الخادم أعداد صحيحة وtimeoutMs بالمللي ثانية. الحدود لكل عملية؛ bodyBytes ليس memory_limit، والدفتر الأصلي لا يحصي كل تخصيصات Rust/TLS. احسب مجموع العمال والاتصالات وقيم PHP وحدود النظام.

## خلف Reverse Proxy

اربط التطبيق بواجهة خاصة، واضبط حدود الأجسام والمهل في الطرفين. إذا احتجت بثاً فورياً عطّل buffering على المسار المناسب:

```nginx
location / {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header Connection "";
    proxy_buffering off;
}
```

هذا إعداد بداية، وليس شهادة توافق شاملة. لا يثق App تلقائياً في Forwarded أو X-Forwarded-*؛ لا تستخدم عنواناً يرسله المستخدم للمصادقة. حدّد TLS ومعدل الطلبات في طبقة النشر.

شغّل الخدمة بمدير عمليات ووقت إغلاق محدود، وأرسل إيقافاً لطيفاً قبل القتل. تجنّب الكتابة المتزامنة للسجلات داخل المعالج. `Runtime::diagnostics` يقيس موارد الجدولة و`App::stats` يقيس النقل. توافق FPM لم يُحذف، لكنه ليس أولوية التطوير الحالية؛ `listen` نفسه متاح فقط في CLI.
