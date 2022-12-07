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
namespace HyperfTest\Tracer\Aspect;

use Hyperf\Tracer\Aspect\HttpClientAspect;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use OpenTracing\Tracer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class HttpClientAspectTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testClearUri(): void
    {
        $aspect = new HttpClientAspect(
            Mockery::spy(Tracer::class),
            Mockery::spy(SwitchManager::class),
            Mockery::spy(SpanTagManager::class),
        );

        self::assertSame('/v1/test', $aspect->clearUri('/v1/test'));
        self::assertSame('/v2/test/<NUMBER>', $aspect->clearUri('/v2/test/123'));
        self::assertSame('/v3/test/<NUMBER>/bar', $aspect->clearUri('/v3/test/123/bar'));
        self::assertSame('/v4/test/<NUMBER>/bar/<NUMBER>/', $aspect->clearUri('/v4/test/123/bar/456/'));
    }
}
