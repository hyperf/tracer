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
namespace HyperfTest\Tracer;

use Exception;
use Hyperf\Config\Config;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Container;
use Hyperf\Tracer\Adapter\JaegerTracerFactory;
use Hyperf\Tracer\TracerFactory;
use Hyperf\Utils\ApplicationContext;
use Jaeger\Tracer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class TracerFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @throws Exception
     */
    public function testJaegerFactory(): void
    {
        $config = new Config([
            'opentracing' => [
                'default' => 'jaeger',
                'enable' => [
                ],
                'tracer' => [
                    'jaeger' => [
                        'driver' => JaegerTracerFactory::class,
                        'name' => 'skeleton',
                        'options' => [
                        ],
                    ],
                ],
            ],
        ]);
        $container = $this->getContainer($config);
        $factory = new TracerFactory();

        $this->assertInstanceOf(Tracer::class, $factory($container));
    }

    protected function getContainer($config)
    {
        $container = Mockery::mock(Container::class);

        $container->allows('get')
            ->with(JaegerTracerFactory::class)
            ->andReturns(new JaegerTracerFactory($config));

        $container->allows('get')
            ->with(ConfigInterface::class)
            ->andReturns($config);

        ApplicationContext::setContainer($container);

        return $container;
    }
}
