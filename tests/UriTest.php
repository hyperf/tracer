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
namespace HyperfTest\Tracer\Support;

use Hyperf\Tracer\Support\Uri;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class UriTest extends TestCase
{
    public function testSanitizeNumbers(): void
    {
        self::assertSame('/v1/test', Uri::sanitize('/v1/test'));
        self::assertSame('/v2/test/<NUMBER>', Uri::sanitize('/v2/test/123'));
        self::assertSame('/v3/test/<NUMBER>/bar', Uri::sanitize('/v3/test/123/bar'));
        self::assertSame('/v4/test/<NUMBER>/bar/<NUMBER>/', Uri::sanitize('/v4/test/123/bar/456/'));
        self::assertSame('/v5/test/<NUMBER>/<NUMBER>', Uri::sanitize('/v5/test/123/456'));
        self::assertSame('/v6/test/<NUMBER>/<NUMBER>/', Uri::sanitize('/v6/test/123/456/'));
        self::assertSame('/v7/test/<NUMBER>/<NUMBER>/<NUMBER>', Uri::sanitize('/v7/test/123/456/789'));
        self::assertSame('/v8/test/<NUMBER>/<NUMBER>/<NUMBER>/', Uri::sanitize('/v8/test/123/456/789/'));
    }

    public function testClearUriUuids(): void
    {
        $uuid = '123e4567-e89b-12d3-a456-426614174000';

        self::assertSame('/v1/test', Uri::sanitize('/v1/test'));
        self::assertSame('/v2/test/<UUID>', Uri::sanitize("/v2/test/{$uuid}"));
        self::assertSame('/v3/test/<UUID>/bar', Uri::sanitize("/v3/test/{$uuid}/bar"));
        self::assertSame('/v4/test/<UUID>/bar/<UUID>/', Uri::sanitize("/v4/test/{$uuid}/bar/{$uuid}/"));
        self::assertSame('/v5/test/<UUID>/<UUID>', Uri::sanitize("/v5/test/{$uuid}/{$uuid}"));
        self::assertSame('/v6/test/<UUID>/<UUID>/', Uri::sanitize("/v6/test/{$uuid}/{$uuid}/"));
        self::assertSame('/v7/test/<UUID>/<UUID>/<UUID>', Uri::sanitize("/v7/test/{$uuid}/{$uuid}/{$uuid}"));
        self::assertSame('/v8/test/<UUID>/<UUID>/<UUID>/', Uri::sanitize("/v8/test/{$uuid}/{$uuid}/{$uuid}/"));
    }
}
