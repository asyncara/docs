---
title: "التثبيت"
---

ثبّت Asyncara، وتحقق من تحميلها، ثم شغّل تطبيقك الأول. تحتاج PHP CLI وحزمة تطابق نظام التشغيل والمعمارية وإصدار PHP ووضع NTS أو ZTS.

:::caution التنزيل غير متاح حالياً
روابط تنزيل مثبّت وحزم v0.10.0 العامة غير متاحة حالياً. تحتاج أوامر التنزيل أدناه إلى توفر الملفات في [صفحة الإصدار](https://github.com/asyncara/asyncara/releases/tag/v0.10.0). إذا كانت لديك حزمة مطابقة بالفعل، استخدم خطوات التثبيت دون اتصال أدناه.
:::

## 1. افحص إصدار PHP

```bash
php -v
php -r 'printf("PHP %s / %s / %d-bit\n", PHP_VERSION, PHP_ZTS ? "ZTS" : "NTS", PHP_INT_SIZE * 8);'
uname -m
```

تحتاج حزم Linux للإصدار v0.10.0 إلى **Ubuntu 24.04 ومعمارية x86_64 (amd64) وPHP 8.3.36 أو 8.4.27 أو 8.5.12 NTS** ببناء non-debug. اختر الحزمة المطابقة لإصدار PHP كاملاً، وراجع [التوافق](operations/compatibility.md) للمنصات الأخرى.

تحتاج PHP CLI وcurl وشهادات CA، بالإضافة إلى مكتبات libcurl وSQLite وlibpq وOpenSSL وzlib. من أسمائها في Ubuntu: `libcurl4t64` و`libsqlite3-0` و`libpq5` و`libssl3t64` و`zlib1g`.

## 2. ثبّت على Linux

عند إتاحة التنزيل، نفّذ:

```bash
curl -fsSL https://github.com/asyncara/asyncara/releases/download/v0.10.0/install.sh | sh
```

يختار المثبّت الحزمة المطابقة ويتحقق من SHA-256 ثم يثبت الملفات ويجهز أمر `asyncara`، دون بناء محلي أو تعديل `php.ini` الأصلي. إذا طبع تعليمة لإضافة مسار إلى PATH، طبّقها في الطرفية قبل المتابعة.

لاختيار مفسر PHP محدد أو استعمال ملفات نزّلتها مسبقاً، احفظ المثبّت أولاً:

```bash
curl -fSLo install.sh https://github.com/asyncara/asyncara/releases/download/v0.10.0/install.sh
curl -fSLo install.sh.sha256 https://github.com/asyncara/asyncara/releases/download/v0.10.0/install.sh.sha256
sha256sum -c install.sh.sha256
ASYNCARA_PHP=/usr/bin/php8.4 sh install.sh --version 0.10.0
```

للتثبيت دون اتصال، ضع الحزمة المطابقة وملف `.sha256` بجوار المثبّت المحفوظ، ثم نفّذ:

```bash
sh install.sh --bundle asyncara-0.10.0-php8.4.27-nts-ubuntu24.04-x86_64.tar.gz
```

يفحص `sh install.sh --check-platform` توافق النظام. إذا رفض المثبّت الحزمة، اختر حزمة مطابقة بدلاً من تجاوز الفحص.

## 3. تحقق من التثبيت

```bash
asyncara --check
asyncara --example 127.0.0.1:8080
```

يشغّل الأمر الثاني خادماً محلياً. من طرفية أخرى:

```bash
curl http://127.0.0.1:8080/status
```

أوقف المثال باستخدام Ctrl+C، ثم تابع إلى [تطبيقك الأول](quickstart.md).

## Android باستخدام Termux

تحتاج حزم Android إلى **Termux الأصلي وBionic ومعمارية aarch64 أو x86_64 وPHP 8.5.12 NTS**. اختر حزمة Android، لا حزمة Ubuntu أو حزمة لبيئة GNU/Linux proot. هذه الحزم تحمل علامة preview.

ثبّت الاعتماديات وافحص PHP:

```bash
pkg install php curl libcurl libsqlite openssl postgresql mariadb brotli zstd
php -n -r 'exit(PHP_VERSION === "8.5.12" && !PHP_ZTS && PHP_INT_SIZE === 8 ? 0 : 1);'
```

إذا فشل الفحص، توقف واختر حزمة تطابق PHP المثبت على جهازك. عند إتاحة التنزيل، نفّذ داخل مجلد جديد:

```bash
set -eu
arch=$(dpkg --print-architecture)
case "$arch" in aarch64|x86_64) ;; *) echo 'No package for this architecture'; exit 1;; esac
bundle="asyncara-0.10.0-android-$arch-php8.5.12-nts-hyper-sql-preview"
base=https://github.com/asyncara/asyncara/releases/download/v0.10.0
curl -fSLo "$bundle.tar.gz" "$base/$bundle.tar.gz"
curl -fSLo "$bundle.tar.gz.sha256" "$base/$bundle.tar.gz.sha256"
sha256sum -c "$bundle.tar.gz.sha256"
test ! -e "$bundle"
tar -xzf "$bundle.tar.gz"
export PATH="$PWD/$bundle:$PATH"
asyncara --check
```

احتفظ بالمجلد المستخرج وأضف مساره المطلق إلى إعداد PATH في الصدفة لاستعمال الأمر لاحقاً. مثبّت Linux لا يختار حزم Android تلقائياً.

## تشغيل تطبيقك

```bash
asyncara app.php
```

يحمّل المشغّل الامتداد ومكتبة PHP معاً. يستخدم `php -n`، فلا يحمّل `php.ini` المعتاد وامتداداته الديناميكية. إذا احتاج تطبيقك امتداداً إضافياً فمرره صراحةً، مثل `asyncara -d extension=mbstring app.php`، مع التأكد من توافقه مع المفسر.

عند تشغيل PHP مباشرة، حمّل `asyncara.so` بخيار `-d extension=/path/to/asyncara.so` واستدع autoloader المكتبة المرفق في سكربتك. نسخ الامتداد وحده لا يثبت `App` أو `Async` أو `HttpClient`.

حزم Linux تتضمن اعتماديات PSR التي يستخدمها محول HTTP. ثبّت اعتماديات تطبيقك الإضافية منفصلة. في Android تحتاج اعتماديات Composer منفصلة عند استعمال محول PSR الاختياري.

## طرق التثبيت الأخرى

PIE ليس طريقة تثبيت كاملة مدعومة للإصدار v0.10.0. استخدم حزمة مطابقة تضم الامتداد والمكتبة والمشغّل معاً.

البناء المخصص يحتاج headers وphpize وphp-config مطابقة لـPHP، ومترجم C11 ومكتبات تطوير libcurl وSQLite. الميزات الاختيارية تحتاج اعتمادياتها؛ اتبع تعليمات البناء في مستودع المصدر.

قبل ترقية تطبيق قائم، احتفظ بالتثبيت السابق وشغّل `asyncara --check` على الجديد، ثم افحص اتصالات تطبيقك. راجع [حل المشكلات](operations/troubleshooting.md) عند تعذر التحميل.
