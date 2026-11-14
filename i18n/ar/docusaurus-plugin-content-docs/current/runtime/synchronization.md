---
title: "القفل وSemaphore وEvent وQueue"
---

هذه الأدوات تنسّق Fibers داخل مفسر واحد، ولا تحمي ملفاً أو قاعدة بيانات من عملية أخرى. عند تشغيل عدة عمال Server أو ProcessPool تحتاج آلية مشتركة خارج العملية إذا كانت البيانات مشتركة.

## حماية حالة قصيرة بالقفل

```php
use Asyncara\Concurrency\Lock;
$lock = new Lock();
$counter = 0;
$increment = function () use ($lock, &$counter): int {
    return $lock->synchronized(function () use (&$counter): int { return ++$counter; });
};
```

القفل غير reentrant، ومالكه هو المهمة التي اكتسبته. لا تستدع `synchronized` لنفس القفل من داخل نطاق يملكه أصلاً. استخدم try/finally عند acquire/release اليدوي، ولا تنتظر طفلاً يحتاج القفل الذي تحتفظ به؛ سيصنع ذلك deadlock منطقياً حتى لو كانت حلقة الأحداث سليمة.

## تحديد عدد العمليات المتزامنة

```php
use Asyncara\{Async, TaskGroup};
use Asyncara\Concurrency\Semaphore;
Async::run(function (): void {
    $slots = new Semaphore(4);
    Async::taskGroup(function (TaskGroup $group) use ($slots): void {
        for ($n = 0; $n < 20; $n++) {
            $group->spawn(fn() => $slots->withPermit(fn() => Async::sleep(0.01)));
        }
    });
});
```

المنتظرون في Semaphore لا يستهلكون حصة حتى تسلّمها. `withPermit` يعيد الحصة عند رمي الاستثناء أيضاً. `tryAcquire` لا يعلّق؛ إذا أعاد false فاختر التأجيل أو رفض العمل حسب تطبيقك. السعة تحدد العمل الجاري، لكنها لا تحد عدد Tasks التي أنشأتها أنت قبل الوصول إلى القفل.

## Queue وبروتوكول اكتمال العمل

السعة الافتراضية 100 عنصر، و`new Queue(0)` يعني طلب مخزن غير محدود صراحة. فضّل سعة موجبة. `put` يعلّق المنتج عندما تمتلئ؛ `tryPut` يعيد false دون تعليق. `get` يعلّق المستهلك عند الفراغ. بعد كل عنصر مقبول استدع `taskDone` مرة واحدة، حتى إذا فشلت معالجته؛ `join` ينتظر اكتمال هذه المحاسبة، وليس فراغ المخزن فقط.

```php
use Asyncara\{Async};
use Asyncara\Concurrency\Queue;
use Asyncara\Exception\QueueClosedException;
Async::run(function (): void {
    $queue = new Queue(8);
    $consumer = Async::spawn(function () use ($queue): void {
        while (true) {
            try { $item = $queue->get(); } catch (QueueClosedException) { return; }
            try { echo $item, "\n"; } finally { $queue->taskDone(); }
        }
    });
    foreach (range(1, 20) as $item) $queue->put($item);
    $queue->close();
    $consumer->await();
    $queue->join();
});
```

`close` يرفض الكتابات الجديدة والمنتظرين، لكنه يسمح بتصريف العناصر المخزنة. بعد تصريفها يرمي `get` استثناء الإغلاق. الإغلاق لا يعفيك من `taskDone`، وإلغاء مستهلك بعد استلام عنصر لا يعيده تلقائياً إلى الطابور. أضف سياسة إعادة المحاولة أو dead-letter في تطبيقك.

## Event لشرط مستقل

`Event` manual-reset: يستمر أثر `set` حتى `reset`. تصل الإشارة لكل المنتظرين، وليس لمنتظر واحد. يمكن استخدامه لحاجز بدء أو نشر اكتمال تهيئة مشتركة. إعادة الضبط لا تسحب إشارة سُلّمت بالفعل. إذا احتجت ترتيب رسائل وعدّها فاستعمل Queue بدلاً منه. افحص `statistics()` للطوابير لمعرفة المخزن والمنتظرين والعمل غير المنتهي عند تشخيص توقف.
