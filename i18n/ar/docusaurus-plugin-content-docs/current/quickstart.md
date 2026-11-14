---
title: "أول تطبيق يعمل"
---

هذه البداية تستخدم CLI ومشغّل `asyncara`. ابدأ بفحص `asyncara --check`. لا تضف `Async::run()` حول `App::listen()`؛ الخادم يملك حلقة التنفيذ العليا بنفسه.

## مهمتان تنتظران معاً

احفظ الملف التالي باسم `tasks.php` وشغّله باستخدام `asyncara tasks.php`:

```php
<?php
use Asyncara\Async;
$result = Async::run(function (): array {
    $first = Async::spawn(function (): string {
        Async::sleep(0.02);
        return 'first';
    });
    $second = Async::spawn(function (): string {
        Async::sleep(0.01);
        return 'second';
    });
    return Async::gather(['first' => $first, 'second' => $second]);
});
echo json_encode($result, JSON_THROW_ON_ERROR), "\n";
```

النتيجة تحتفظ بترتيب المفاتيح حتى إذا انتهت المهمة الثانية أولاً. الاستدعاء `spawn` ينشئ مهمة قابلة للجدولة؛ `sleep` يعلّق المهمة الحالية؛ `gather` ينتظر القيم. إذا انتظرت كل مهمة فور إنشائها داخل الحلقة فسيتحول العمل إلى تسلسل، لذلك أنشئ العمليات أولاً ثم اجمع النتائج ضمن حدود معقولة.

## أول خادم JSON

```php
<?php
use Asyncara\App;
use Asyncara\Server\{Request, Response};
$app = new App();
$app->get('/status', fn() => Response::json(['status' => 'OK']));
$app->get('/hello/{name}', fn(Request $request) =>
    Response::json(['hello' => $request->param('name')])
);
$app->post('/data', fn(Request $request) =>
    Response::json(['received' => $request->json()], 201)
);
$app->listen('127.0.0.1:8080');
```

```bash
asyncara server.php
# في نافذة أخرى:
curl http://127.0.0.1:8080/status
curl http://127.0.0.1:8080/hello/Ajmal
curl -H 'Content-Type: application/json' -d '{"name":"Asyncara"}' http://127.0.0.1:8080/data
```

إذا غاب `asyncara_server_listen` فحزمتك لم تُبن بميزة Hyper server؛ وجود امتداد أساسي لا يكفي. الخادم يتطلب CLI ويستخدم HTTP/1.1. إنهاء Ctrl+C يطلب الإغلاق عند توفر pcntl؛ اقرأ [النشر](server/deployment.md) قبل تشغيل خدمة دائمة.

## عميل يستدعي الخادم

```php
<?php
use Asyncara\Async;
use Asyncara\HTTP\HttpClient;
Async::run(function (): void {
    $client = new HttpClient(timeout: 5);
    $response = $client->get('http://127.0.0.1:8080/status')->await();
    if ($response->status !== 200) throw new RuntimeException('HTTP '.$response->status);
    echo $response->json()['status'], "\n";
});
```

استجابة العميل لها `status` و`body` و`headers` و`json()`. حالة HTTP مثل 404 لا تُعد خطأ نقل تلقائياً؛ افحصها كما في المثال. أخطاء الاتصال وTLS والمهلة تظهر كاستثناءات.

## أمثلة كاملة قابلة للتشغيل

المجلد [examples في مستودع التوثيق](https://github.com/asyncara/docs/tree/main/examples) يحتوي على ملفات كاملة للتزامن والإلغاء والطوابير وSQLite والملفات والشبكة وProcessPool والخادم وعميل HTTP. شغّلها بـ`asyncara examples/tasks.php`. ملفات bootstrap تشرح طريقة التحميل إذا استخدمت PHP مباشرة. اختبارات هذه الأمثلة محلية وحتمية؛ أمثلة الخدمات الخارجية في الأدلة تحتاج إعداد الخادم والشهادات الخاص بك.
