---
title: "المعاملات ومجمعات الاتصالات"
---

تشارك محركات SQL مبادئ دورة حياة، لا بروتوكولاً واحداً. مصنع Database يختار SQLite أو PostgreSQL أو MySQL/MariaDB؛ Redis منفصل وله MULTI/EXEC مختلف.

```php
$db->transaction(function ($connection) {
    $connection->execute('INSERT INTO notes(title) VALUES (?)', ['draft'])->await();
    $connection->savepoint('optional_step')->await();
    $connection->execute('INSERT INTO notes(title) VALUES (?)', ['extra'])->await();
    $connection->releaseSavepoint('optional_step')->await();
})->await();
```

المثال يستخدم معاملات SQLite/MySQL؛ في PostgreSQL استخدم `$1`. نجاح callback يؤدي إلى commit، والفشل إلى rollback. المعاملة مملوكة لـTask واحد؛ لا تنتظر طفلاً يحتاج الاتصال نفسه داخلها. للتداخل استخدم savepoints وليس begin داخل begin.

## تحكم يدوي

```php
$db->beginTransaction()->await();
try {
    $db->commit()->await();
} catch (Throwable $error) {
    if ($db->inTransaction()) Asyncara\Async::shield(fn() => $db->rollback()->await());
    throw $error;
}
```

inTransaction حالة محلية وليست فحصاً مستقلاً للخادم. لا تخلط BEGIN/COMMIT الخام مع واجهات المعاملة المُدارة، ولا تدخل SQL غير موثوق في أسماء savepoint.

## استعارة اتصال

```php
$pool = new Asyncara\Pool\ConnectionPool(
    fn() => Asyncara\SQLite\Connection::open('/srv/app/notes.sqlite'),
    fn($db) => $db->close(), size: 4, pendingLimit: 128,
);
$rows = $pool->withConnection(fn($db) => $db->query('SELECT id, title FROM notes')->await());
```

نفّذ الاستعارة داخل Async::run أو معالج App. الإنشاء كسول، ويفهم pool مصنعاً وإغلاقاً غير متزامنين. استثناء المستعير يتسبب في استبعاد الاتصال. لا تحتفظ بالاتصال بعد انتهاء النطاق، ولا تعد عملية معلقة ما زالت تحتاج ملكيته. size يحد الاتصالات وpendingLimit الانتظار.

أغلق pool بعد انتهاء كل المستعيرين؛ الإغلاق أثناء وجودهم مرفوض. لكل عامل خادم pool مستقل، لذلك احسب الاتصالات الكلية بعدد العمال × حجم pool. لا توجد إعادة محاولة شفافة للمعاملات.
