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

final class GuzzleHeaderValidate
{
    public static function isValidHeader(string $headerName, string $headerValue): bool
    {
        return self::isValidHeaderName($headerName) && self::isValidHeaderValue($headerValue);
    }

    /**
     * caused by https://github.com/guzzle/psr7/blob/a70f5c95fb43bc83f07c9c948baa0dc1829bf201/src/MessageTrait.php#L229
     */
    public static function isValidHeaderName(string $headerName): bool
    {
        return (bool)preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/D', $headerName);
    }

    /**
     * caused by https://github.com/guzzle/psr7/blob/a70f5c95fb43bc83f07c9c948baa0dc1829bf201/src/MessageTrait.php#L259
     */
    public static function isValidHeaderValue(string $value): bool
    {
        return (bool)preg_match('/^[\x20\x09\x21-\x7E\x80-\xFF]*$/D', $value);
    }
}
