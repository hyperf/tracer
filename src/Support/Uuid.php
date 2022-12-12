<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf + PicPay.
 *
 * @link     https://github.com/PicPay/hyperf-tracer
 * @document https://github.com/PicPay/hyperf-tracer/wiki
 * @contact  @PicPay
 * @license  https://github.com/PicPay/hyperf-tracer/blob/main/LICENSE
 */
namespace Hyperf\Tracer\Support;

use RuntimeException;

final class Uuid
{
    public static function asInt(string $uuid): int
    {
        $bin = hex2bin(str_replace(['{', '-', '}'], '', $uuid));
        [$left, $right] = str_split($bin, 8);
        $results = unpack('q', $left ^ $right);

        if (empty($results)) {
            throw new RuntimeException("Error unpacking given UUID {$uuid}");
        }

        return abs($results[1] ?? $results[0]);
    }
}
