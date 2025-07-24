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

namespace Hyperf\Tracer\Contract;

use OpenTracing\Tracer;

interface NamedFactoryInterface
{
    /**
     * Create the object from factory.
     */
    public function make(string $name): Tracer;
}
