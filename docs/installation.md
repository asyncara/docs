---
title: "Installation"
---

Install Asyncara, check that it loads, then run your first application. You need PHP CLI and an Asyncara package that matches your operating system, processor architecture, PHP version and thread-safety mode.

:::caution Download availability
The public v0.10.0 installer and package download links are currently unavailable. The download commands below require those files to be available on the [release page](https://github.com/asyncara/asyncara/releases/tag/v0.10.0). If you already have a matching package, use the offline installation steps below.
:::

## 1. Check your PHP version

```bash
php -v
php -r 'printf("PHP %s / %s / %d-bit\n", PHP_VERSION, PHP_ZTS ? "ZTS" : "NTS", PHP_INT_SIZE * 8);'
uname -m
```

The Linux v0.10.0 packages require **Ubuntu 24.04, x86_64 (amd64), and PHP 8.3.36, 8.4.27 or 8.5.12 NTS**, with a non-debug PHP build. Choose the package for your exact PHP version. See [compatibility](operations/compatibility.md) for other platforms.

PHP CLI, curl and CA certificates are required. Linux packages also use the system libcurl, SQLite, libpq, OpenSSL and zlib libraries. On Ubuntu their package names include `libcurl4t64`, `libsqlite3-0`, `libpq5`, `libssl3t64` and `zlib1g`.

## 2. Install on Linux

When downloads are available, run:

```bash
curl -fsSL https://github.com/asyncara/asyncara/releases/download/v0.10.0/install.sh | sh
```

The installer selects the matching package, verifies its SHA-256 checksum, installs the runtime and creates the `asyncara` command. It does not build the extension locally or edit your original `php.ini`. If it prints a PATH instruction, apply that instruction in your shell before continuing.

To choose a PHP executable or install from files you already downloaded, save the installer first:

```bash
curl -fSLo install.sh https://github.com/asyncara/asyncara/releases/download/v0.10.0/install.sh
curl -fSLo install.sh.sha256 https://github.com/asyncara/asyncara/releases/download/v0.10.0/install.sh.sha256
sha256sum -c install.sh.sha256
ASYNCARA_PHP=/usr/bin/php8.4 sh install.sh --version 0.10.0
```

For offline installation, place the matching package and its `.sha256` file beside the saved installer, then run:

```bash
sh install.sh --bundle asyncara-0.10.0-php8.4.27-nts-ubuntu24.04-x86_64.tar.gz
```

Use `sh install.sh --check-platform` to inspect compatibility before installing. An incompatible package is rejected; select a matching package instead of bypassing the check.

## 3. Check the installation

```bash
asyncara --check
asyncara --example 127.0.0.1:8080
```

The second command starts a local example server. In another terminal, run:

```bash
curl http://127.0.0.1:8080/status
```

Stop the example with Ctrl+C. Continue with [your first application](quickstart.md) to create your own server and HTTP client.

## Android with Termux

Android packages require **native Termux, Bionic, aarch64 or x86_64, and PHP 8.5.12 NTS**. Use the Android package for Termux, not an Ubuntu package or a package intended for a GNU/Linux proot environment. These packages are marked preview.

Install dependencies and verify PHP before selecting a package:

```bash
pkg install php curl libcurl libsqlite openssl postgresql mariadb brotli zstd
php -n -r 'exit(PHP_VERSION === "8.5.12" && !PHP_ZTS && PHP_INT_SIZE === 8 ? 0 : 1);'
```

If the PHP check fails, stop and choose a package matching the PHP version installed on your device. When release downloads are available, run the following in a new directory:

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

Keep the extracted directory in place. To use the command in future terminals, add its absolute path to your shell's PATH configuration. The Linux installer does not select Android packages automatically.

## Running your application

```bash
asyncara app.php
```

The launcher loads both the extension and Asyncara's PHP library. It starts PHP with `-n`, so your normal `php.ini` and its dynamically enabled extensions are not loaded. If your application needs another extension, pass it explicitly, for example `asyncara -d extension=mbstring app.php`, using an extension compatible with that PHP executable.

For direct PHP invocation, load your installed `asyncara.so` with `-d extension=/path/to/asyncara.so` and require the accompanying runtime autoloader in your script. Copying only the extension does not install `App`, `Async` or `HttpClient`.

Linux packages include the PSR dependencies used by the HTTP adapter. Install your application's additional dependencies separately. Android packages require separate Composer dependencies when you use the optional PSR adapter.

## Other installation methods

PIE is not a supported complete installation method for v0.10.0. Use a matching package for the extension, PHP library and launcher together.

For a custom PHP build, source installation requires matching PHP headers, phpize and php-config, a C11 compiler, libcurl and SQLite development libraries. Optional PostgreSQL, MySQL/MariaDB and Hyper require their corresponding libraries. Follow the source repository's build instructions for those options.

Before upgrading an existing application, keep the previous installation, run `asyncara --check` with the new one, and exercise your application's database and network connections. See [troubleshooting](operations/troubleshooting.md) if loading fails.
