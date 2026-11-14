---
title: "وصفة: تجميع خدمات HTTP متزامنة"
---

ابدأ الطلبات المستقلة قبل انتظارها، وحدد من يملك إلغاءها. استعمل origins من إعدادات موثوقة، لا URLs عشوائية من المستخدم كي لا تفتح SSRF.

```php
use Asyncara\Async;
use Asyncara\HTTP\HttpClient;
$client = new HttpClient(concurrency: 16, timeout: 5, connectTimeout: 2);
$jobs = ['catalog' => $client->get('https://catalog.example.net/summary'), 'stock' => $client->get('https://stock.example.net/summary')];
try {
    $responses = Async::gather($jobs);
    foreach ($responses as $name => $response) {
        if ($response->status !== 200) throw new RuntimeException('Upstream status');
        $result[$name] = $response->json();
    }
} finally {
    foreach ($jobs as $job) if (!$job->isCompleted()) $job->cancel();
    Async::shield(function () use ($jobs) {
        foreach ($jobs as $job) { try { $job->await(); } catch (Throwable) {} }
    });
}
```

نفّذ داخل Async::run أو handler، واستبدل النطاقات بخدمات فعلية. gather يحتفظ بالمفاتيح لكنه لا يلغي الأشقاء؛ finally يلغي ويجمع العمليات التي يملكها هذا النطاق. لا تطبق ذلك على عملية مشتركة يملكها مكوّن آخر.

timeout نقل يختلف عن HTTP500 الذي يحتاج تصنيف التطبيق. لا تعاود POST تلقائياً. للأعداد الكبيرة حد عدد Futures المنشأة أيضاً، لا التزامن فقط؛ استعمل batching أو Queue محدوداً. مثالَا server.php وhttp.php يوفران اختباراً محلياً لا يعتمد على الإنترنت.
