---
title: "الإلغاء والمهل وملكية الانتظار"
---

الإلغاء تعاوني. يلاحظ عند انتظار Future أو مؤقت أو جاهزية I/O أو قفل؛ لا يوقف PHP وسط حلقة حساب، ولا يعيد بيانات أُرسلت إلى خادم بعيد. قبل إضافة timeout حدد من يملك العملية ومن ينتظرها فقط.

## إلغاء منتظر لا يلغي العملية المشتركة

`$future->await($token)` يربط الإلغاء بانتظار هذا المستهلك. إذا أُلغي المنتظر تظل العملية مملوكة لصاحبها ويمكن لمنتظر آخر استلام النتيجة. أما `Async::await($future)` فهو انتظار **عملية يملكها النطاق الحالي**؛ إذا خرج قبل اكتمالها يطلب إلغاءها ويجمعها. لا تستخدمه عشوائياً لعملية مشتركة بين مستهلكين.

```php
use Asyncara\{Async, CancellationSource};
use Asyncara\Exception\CancelledException;
Async::run(function (): void {
    $operation = Async::spawn(function (): string { Async::sleep(0.02); return 'result'; });
    $source = new CancellationSource();
    $waiter = Async::spawn(fn() => $operation->await($source->token));
    $source->cancel();
    try { $waiter->await(); } catch (CancelledException) { /* هذا الانتظار فقط */ }
    echo $operation->await();
});
```

`Task::cancel()` يطلب إلغاء المهمة. `Future::cancel()` يفعّل canceller الخاص بالعملية إن وجد؛ ليس كل Future له عملية قابلة للإيقاف. نجاح طلب الإلغاء لا يعني وصول إقرار من PostgreSQL أو MySQL بأن الاستعلام توقف على الخادم.

## مهلة تملك عملاً محدوداً

```php
use Asyncara\Async;
use Asyncara\Exception\TimeoutException;
Async::run(function (): void {
    try {
        Async::timeout(0.05, function (): void { Async::sleep(1); });
    } catch (TimeoutException) {
        echo "deadline exceeded\n";
    }
});
```

كل مدة هنا بالثواني. الاستثناء هو مساعد `Asyncara::sleep(int)` العام الذي يقبل **مللي ثانية**؛ لتقليل اللبس استخدم `Asyncara\Async::sleep(float)` في أمثلتك. `Async::timeout` يلغي طفله ويجمع cleanup، لذلك قد ينتهي بعد الموعد إذا كان التنظيف أو syscall في عامل غير قابل للإيقاف فوراً. لا تعامل timeout كضمان لحد أقصى صارم لزمن الرجوع.

مهلة HttpClient تشمل نموذج النقل الموثق لديه، وليست بديلاً عاماً لمهلة انتظار دورك في كل pool. ضع `Async::timeout` حول العملية الكاملة إذا أردت تضمين انتظار القرض أو حصة التزامن. استخدم مهلة اتصال منفصلة لضبط إنشاء اتصال HTTP دون خلطها بمهلة إنتاج جسم الرفع.

## التنظيف المحمي

`shield` يؤخر إلغاء المهمة الافتراضي أثناء التنظيف. اجعله قصيراً ومحدوداً. `using` يحمي الاكتساب حتى تتحدد ملكية المورد، ثم يحرره في النجاح والفشل والإلغاء:

```php
use Asyncara\Async;
use Asyncara\IO\{File, Files};
Async::run(fn() => Async::using(
    fn() => Files::open('/tmp/data.txt', 'w'),
    fn(File $file) => $file->write('saved')->await(),
    fn(File $file) => $file->close(),
));
```

إلغاء كتابة ملف لا يعكس bytes سبق أن كتبها العامل. إلغاء HTTP لا يلغي تأثير POST وصل إلى الخادم. عند تكرار طلب بعد مهلة استخدم سياسة idempotency على مستوى التطبيق؛ لا تعِد المحاولة تلقائياً لعملية ذات أثر جانبي.

للمهام الدورية، احتفظ بـTask المعادة من `repeat` وألغها ثم اجمعها في finally. وللمؤقت الواحد احتفظ بـTimer المعادة من `callLater` واستدع `cancel` إذا لم تعد تحتاجها. تركها حية قد يطيل عمر التشغيل.
