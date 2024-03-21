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

    public function testSanitizeLicensePlatesStrings(): void
    {
        self::assertSame('/v1/test', Uri::sanitize('/v1/test'));
        self::assertSame('/v2/test/<LICENSE-PLATE>', Uri::sanitize('/v2/test/PET9D49'));
        self::assertSame('/v2/test/<LICENSE-PLATE>', Uri::sanitize('/v2/test/PET9349'));
        self::assertSame('/v3/test/<LICENSE-PLATE>/bar', Uri::sanitize('/v3/test/PET9D49/bar'));
        self::assertSame('/v3/test/<LICENSE-PLATE>/bar', Uri::sanitize('/v3/test/PET9349/bar'));
        self::assertSame('/v4/test/<LICENSE-PLATE>/bar/<LICENSE-PLATE>/', Uri::sanitize('/v4/test/PET9D49/bar/PET9D49/'));
        self::assertSame('/v4/test/<LICENSE-PLATE>/bar/<LICENSE-PLATE>/', Uri::sanitize('/v4/test/PET9349/bar/PET9349/'));
        self::assertSame('/v5/test/<LICENSE-PLATE>/<LICENSE-PLATE>', Uri::sanitize('/v5/test/PET9D49/PET9D49'));
        self::assertSame('/v5/test/<LICENSE-PLATE>/<LICENSE-PLATE>', Uri::sanitize('/v5/test/PET9349/PET9349'));
        self::assertSame('/v6/test/<LICENSE-PLATE>/<LICENSE-PLATE>/', Uri::sanitize('/v6/test/PET9D49/PET9D49/'));
        self::assertSame('/v6/test/<LICENSE-PLATE>/<LICENSE-PLATE>/', Uri::sanitize('/v6/test/PET9349/PET9349/'));
        self::assertSame('/v7/test/<LICENSE-PLATE>/<LICENSE-PLATE>/<LICENSE-PLATE>', Uri::sanitize('/v7/test/PET9D49/PET9D49/PET9D49'));
        self::assertSame('/v7/test/<LICENSE-PLATE>/<LICENSE-PLATE>/<LICENSE-PLATE>', Uri::sanitize('/v7/test/PET9349/PET9349/PET9349'));
        self::assertSame('/v8/test/<LICENSE-PLATE>/<LICENSE-PLATE>/<LICENSE-PLATE>/', Uri::sanitize('/v8/test/PET9D49/PET9D49/PET9D49/'));
        self::assertSame('/v8/test/<LICENSE-PLATE>/<LICENSE-PLATE>/<LICENSE-PLATE>/', Uri::sanitize('/v8/test/PET9349/PET9349/PET9349/'));
        self::assertSame('/v8/test/PET9349FOOBAR/foo/<LICENSE-PLATE>', Uri::sanitize('/v8/test/PET9349FOOBAR/foo/PET9349'));
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

    public function testClearUriOids(): void
    {
        $oid = '650229807612bba4984d1fc7';
        $oidLonger = 'ddb21302b3c66b5111bb99a907f783e2a29947f0';

        self::assertSame('/v1/test', Uri::sanitize('/v1/test'));
        self::assertSame('/v2/test/<OID>', Uri::sanitize("/v2/test/{$oid}"));
        self::assertSame('/v3/test/<OID>/bar', Uri::sanitize("/v3/test/{$oid}/bar"));
        self::assertSame('/v4/test/<OID>/bar/<OID>/', Uri::sanitize("/v4/test/{$oid}/bar/{$oid}/"));
        self::assertSame('/v5/test/<OID>/<OID>', Uri::sanitize("/v5/test/{$oid}/{$oid}"));
        self::assertSame('/v6/test/<OID>/<OID>/', Uri::sanitize("/v6/test/{$oid}/{$oid}/"));
        self::assertSame('/v7/test/<OID>/<OID>/<OID>', Uri::sanitize("/v7/test/{$oid}/{$oid}/{$oid}"));
        self::assertSame('/v8/test/<OID>/<OID>/<OID>/', Uri::sanitize("/v8/test/{$oid}/{$oid}/{$oid}/"));
        self::assertSame('/v2/token/<OID>/foo/<OID>', Uri::sanitize("/v2/token/{$oidLonger}/foo/{$oid}"));
        self::assertSame('/v3/token/<OID>/foo/<OID>/bar', Uri::sanitize("/v3/token/$oidLonger/foo/{$oid}/bar"));
    }
}
