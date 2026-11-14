---
title: "التثبيت والتحقق"
---

تحتاج إلى جزأين متطابقين: امتداد `asyncara.so` وملفات مكتبة PHP. الحزم الجاهزة تجمعهما مع مشغّل `asyncara`. لا يكفي نسخ ملف الامتداد وحده للحصول على `App` و`HttpClient` و`Async`.

## Linux: الحزم الجاهزة

الحزم المنشورة للإصدار الأول تستهدف Ubuntu24.04 وamd64 وPHP **8.3.36 أو 8.4.27 أو 8.5.12 NTS**، ببناء non-debug و64-bit. يرفض المثبّت عدم التطابق، بما في ذلك إصدار PHP الفرعي. تثبيت حزمة معمارية صحيحة لا يكفي إذا كانت libc أو تبعيات النظام مختلفة.

```bash
curl -fsSL https://github.com/asyncara/asyncara/releases/download/v0.10.0/install.sh | sh
asyncara --check
asyncara --example 127.0.0.1:8080
```

الأمر الأخير يشغّل مثالاً محلياً؛ افتح `/status`. يتطلب التنزيل المجهول أن يكون الإصدار متاحاً للعامة. للمستودع الخاص استخدم `gh auth login` ثم نزّل المثبّت عبر `gh release download v0.10.0 --repo asyncara/asyncara --pattern 'install.sh*'`، وتحقق باستخدام `sha256sum -c install.sh.sha256` ثم شغّل `sh install.sh`.

المثبّت مكتوب بـPOSIX shell وPHP؛ لا يحتاج Python أو Cargo أو phpize على جهاز المستخدم. يفحص SHA256 ومحتوى الحزمة، ثم يشغّل فحص التحميل قبل تفعيل الأمر. لا يعدّل `php.ini` الأصلي، ولا ينفذ sudo تلقائياً. عند اختيار مجلد أوامر خارج PATH يعرض التصدير المطلوب؛ لا يستطيع تغيير بيئة الصدفة الأم.

تحتاج أيضاً مكتبات النظام التي رُبط الامتداد بها: libcurl وSQLite وlibpq وOpenSSL وzlib، بالإضافة إلى PHP CLI. في Ubuntu يمكنك تثبيتها من مدير الحزم المعتاد، لكن وجود `php-cli` لا يضمن أنه الإصدار الفرعي الموافق لحزمتك. راجع نتيجة `php -v` قبل التثبيت.

```bash
ASYNCARA_PHP=/usr/bin/php8.4 sh install.sh --version 0.10.0
sh install.sh --check-platform
# حزمة موجودة محلياً مع ملف SHA256 المجاور:
sh install.sh --bundle asyncara-0.10.0-php8.4.27-nts-ubuntu24.04-x86_64.tar.gz
```

## Android: Termux، 64-bit فقط في الحزم الجديدة

حزمتا `aarch64` و`x86_64` تستهدفان PHP8.5.12 NTS وBionic. هما **preview من مصدر أحدث** من وسم 0.10.0، ومتاحان في صفحة الإصدار بأسماء تنتهي بـ`hyper-sql-preview.tar.gz`. لا تستخدمهما داخل بيئة GNU/Linux proot ذات glibc، ولا تستخدم حزمة Ubuntu داخل Termux الأصلي.

```bash
arch=$(dpkg --print-architecture)
case "$arch" in aarch64|x86_64) ;; *) echo 'لا توجد حزمة لهذه المعمارية'; exit 1;; esac
bundle="asyncara-0.10.0-android-$arch-php8.5.12-nts-hyper-sql-preview"
gh release download v0.10.0 --repo asyncara/asyncara --pattern "$bundle.tar.gz*"
sha256sum -c "$bundle.tar.gz.sha256"
pkg install php libcurl libsqlite openssl postgresql mariadb brotli zstd
php -n -r 'exit(PHP_VERSION === "8.5.12" && !PHP_ZTS && PHP_INT_SIZE === 8 ? 0 : 1);' || exit 1
test ! -e "$bundle" || exit 1
tar -xzf "$bundle.tar.gz"
export PATH="$PWD/$bundle:$PATH"
asyncara --check
```

مدير Termux قد يحدّث PHP؛ عند اختلاف الإصدار تحتاج حزمة مطابقة، لا تجاوز الفحص. المثبّت Linux أعلاه لا يختار حزم Android preview تلقائياً. اختبارات Android المستقلة أجريت في حاويات Bionic على معماريات مطابقة، وليست اعتماداً لكل جهاز أو سياسة SELinux. Windows و32-bit مؤجلان.

## Composer والتحميل التلقائي

المشغّل يستخدم `php -n` ويجهز مكتبة Asyncara. إذا كنت تشغّل PHP بنفسك، حمّل الامتداد المطابق ثم ملف `autoload.php` من توزيع Asyncara، أو استخدم Composer مع المكتبة وامتداد مثبت. اسم مكتبة PHP هو `asyncara/runtime`؛ لا تستبدله بافتراض أن `asyncara/asyncara` حزمة PIE مكتملة.

```php
<?php
require '/opt/asyncara/autoload.php';
Asyncara\Async::run(fn() => print("ready\n"));
```

```bash
php -n -d extension=/opt/asyncara/asyncara.so app.php
```

حزمة Linux تتضمن تبعيات PSR الإنتاجية. حزم Android preview تتضمن مكتبة Asyncara، ولكن تبعيات PSR الاختيارية تحتاج إعداد Composer منفصلاً. عند استخدام `php -n` لا تُحمّل امتداداتك الديناميكية المعتادة؛ مرّر خيارات `-d extension=...` التي يحتاجها تطبيقك، بعد التحقق من توافقها.

## البناء وPIE

البناء من المصدر متاح باستخدام SDK PHP مطابق وphpize وC11 وlibcurl وSQLite. ميزات PostgreSQL وMySQL وHyper تحتاج تبعياتها وأعلام configure المقابلة. اقرأ خيارات `ext/config.m4`، ولا تخلط SDK من PHP آخر. الحزمة الجاهزة هي المسار الأسهل للمستخدم.

الأمران `pie install asyncara/asyncara` و`pie install asyncara/asyncara:0.10.0` **ليسا مسار تثبيت كامل موثق النجاح حالياً**: metadata وحدها لا تثبت تثبيت مكتبة PHP والمشغّل والتحقق من SHA256. لا يقدّم المشروع بديلاً مزيفاً لأداة PIE.

بعد أي تثبيت أو ترقية شغّل `asyncara --check` ثم مثالاً صغيراً يفتح ملفاً واتصالاً. احتفظ بالإصدار السابق قبل نقل خدمة قائمة، وافحص [حل المشكلات](operations/troubleshooting.md) إذا رفض الامتداد التحميل.
