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
namespace Hyperf\Tracer;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

class SpanTagManagerFactory
{
    public function __invoke(ContainerInterface $container): SpanTagManager
    {
        $config = $container->get(ConfigInterface::class);
        $spanTag = new SpanTagManager();
        $spanTag->apply($config->get('opentracing.tags', []));
        return $spanTag;
    }
}
