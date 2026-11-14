---
title: "TLS وProxy وحدود الثقة"
---

التحقق من TLS جزء من العقد. استخدم CA خاصة عند الحاجة، ولا تعطّل التحقق لمعالجة خطأ شهادة. ثقة origin وثقة Proxy وهوية العميل حدود مستقلة.

```php
use Asyncara\HTTP\HttpClient;
$http = new HttpClient(
    proxy: 'https://proxy.example:8443',
    caFile: '/etc/app/origin-ca.pem',
    proxyCaFile: '/etc/app/proxy-ca.pem',
    proxyCredentials: ['username' => getenv('PROXY_USER'), 'password' => getenv('PROXY_PASSWORD')],
    clientCertFile: '/etc/app/client-chain.pem',
    clientKeyFile: '/etc/app/client-key.pem',
);
```

تحقق من وجود متغيرات البيئة قبل إنشاء العميل. شهادة العميل ومفتاحه يجب أن يقدما معاً كملفات محلية. تُقرأ الهوية بعمال وتبقى snapshot لذلك العميل؛ أنشئ عميلاً جديداً بعد تبديلها. تبديل ملفين ليس snapshot ذرياً. المفاتيح المشفرة وPKCS#11 غير مدعومين بهذه الواجهة.

لا تضع الاعتماد في URL عند استعمال proxyCredentials ولا تضف Proxy-Authorization كترويسة تطبيق. Basic عبرProxy HTTP غير مشفر على تلك الوصلة حتى لو كان origin عبرCONNECT/TLS؛ استخدم HTTPS proxy لحمايتها.

## البروتوكولات المدعومة

توجد مسارات مختبرة لـHTTP/HTTPS forwarding وCONNECT. SOCKS5 يحلل اسم origin محلياً؛ SOCKS5h يفوضه إلى Proxy. Hyper لا يختار Proxy تلقائياً من متغيرات البيئة. لا يوجد ادعاء دعمSOCKS4 أوBIND/UDP أوDigest/NTLM/Negotiate أوكل الأجهزة التجارية.

## منع SSRF وتسريب الاعتماد

لا تمرر URL يقدمه مستخدم بلا سياسة للمخطط والمضيف والمنفذ والتحويلات. DNS rebinding وتعدد العناوين يجعلان فحص النص وحده غير كافٍ؛ طبّق سياسة egress في الشبكة للمدخل غير الموثوق. التحويل قد يغير الوجهة؛ الترويسات القياسية الحساسة تزال عبرorigin في المسار المملوك، لكن أسرارك الخاصة تحتاج قواعد تطبيق. mTLS يرفض تغييرorigin.

قد يظهر رفض مصادقة Proxy كحالةHTTP أوخطأ إنشاء tunnel حسب المسار. افحص الرد إن وجد، وأمسك خطأ النقل عند فشل الإنشاء، ولا تطبع كلمة المرور ضمن رسائل التشخيص.
