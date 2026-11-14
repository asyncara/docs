---
title: "المسارات وMiddleware"
---

`App` خادم HTTP/1.1 يعمل من CLI، وتنفّذ معالجاته داخل Fibers. سجّل المسارات قبل `listen`، ولا تضع `listen` داخل `Async::run` لأنه يملك حلقة التشغيل العليا.

```php
use Asyncara\App;
use Asyncara\Server\{Request, Response};
$app = new App();
$app->get('/status', fn() => Response::json(['status' => 'OK']));
$app->group('/api/v1', function (App $router): void {
    $router->get('/users/{id}', function (Request $request): Response {
        $id = filter_var($request->param('id'), FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) return Response::text('Invalid id', 400);
        return Response::json(['id' => $id]);
    });
});
$app->listen('127.0.0.1:8080');
```

المسار الفعلي هو `/api/v1/users/42`. المسارات الثابتة لها الأولوية، ثم الديناميكية حسب ترتيب التسجيل. `{id}` يلتقط جزءاً غير فارغ ويفك percent encoding؛ لا يجعله رقماً أو مسار ملف آمناً تلقائياً. المسارات الثابتة المكررة وأسماء المعاملات المكررة مرفوضة.

توجد get/post/put/patch/delete؛ للطرق الأخرى استخدم `route('OPTIONS', '/resource', $handler)`. HEAD يعود إلى GET عند غياب تعريف خاص ويحذف الجسم. عدم تطابق المسار أو الطريقة يعطي404؛ لا تفترض405 تلقائياً.

## وسيط مصادقة مسمّى

```php
$app->middleware('auth', function (Request $request, callable $next): Response {
    $token = getenv('API_TOKEN');
    if (!is_string($token) || $token === '' || !hash_equals('Bearer '.$token, $request->header('authorization', ''))) {
        return Response::json(['error' => 'unauthorized'], 401);
    }
    return $next($request);
});
$app->post('/private', fn() => Response::json(['accepted' => true]))->middleware('auth');
```

يستقبل الوسيط الطلب وnext، وعليه إعادة استجابة في كل مسار. إذا كان المعالج يعيد StreamResponse فاستخدم نوع إرجاع يسمح به أيضاً. تعمل الوسائط بترتيب إضافتها؛ الاسم غير المسجل خطأ عند البدء. group يضيف prefix فقط، ولا يفرض المصادقة تلقائياً. CORS وCSRF والجلسات مسؤولية التطبيق.

لا تحتفظ بطلبات المستخدم في globals؛ العملية طويلة العمر. هيّئ إعدادات وpools كسولة قبل البدء، وافتح الاتصالات داخل العامل أو المعالج. انظر [وصفة API](../recipes/rest-api.md) لتطبيق كامل.
