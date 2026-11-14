---
title: "SQLite: عمليات متسلسلة في العمال"
---

تنفّذ SQLite عملياتها الحاجبة داخل عمال أصليين محدودين، بينما يتوقف Fiber المنتظر. هذا Worker-backed Async وليس I/O شبكياً غير حاجب. تتسلسل العمليات على الاتصال نفسه؛ الاتصالات المستقلة تعمل بالتوازي ضمن قيود أقفال SQLite.

```php
use Asyncara\Async;
use Asyncara\SQLite\Connection;
Async::run(function () {
    $db = Connection::open(':memory:')->await();
    try {
        $db->execute('CREATE TABLE notes (id INTEGER PRIMARY KEY, title TEXT)')->await();
        $db->prepare('INSERT INTO notes(title) VALUES (?)')->execute(['hello'])->await();
        echo json_encode($db->query('SELECT * FROM notes')->await()), "\n";
    } finally { Async::shield(fn() => $db->close()->await()); }
});
```

النتائج مصفوفات صفوف؛ execute ليست واجهة تعيد عدد الصفوف المعدلة. اربط القيم بالمعاملات بدلاً من تركيب SQL. Statement يحتفظ بالاتصال والنص، ولا يعد بذاكرة cache دائمة للعبارات المحضرة. كل اتصال `:memory:` قاعدة مستقلة.

## الملكية والحدود

داخل المعاملة نفّذ الاستعلامات من Task المالك؛ إنشاء طفل ينتظر الاتصال ثم انتظاره من الأب قد يسبب انتظاراً دائرياً. راجع [المعاملات والمجمعات](transactions-pools.md). إلغاء المنتظر لا يتراجع عن عبارة نُفّذت، ويجب تصريف عمل العامل قبل تحرير موارده. أغلق الاتصال صراحةً مع await داخل shield؛ لا تنسخه أو تسلسله.

النتائج مجمّعة وليست stream؛ استخدم pagination وأعمدة محددة. حدود Asyncara لا تشمل كل ذاكرة SQLite أو مصفوفات PHP. مثال `examples/sqlite.php` ينفّذ معاملة وsavepoint وإغلاقاً على قاعدة مؤقتة دون خدمة خارجية.
