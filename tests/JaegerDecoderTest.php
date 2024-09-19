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

use Hyperf\Tracer\Support\JaegerDecoder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class JaegerDecoderTest extends TestCase
{
    public function testTraceIdDecoder(): void
    {
        self::assertSame('34876401113185248764011131852473', JaegerDecoder::traceIdDecoder('348764011131852473'));
        self::assertSame('a36b5a7dcb5f6a542976b89e952e69ab', JaegerDecoder::traceIdDecoder('a36b5a7dcb5f6a542976b89e952e69ab'));
    }

    public function testSpanIdDecoder(): void
    {
        self::assertSame('255d1b261e0fcbc3', JaegerDecoder::spanIdDecoder('2692338002764483523'));
        self::assertSame('21c2405de0e90c56', JaegerDecoder::spanIdDecoder('21c2405de0e90c56'));
    }
}
