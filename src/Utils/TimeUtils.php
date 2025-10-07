<?php
declare(strict_types=1);

namespace App\Utils;

final class TimeUtils {
    public static function nowUtcIso(): string {
        return gmdate('Y-m-d\TH:i:s\Z');
    }
    public static function nowUnix(): int {
        return time();
    }
}
