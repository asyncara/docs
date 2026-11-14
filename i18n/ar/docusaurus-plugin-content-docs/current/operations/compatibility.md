---
title: "التوافق وحدود التنفيذ"
---

يستهدف الدليل عائلة API0.10. الحزمة المنشورة والتطوير اللاحق ليسا المصدر نفسه؛ راجع manifest والقدرات الفعلية.

| القدرة | التنفيذ |
|---|---|
| TCP/UDP | جاهزية غير حاجبة epoll/poll |
| PostgreSQL | libpq غير حاجب |
| MySQL/MariaDB | Connector/C start/continue، TLS المتحقق حالياً IP رقمي |
| Redis | TCP/RESP غير حاجب، دون Cluster/Sentinel كامل |
| HTTP | curl multi أو Hyper الاختياري |
| App | HTTP/1.1 وFibers، ليس خادم HTTP/2/3 |
| SQLite/Files/DNS | عمال أصليون |
| ProcessPool | عمليات متعددة |

Linux NTS PHP8.3/8.4/8.5 أساس الاختبارات. الحزم الجاهزة لإصدارات PHP ومعماريات وABI محددة، لا لكل توزيع. Android64 preview لـaarch64/x86_64 مع PHP8.5.12 NTS من مصدر أحدث؛ ليس اعتماداً لكل sandbox. اختبارات المالك السابقة منفصلة عن مصفوفة الحزم.

Windows و32bit مؤجلان. لا توجد مصفوفة حزم معتمدة هنا لـmacOS/FreeBSD. poll لا يثبت توافق OS. ZTS ليس اعتماداً لمفسرات متزامنة، وThreadPool وio_uring للملفات وkqueue وIOCP غير منفذة.

CLI وNative Server أولوية؛ توافق FPM باقٍ مع إخفاقات تاريخية موثقة وليس أولوية التطوير الحالية. Composer وPSR17/18 لا يعنيان اعتماد كل framework. hooks اختيارية لـsleep/usleep فقط؛ PDO/cURL/ملفات PHP لا تصبح async. الاختبارات القصيرة ليست شهادة24 ساعة. الإصدار EXPERIMENTAL.
