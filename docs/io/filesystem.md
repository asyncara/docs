---
title: "Files, handles and atomic replacement"
---

Asyncara's regular-file APIs run blocking filesystem calls in four native pthread workers. Their Futures suspend the calling Fiber. This is worker-backed async, not io_uring or O_NONBLOCK disk I/O. Use Files for complete operations and File for an explicitly owned handle.

## Small files and atomic visibility

```php
use Asyncara\Async;
use Asyncara\IO\Files;
Async::run(function () {
    $path = sys_get_temp_dir().'/asyncara-example-'.bin2hex(random_bytes(8));
    try {
        Files::replaceAtomic($path, "hello\n", durable: true, permissions: 0600)->await();
        echo Files::read($path)->await();
        $stat = Files::stat($path)->await();
    } finally {
        Async::shield(fn() => Files::delete($path)->await());
    }
});
```

Atomic replacement stages in the destination directory and renames into place. Readers see the old file or the new file, not a partially rewritten destination. Durability is separate: durable mode also requests file/directory synchronization. A failure after rename can mean the new file is visible but durable synchronization failed; inspect FilesystemException's committed state instead of assuming no change occurred.

`write` truncates, `append` appends, and `copy` is not automatically an atomic publish operation. `exists` distinguishes missing paths from other failures: permission and I/O errors should not be treated as “does not exist.” Directory APIs include mkdir/rmdir/entries; entries are bounded buffered results, not an unlimited recursive walker.

## Handle ownership and partial reads

```php
$file = Files::open('/srv/data/input.bin', 'rb')->await();
try {
    while (($chunk = $file->read(65536)->await()) !== '') {
        // Process a possibly short chunk. Empty string is EOF.
    }
    $file->seek(0)->await();
} finally { Asyncara\Async::shield(fn() => $file->close()->await()); }
```

Operations on a handle are serialized. Writes complete their internal partial-write loop or fail; an error can still leave bytes written. `sync` asks the OS to synchronize, not just flush a PHP buffer. Close is asynchronous and idempotent. Cloning/serialization are rejected because a second owner of the same native handle would be unsafe. An outstanding worker operation retains the handle until collection; cancelling a waiter cannot undo an already executing write.

## Large files and backpressure

```php
$stream = Files::readStream('/srv/data/input.bin');
try {
    foreach ($stream as $chunk) {
        // Await downstream work here; upstream buffering stays bounded.
    }
} finally { $stream->close(); }
```

The default chunk is 64 KiB with a bounded producer queue. `writeStream($path, $iterable)` consumes chunks and closes an owned source; it truncates the destination and is not an atomic-write shortcut. Always close early-abandoned streams. Avoid concatenating every chunk if bounded memory is the goal.

Path checks and open_basedir admission do not create a security sandbox against an attacker who controls directory components. Use trusted directories, validate application filenames, and avoid traversal/symlink assumptions. Timeouts cancel waiting; a native syscall already in progress may need to drain during cleanup. io_uring filesystem support is not implemented.
