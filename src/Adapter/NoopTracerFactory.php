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
namespace Hyperf\Tracer\Adapter;

use Hyperf\Tracer\Contract\NamedFactoryInterface;
use OpenTracing\NoopTracer;
use OpenTracing\Tracer;

class NoopTracerFactory implements NamedFactoryInterface
{
    public function make(string $name): Tracer
    {
        return new NoopTracer();
    }
}
