---
title: "Tasks وFutures وتنظيم العمل"
---

`Async::run()` يبدأ Scheduler واحداً، ينفذ المهمة الجذرية، وينتظر العمل المجدول قبل العودة. لا يمكن تداخل تشغيلين داخل بعضهما. قيمة return من الجذر هي نتيجة `run`، لكن بقاء مهمة تكرارية نشطة قد يمنع عودته؛ ألغِ المهام الدائمة صراحة.

## كيف تختلف Task عن Future؟

`Task` مهمة PHP تنفذ داخل Fiber، وهي أيضاً Future. أما Future فقد تمثل عملية native لا تحتاج Fiber منفصلة لكل خطوة. كلاهما يتيح `await()` و`isCompleted()` و`result()` و`exception()`؛ النتيجة المكتملة قابلة للانتظار أكثر من مرة. `result()` قبل الاكتمال يرمي `LogicException`، والنتيجة الفاشلة تعيد رمي الاستثناء المخزن.

العمليات تبدأ بشكل eager عند الإرسال أو عندما تعمل مهمة الإرسال؛ ترك Future دون انتظار لا يعني أن الطلب لم يُرسل. يحتفظ Scheduler بالعملية الأصلية حتى جمع اكتمالها. لا تستخدم `resolve/reject/subscribe` أو حقول Task الداخلية كواجهات تطبيق عادية؛ هذه حدود للبنية الداخلية لا تعادل نموذج Promise عام قابل للتعديل بأمان.

## اختيار أسلوب التجميع

```php
use Asyncara\{Async, TaskResult};
Async::run(function (): void {
    $jobs = [];
    foreach ([1, 2, 3] as $id) {
        $jobs[$id] = Async::spawn(function () use ($id): int {
            Async::sleep(0.001 * $id);
            return $id * 10;
        });
    }
    foreach (Async::asCompleted($jobs) as $id => $finished) {
        $result = TaskResult::from($finished);
        if ($result->error) { echo "failed $id\n"; continue; }
        echo $id.':'.$result->value."\n";
    }
});
```

`gather` يحتفظ بالمفاتيح وينتظر بترتيب الإدخال؛ فشله لا يلغي باقي العمليات تلقائياً. `asCompleted` ينتج Futures بترتيب ملاحظة اكتمالها، وليس قياساً لترتيب حزم الشبكة. `wait($jobs, timeout: 0.1, all: false)` يعيد `WaitResult` بصفيفي `completed` و`pending`. انتهاء مهلة wait لا يلغي pending؛ أنت تملك قرار متابعتها أو إلغائها.

## Structured concurrency

استخدم TaskGroup عندما يجب أن تنتهي وحدة العمل كلها معاً. فشل طفل يلغي الإخوة ويجمع تنظيفهم؛ فشل جسم المجموعة يفعل الشيء نفسه.

```php
use Asyncara\{Async, TaskGroup};
Async::run(function (): void {
    Async::taskGroup(function (TaskGroup $group): void {
        $group->spawn(function (): void { Async::sleep(0.01); echo "A\n"; });
        $group->spawn(function (): void { Async::sleep(0.02); echo "B\n"; });
    });
    echo "Both children have finished\n";
});
```

لا تفتح موارد داخل طفل وتعيد مرجعاً لها إلى مكان سيستعملها بعد انتهاء مالكها دون عقد إغلاق واضح. أبقِ المعاملة وقرض الاتصال وتدفق الملف في النطاق الذي يملك تنظيفه.

## العدالة وحدود العمل

لن يقطع Scheduler حلقة CPU لا تتوقف. جزّئ حساباً صغيراً واستعمل `Async::sleep(0)` عند حد عمل مناسب، أو انقل حساباً كبيراً إلى ProcessPool. ولا تنشئ مليون Task ثم تعتمد على Semaphore داخلها؛ ستكون قد خصصت مليون كائن بالفعل. استخدم Queue محدودة وعدداً ثابتاً من العمال، كما في [وصفة الدفعات](../recipes/pipeline.md).

`Runtime::configure(['taskLimit'=>2000,'futureLimit'=>4000])` يُستدعى قبل التشغيل. تجاوز السعة فشل صريح، وليس وعداً بتوسيع الذاكرة تلقائياً. راجع [الحدود](../operations/limits.md) و[الإلغاء](cancellation.md).
