---
title: "المقاييس والتتبّع المحدود"
---

ابدأ بـRuntime::information لمعرفة القدرات الفعلية؛ اسم API لا يثبت backend. الملفات عمال، وHTTP قد يكون curl multi أو Hyper، وProcessPool عمليات منفصلة.

```php
echo json_encode(Asyncara\Runtime::information(), JSON_PRETTY_PRINT), "\n";
```

Runtime::diagnostics يعرض لقطة Scheduler الحالي أو السابق، وليس كل تخصيص خارجي أو كل عملية حاجبة. قياس slow task يحدد خطوة مفسر طويلة ولا يحل محل profiler شامل.

```php
use Asyncara\{Async, Trace};
Trace::enable(capacity: 1024, classes: Trace::WAIT | Trace::TIMER | Trace::SCHEDULER | Trace::MARK);
try {
    Async::run(function () { Async::sleep(0.01); Trace::mark(100); });
    echo json_encode(Trace::snapshot()), "\n";
} finally { Trace::disable(); }
```

فعّل خارج Async::run واستخرج بعد انتهائه. الحلقة محدودة وتحفظ أرقام تسلسل ووقتاً monotonic وmetadata رقمية، لا payload. الالتفاف ظاهر في العدادات ولا يعطي تاريخاً كاملاً. mark(freeze:true) يوقف الجمع.

dump ينشئ ملفاً جديداً ولا يستبدل القديم؛ writeTo يكتب في stream يملكه المستدعي. الاستخراج قد يحجب لذلك لا ينفذ أثناء dispatch، وفشل الكتابة خطأ صريح. احفظ التقارير في مجلد خاص.

تأخر wait قد يشمل جدولة المضيف؛ قارن المهلة المطلوبة والمدة الفعلية وتسلسل timer/readiness. نجاح بتتبّع ليس قبول endurance دون instrumentation، ولا يفسر بأثر رجعي إخفاق FPM التاريخي. أرسل versions وbackend ومثالاً منقحاً دون أسرار.

سعة الحلقة16..4096 حدثاً، والتصدير محدود4MiB مع ذاكرة PHP إضافية لتحويل JSON.
