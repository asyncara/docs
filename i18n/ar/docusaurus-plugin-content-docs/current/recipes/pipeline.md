---
title: "وصفة: معالجة ملفات بذاكرة محدودة"
---

يفصل producer/consumer محدود القراءة عن المعالجة دون حفظ الملف كله. المثال يعالج قطعاً لا أسطراً؛ CSV وJSONL يحتاجان decoder تراكمياً وحداً لأطول سجل.

```php
use Asyncara\Async;
use Asyncara\IO\Files;
Async::run(function () {
    $source = Files::readStream('/srv/data/input.bin');
    $output = null;
    try {
        $output = Files::open('/srv/data/output.bin', 'wb')->await();
        foreach ($source as $chunk) $output->write(strtoupper($chunk))->await();
        $output->sync()->await();
    } finally {
        $source->close();
        if ($output !== null) Async::shield(fn() => $output->close()->await());
    }
});
```

انتظار الكتابة يضغط على producer فلا يقرأ بلا حد. التحويل الثقيل على CPU ما زال على خيط المفسر؛ استخدم ProcessPool عند ملاءمته دون طابور وظائف غير محدود.

الناتج هنا قد يبقى جزئياً عند الفشل. للنشر بعد النجاح استخدم File::stage ثم write ثم publish(durable:true) وأغلق في finally. الذرية والمزامنة الدائمة مفهومان مختلفان. الإلغاء لا يتراجع عن كتابة تمت. حد طول السجل أيضاً حتى لا يجمع parser سطراً بلا نهاية.
