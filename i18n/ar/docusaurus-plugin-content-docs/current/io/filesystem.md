---
title: "الملفات والمقابض والاستبدال الذري"
---

تنفّذ أربع pthread workers عمليات الملفات الحاجبة خارج حلقة الأحداث. تنتظر Fibers بواسطة Futures؛ هذا Worker-backed Async وليس io_uring أو دليلاً على أن O_NONBLOCK يجعل القرص غير حاجب.

```php
use Asyncara\Async;
use Asyncara\IO\Files;
Async::run(function () {
    $path = sys_get_temp_dir().'/asyncara-example-'.bin2hex(random_bytes(8));
    try {
        Files::replaceAtomic($path, "hello\n", durable: true, permissions: 0600)->await();
        echo Files::read($path)->await();
    } finally { Async::shield(fn() => Files::delete($path)->await()); }
});
```

replaceAtomic يكتب مؤقتاً في المجلد نفسه ثم يعيد التسمية. الذرية تعني رؤية النسخة القديمة أو الجديدة؛ durability تعني طلب مزامنة الملف والمجلد أيضاً. فشل بعد rename قد يترك الملف الجديد مرئياً؛ افحص committed في FilesystemException. write يقتطع الملف وappend يضيف وcopy ليس نشرًا ذرياً تلقائياً.

## مقابض وتدفقات

```php
$file = Files::open('/srv/data/input.bin', 'rb')->await();
try {
    while (($chunk = $file->read(65536)->await()) !== '') {
        // Process the chunk.
    }
} finally { Async::shield(fn() => $file->close()->await()); }
```

القراءة قد تكون قصيرة، والنص الفارغ EOF. تتسلسل عمليات المقبض؛ close غير متزامن وآمن عند التكرار. النسخ والتسلسل ممنوعان. الإلغاء لا يتراجع عن كتابة بدأت فعلياً؛ يحتفظ العامل بالمقبض حتى إنهاء عمليته.

readStream يستخدم قطع64KiB افتراضياً وطابوراً محدوداً. أغلقه في finally حتى عند كسر الحلقة. writeStream يستهلك المصدر دون جمعه كله، لكنه يقتطع الوجهة وليس بديلاً ذرياً. لا تجمع القطع في نص واحد إن أردت ذاكرة محدودة.

mkdir/rmdir/entries/stat/rename/delete واجهات مستقلة؛ entries ليس walker تكرارياً غير محدود. exists لا يخفي أخطاء الصلاحيات كعدم وجود. استخدم مجلدات موثوقة؛ open_basedir وفحص المسار ليسا عزلاً ضد مهاجم يتحكم بمكونات المجلد. io_uring للملفات غير منفذ.
