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

use Hyperf\Tracer\Support\GuzzleHeaderValidate;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class JaegerDecoderTest extends TestCase
{
    public function testTraceIdDecoder(): void
    {
        self::assertFalse(GuzzleHeaderValidate::isValidHeader('uberctx-40247e74-4e8ccd0c@dt', '1234'));
        self::assertTrue(GuzzleHeaderValidate::isValidHeader('uberctx-40247e74-4e8ccd0c', '1234'));
    }

    public function testSpanIdDecoder(): void
    {
        self::assertSame('255d1b261e0fcbc3', JaegerDecoder::spanIdDecoder('2692338002764483523'));
        self::assertSame('21c2405de0e90c56', JaegerDecoder::spanIdDecoder('21c2405de0e90c56'));
    }
}
