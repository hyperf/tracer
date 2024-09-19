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

final class JaegerDecoder
{
    /**
     * caused by https://github.com/jonahgeorge/jaeger-client-php/blob/39e35bc3168da12cf596038cd1332e700b5131e9/src/Jaeger/Mapper/SpanToJaegerMapper.php#L181
     */
    public static function traceIdDecoder(string $traceId): string
    {
        if (strlen($traceId) == 32 || !is_numeric($traceId)) {
            return $traceId;
        }

        return substr($traceId, 0, 16) . substr($traceId, -16, 16);
    }

    public static function spanIdDecoder(string $spanId): string
    {
        if (!is_numeric($spanId) || strlen($spanId) == 16) {
            return $spanId;
        }
        return strtolower(dechex((int)$spanId));
    }
}
