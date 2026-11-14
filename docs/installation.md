---
title: "Installation and verification"
---

An installation needs both the native extension and the PHP library. The prebuilt bundles contain them and an `asyncara` launcher. Copying only `asyncara.so` does not install `App`, `Async` or the HTTP client classes.

## Linux prebuilt bundles

The initial packages target **Ubuntu 24.04 amd 64, PHP 8.3.36, 8.4.27 or 8.5.12 NTS**, non-debug, 64-bit. Match the exact PHP patch, architecture and userspace. A correct CPU architecture alone does not establish libc or dependency compatibility.

```bash
curl -fsSL https://github.com/asyncara/asyncara/releases/download/v0.10.0/install.sh | sh
asyncara --check
asyncara --example 127.0.0.1:8080
```

Open `/status` on the example server. Anonymous download requires a publicly accessible Release. For a private repository, authenticate with GitHub CLI, download `install.sh` and its checksum using the commands below, then run the installer. The installer can use authenticated `gh` for private binary downloads.

```bash
gh auth login --hostname github.com
gh release download v0.10.0 --repo asyncara/asyncara --pattern 'install.sh*'
sha256sum -c install.sh.sha256
sh install.sh
```

The bootstrap uses POSIX shell and PHP, without Python, Cargo, phpize or Docker on the application machine. It verifies the archive and manifest, checks compatibility, and runs installation checks before activating the launcher. It does not edit your original php.ini, run sudo, or change the parent shell's PATH. Follow its printed PATH instruction if no writable PATH directory is available.

Install the runtime libraries through your normal package policy: PHP CLI, libcurl, SQLite, libpq, OpenSSL and zlib, plus curl/CA certificates for download. On Ubuntu the package names include `php-cli`, `libcurl4t64`, `libsqlite3-0`, `libpq5`, `libssl3t64` and `zlib1g`. Your package source may supply a different PHP patch; check `php -v` instead of assuming compatibility.

```bash
ASYNCARA_PHP=/usr/bin/php8.4 sh install.sh --version 0.10.0
sh install.sh --check-platform
# Offline: keep the matching .sha256 beside the archive.
sh install.sh --bundle asyncara-0.10.0-php8.4.27-nts-ubuntu24.04-x86_64.tar.gz
```

## Android: native Termux, 64-bit previews

The `aarch64` and `x86_64` packages target PHP 8.5.12 NTS and Bionic. They are **later-source previews**, identified by `hyper-sql-preview` in their names, attached to the 0.10.0 Release. They are not interchangeable with Ubuntu binaries or a GNU/Linux proot userspace.

```bash
arch=$(dpkg --print-architecture)
case "$arch" in aarch64|x86_64) ;; *) echo 'No validated package for this architecture'; exit 1;; esac
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

Termux repositories can advance PHP: a newer patch needs a matching bundle, not a bypass. The Linux shell installer does not yet automatically select these Android previews. Independent tests used native Bionic containers on matching architectures; they do not certify every physical device, Android version or SELinux policy. Windows and new 32-bit builds are deferred.

## Autoloading and application extensions

The launcher supplies the Asyncara autoloader and runs `php -n`. Your original ini files and dynamically enabled extensions are not loaded. Pass explicit `-d extension=...` options for application extensions after checking compatibility. To launch PHP yourself:

```bash
php -n -d extension=/opt/asyncara/asyncara.so app.php
```

```php
<?php
require '/opt/asyncara/autoload.php';
Asyncara\Async::run(fn() => print("ready\n"));
```

The library's Composer name is `asyncara/runtime`. Linux bundles include production PSR dependencies. Android previews include Asyncara classes but optional PSR integration needs its dependencies separately. A Composer autoloader does not replace the native extension.

## Source builds and PIE

Source builds require a matching PHP SDK, phpize, C 11, libcurl and SQLite. libpq, Connector/C and Rust/Hyper are optional configure features with additional dependencies. Inspect `ext/config.m4` and use the same PHP patch for headers and runtime; do not reuse a module built for a different SDK.

`pie install asyncara/asyncara` and `pie install asyncara/asyncara:0.10.0` are **not currently a verified complete installation path**. Prepared metadata alone does not deliver the PHP runtime, launcher and archive verification required by this package. The project does not replace upstream PIE with a lookalike executable.

After every installation or upgrade, run `asyncara --check` and a small application using the capabilities you need. Keep the previous version until checks pass. See [troubleshooting](operations/troubleshooting.md) for loader and dependency errors.
