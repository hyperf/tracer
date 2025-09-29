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

final class Uri
{
    public static function sanitize(string $uri, array $uriMask = []): string
    {
        return preg_replace(
            array_merge(
                array_keys($uriMask),
                [
                    '/\/(?<=\/)[ED]\d{8}\d{12}[0-9a-zA-Z]{11}(?=\/)?/',
                    '/\/(?<=\/)[a-f0-9]{40}(?=\/)?/i',
                    '/\/(?<=\/)([A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12})(?=\/)?/i',
                    '/\/(?<=\/)([A-Z]{3}-?\d[0-9A-Z]\d{2})(?=\/)?/i',
                    '/\/(?<=\/)[0-9A-F]{16,24}(?=\/)?/i',
                    '/\/(?<=\/)\d+(?=\/)?/',
                    '/\/(?<=\/)R[RN]\d{16}[A-Za-z0-9]{11}/',
                    '/\/([A-Z]{3,}(?:-[A-Z]{3,})*)-([A-Z0-9]{3}-\d+(?:_\d{3})?|[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}|[A-Za-z0-9]{8,})(?=\/|$)/i',
                ],
            ),
            array_merge(
                array_values($uriMask),
                [
                    '/<E2E-ID>',
                    '/<SHA1>',
                    '/<UUID>',
                    '/<LICENSE-PLATE>',
                    '/<OID>',
                    '/<NUMBER>',
                    '/<EXTERNAL-ID>',
                    '/<PREFIXED-ID>',
                ],
            ),
            '/' . ltrim($uri, '/'),
        );
    }
}
