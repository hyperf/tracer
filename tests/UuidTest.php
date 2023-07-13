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

use Hyperf\Tracer\Support\Uuid;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class UuidTest extends TestCase
{
    public function testAsInt(): void
    {
        self::assertSame(0, Uuid::asInt('00000000-0000-0000-0000-000000000000'));
        self::assertSame(9_189_094_052_915_056_584, Uuid::asInt('96b2bd47-a66e-434f-ae42-ea5703a93acf'));
        self::assertSame(2_817_854_019_230_525_549, Uuid::asInt('29adb00e-dc41-495a-ba3a-6bf46eb9ad82'));
        self::assertSame(7_856_230_658_856_071_025, Uuid::asInt('d88187ee-37dd-41ba-a9ae-21101f2d47d7'));
        self::assertSame(0, Uuid::asInt('ffffffff-ffff-ffff-ffff-ffffffffffff'));
    }
}
