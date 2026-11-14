---
title: "أمثلة قابلة للتشغيل"
---

يحتوي [مجلد examples](https://github.com/asyncara/docs/tree/main/examples) على سكربتات PHP كاملة. نزّل مستودع التوثيق وافتح مجلده في الطرفية، ثم شغّلها بالمشغّل المثبت:

```bash
asyncara examples/tasks.php
```

يطبع المثال `{"a":"first","b":"second"}`. تبدأ المهمتان قبل جمع النتائج؛ قد ينتهي الانتظار الأقصر أولاً دون تغير مفاتيح النتيجة.

## اختر المثال

| الملف | الاستخدام | النتيجة |
|---|---|---|
| tasks.php | مهام متزامنة | JSON بقيم first/second |
| shared-waiter.php | إلغاء منتظر دون إلغاء العمل المشترك | waiter cancelled ثم kept |
| queue.php | طابور منتج ومستهلك محدود | 55 |
| streams.php | Context مع تدفق محدود | demo:0 حتى demo:4 |
| sqlite.php | ربط معاملات وsavepoints | ملاحظة واحدة محفوظة |
| files.php | استبدال ذري وقراءة محدودة | 90000 بايت |
| network.php | طلب ورد عبر TCP محلي | PING |
| processes.php مع jobs.php | وظائف مسجلة في عمليات مستقلة | قيمتا SHA-256 |

SQLite يستخدم قاعدة في الذاكرة، ومثال الملفات ينشئ ويحذف ملفاً مؤقتاً. مثال TCP يختار منفذاً محلياً متاحاً. يحتاج ProcessPool التثبيت الكامل الموضح في [دليل العمليات](../io/processes.md).

## الخادم وعميل HTTP

في الطرفية الأولى:

```bash
asyncara examples/server.php
```

في الثانية:

```bash
asyncara examples/http.php
```

يرسل العميل أربعة طلبات، ويعيد كل رد معرفاً وdelay_ms بقيمة10؛ قد يختلف ترتيب الاكتمال. أوقف الخادم بـCtrl+C.

لمنفذ مختلف، اضبط القيمتين بما يتطابق في الطرفيتين:

```bash
# Server terminal
ASYNCARA_LISTEN=127.0.0.1:8081 asyncara examples/server.php
```

```bash
# Client terminal
ASYNCARA_DEMO_URL=http://127.0.0.1:8081 asyncara examples/http.php
```

الأمثلة التي تستخدم example.net تحتاج عنوان خدمتك وشهاداتها وإعدادات الدخول. ضع هذه القيم في إعدادات التطبيق، ولا تحفظ أسرارك في مثال ترفعه إلى Git.
