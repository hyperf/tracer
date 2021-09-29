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
use Hyperf\Tracer\Adapter\JaegerTracerFactory;
use Hyperf\Tracer\Contract\NamedFactoryInterface;
use Hyperf\Tracer\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;

class TracerFactory
{
    /**
     * @var ConfigInterface
     */
    private $config;

    public function __invoke(ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
        $name = $this->config->get('opentracing.default');

        // v1.0 has no 'default' config. Fallback to v1.0 mode for backward compatibility.
        if (empty($name)) {
            $factory = $container->get(JaegerTracerFactory::class);
            return $factory->make('');
        }

        $driver = $this->config->get("opentracing.tracer.{$name}.driver");
        if (empty($driver)) {
            throw new InvalidArgumentException(
                sprintf('The tracing config [%s] doesn\'t contain a valid driver.', $name)
            );
        }

        $factory = $container->get($driver);

        if (! ($factory instanceof NamedFactoryInterface)) {
            throw new InvalidArgumentException(
                sprintf('The driver %s is not a valid factory.', $driver)
            );
        }

        return $factory->make($name);
    }
}
