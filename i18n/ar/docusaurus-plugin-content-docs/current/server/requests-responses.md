---
title: "الطلبات وJSON والاستجابات"
---

يستقبل المعالج `Asyncara\Server\Request`. الحقول method/target/path/body للقراءة فقط. `param` لمعاملات المسار، و`query` للاستعلام، و`header` للترويسات. القيم النصية لا تصبح أنواعاً موثوقة دون تحقق.

```php
use Asyncara\Server\{Request, Response};
$app->post('/notes', function (Request $request): Response {
    if (!str_starts_with(strtolower($request->header('content-type', '')), 'application/json')) return Response::text('Expected application/json', 415);
    $data = $request->json();
    if (!is_array($data) || !isset($data['title']) || !is_string($data['title']) || $data['title'] === '' || strlen($data['title']) > 200) {
        return Response::json(['error' => 'title must contain 1..200 bytes'], 422);
    }
    return Response::json(['title' => $data['title']], 201);
});
```

`json()` يستعمل محلل ext-json الأصلي. JSON التالف يرمي JsonException ويعيد App حالة400. JSON صالح قد يكون null أو قيمة مفردة؛ تحقّق من بنيته. الدالة لا تفرض Content-Type أو schema تلقائياً.

`query` يفك ترميز URL ويحتفظ بآخر قيمة للمفتاح المكرر؛ لا يبني مصفوفات PHP المتداخلة من الأقواس. `header` غير حساس لحالة الأحرف ويجمع القيم المكررة بفواصل، فلا تعتبره واجهة خاماً تحفظ كل تفاصيل الترويسات الأمنية.

## إنشاء الرد

`Response::json` يشفّر القيمة ويضع نوع JSON. `Response::text` يضع text/plain. للمحتوى المخصص:

```php
$app->get('/page', fn() => new Response('<p>Hello</p>', 200, ['content-type' => 'text/html; charset=utf-8']));
$app->delete('/notes/{id}', fn() => new Response('', 204));
```

الحالات من200 إلى599؛ الحالات204 و205 و304 لا تسمح بجسم. النقل يملك framing، فلا تضع ترويسات طول متعارضة. ترويسات Response الخادم مصفوفة ترابطية؛ ترويسات HttpClient قائمة سطور نصية، وليستا الواجهة نفسها.

الاستثناء غير المتوقع ينتج500 دون تسريب trace للعميل. أمسك أخطاء المجال وأعد رسائل مستقرة، وسجّل سياقاً لا يحتوي بيانات حساسة. بعد بدء بث الرد لا يمكن استبدال حالته؛ انظر [التدفقات](streaming.md).
