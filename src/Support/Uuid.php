<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf + OpenCodeCo
 *
 * @link     https://opencodeco.dev
 * @document https://hyperf.wiki
 * @contact  leo@opencodeco.dev
 * @license  https://github.com/opencodeco/hyperf-metric/blob/main/LICENSE
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
