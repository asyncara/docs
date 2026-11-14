---
title: "وصفة: JSON API مع pool"
---

يجمع هذا التطبيق المسارات والتحقق من المدخلات واتصال SQLite كسولاً. أنشئ الجدول مرة واحدة قبل تشغيل الخدمة، لا داخل كل طلب:

```sql
CREATE TABLE notes (id INTEGER PRIMARY KEY, title TEXT NOT NULL);
```

```php
use Asyncara\App;
use Asyncara\Pool\ConnectionPool;
use Asyncara\SQLite\Connection;
use Asyncara\Server\{Request, Response};
$path = getenv('NOTES_DATABASE') ?: '/srv/app/notes.sqlite';
$pool = new ConnectionPool(fn() => Connection::open($path), fn($db) => $db->close(), size: 4, pendingLimit: 64);
$app = new App();
$app->get('/notes/{id}', function (Request $request) use ($pool) {
    $id = filter_var($request->param('id'), FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) return Response::text('Invalid id', 400);
    return $pool->withConnection(function ($db) use ($id) {
        $rows = $db->query('SELECT id, title FROM notes WHERE id = ?', [$id])->await();
        return $rows ? Response::json($rows[0]) : Response::text('Not found', 404);
    });
});
try { $app->listen('127.0.0.1:8080'); } finally { $pool->close(); }
```

لا يخرج الاتصال من نطاق الاستعارة. أضف مسار كتابة بربط `?` والتحقق من JSON وحجمه قبل الإدخال كما في [دليل الطلبات](../server/requests-responses.md). أضف مصادقة وrate limit وidempotency للتطبيق العام، ولا تعرض استثناء SQL الخام.

زيادة workers تنشئ pool لكل عملية؛ احسب الحدود وأقفال SQLite. الوصفة أحادية العملية وليست وعداً بمعاملات موزعة. اختبار محلي: أضف صفاً ثم اطلب `/notes/1`، وتحقق من400 للمعرف غير الصالح و404 للصف المفقود.
