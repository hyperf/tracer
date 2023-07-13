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
