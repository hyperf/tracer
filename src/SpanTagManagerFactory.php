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
namespace Hyperf\Tracer;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

class SpanTagManagerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get(ConfigInterface::class);
        $spanTag = new SpanTagManager();
        $spanTag->apply($config->get('opentracing.tags', []));
        return $spanTag;
    }
}
