<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace HyperfTest\Tracer;

use Hyperf\Config\Config;
use Hyperf\Di\Container;
use Hyperf\Tracer\TracerFactory;
use Hyperf\Utils\ApplicationContext;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class TracerFactoryTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testJaegerFactory(): void
    {
        $config = new Config([
            'opentracing' => [
                'default' => 'jaeger',
                'enable' => [
                ],
                'tracer' => [
                    'zipkin' => [
                        'driver' => \Hyperf\Tracer\Adapter\ZipkinTracerFactory::class,
                        'app' => [
                            'name' => 'skeleton',
                            // Hyperf will detect the system info automatically as the value if ipv4, ipv6, port is null
                            'ipv4' => '127.0.0.1',
                            'ipv6' => null,
                            'port' => 9501,
                        ],
                        'options' => [
                        ],
                    ],
                    'jaeger' => [
                        'driver' => \Hyperf\Tracer\Adapter\JaegerTracerFactory::class,
                        'name' => 'skeleton',
                        'options' => [
                        ],
                    ],
                ],
            ],
        ]);
        $container = $this->getContainer($config);
        $factory = new TracerFactory();

        $this->assertInstanceOf(\Jaeger\Tracer::class, $factory($container));
    }

    protected function getContainer($config)
    {
        $container = Mockery::mock(Container::class);
        $client = Mockery::mock(\Hyperf\Tracer\Adapter\HttpClientFactory::class);

        $container->shouldReceive('get')
            ->with(\Hyperf\Tracer\Adapter\ZipkinTracerFactory::class)
            ->andReturn(new \Hyperf\Tracer\Adapter\ZipkinTracerFactory($config, $client));
        $container->shouldReceive('get')
            ->with(\Hyperf\Tracer\Adapter\JaegerTracerFactory::class)
            ->andReturn(new \Hyperf\Tracer\Adapter\JaegerTracerFactory($config));
        $container->shouldReceive('get')
            ->with(\Hyperf\Contract\ConfigInterface::class)
            ->andReturn($config);

        ApplicationContext::setContainer($container);

        return $container;
    }
}
