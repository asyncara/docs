<?php
declare(strict_types=1);
return ['digest' => static fn(string $text): string => hash('sha256', $text)];
