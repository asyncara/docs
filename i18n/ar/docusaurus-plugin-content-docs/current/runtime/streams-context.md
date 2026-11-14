---
title: "التدفقات والسياق المعزول"
---

التدفق يعالج عناصر تدريجياً بدلاً من الاحتفاظ بكل النتائج. `AsyncStream` يملك منتجاً واحداً وطابوراً محدوداً، ويقبل مستهلكاً واحداً. عندما يمتلئ الطابور يعلّق `emit` المنتج؛ هذه هي backpressure الفعلية، لا مجرد تقسيم مصفوفة كاملة بعد تحميلها.

```php
use Asyncara\Async;
use Asyncara\Stream\{AsyncStream, Emitter};
Async::run(function (): void {
    $stream = AsyncStream::fromProducer(function (Emitter $emit): void {
        for ($i = 0; $i < 100; $i++) {
            $emit->emit(['id' => $i]);
            Async::sleep(0);
        }
    }, capacity: 4);
    $stream->consume(function (array $row): void {
        echo $row['id'], "\n";
    });
});
```

`consume` يغلق التدفق ويجمع المنتج حتى عند فشل callback. مع foreach استخدم finally و`close()`، خصوصاً عند break مبكر. لا تكرر استهلاك التدفق نفسه، ولا تفترض أن destructor سيعلّق وينتظر عاملاً؛ الإغلاق الصريح داخل runtime هو نقطة جمع التنظيف المضمونة في هذا العقد.

## ربط تدفقات دون تجميع كامل

```php
use Asyncara\Async;
use Asyncara\IO\Files;
Async::run(function (): void {
    $source = Files::readStream('/srv/data/input.bin');
    try {
        $bytes = Files::writeStream('/srv/data/output.bin', $source)->await();
        echo $bytes;
    } finally { $source->close(); }
});
```

دفق الملف افتراضياً يقرأ chunks من64KiB ويحتفظ بطابور صغير. هذا لا يعني أن إجمالي الذاكرة 64KiB: العامل والمنتج والمستهلك وPHP قد يحتفظ كل منها بقطعة. لا تخزّن كل chunk في مصفوفة ثم تتوقع أن يظل تطبيقك محدود الذاكرة.

## Context وليس globals

```php
use Asyncara\{Async, Context};
Async::run(function (): void {
    Context::set('requestId', 'r-123');
    $child = Async::spawn(fn() => Context::get('requestId'));
    echo $child->await();
    Context::isolated(function (): void {
        echo Context::get('requestId', 'none');
    }, ['requestId' => 'isolated']);
});
```

يأخذ الطفل نسخة سطحية من خريطة السياق عند spawn؛ الكائنات داخلها تظل مراجع مشتركة. `inheritContext:false` يبدأ دون سياق موروث. يعيد `isolated` الخريطة السابقة في finally. لا يعزل Context المتغيرات العامة أو static properties أو sessions أو إعدادات الامتدادات.

في Native Server يعاد ضبط Context بين طلبات الاتصال المعاد استخدامه، لكن العملية تبقى طويلة العمر. لا تضع بيانات مستخدم في static وتتوقع محوها بين الطلبات. استخدم Context لرقم التتبع وبيانات قصيرة، ومرّر كائنات الموارد بملكية واضحة.
