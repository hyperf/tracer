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

use Exception;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Tracer\Contract\NamedFactoryInterface;
use Jaeger\Config;
use OpenTracing\Tracer;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

use const Jaeger\SAMPLER_TYPE_CONST;

class JaegerTracerFactory implements NamedFactoryInterface
{
    private ConfigInterface $config;

    private ?LoggerInterface $logger;

    private ?CacheItemPoolInterface $cache;

    private string $prefix;

    public function __construct(ConfigInterface $config, ?LoggerInterface $logger = null, ?CacheItemPoolInterface $cache = null)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    /**
     * @throws Exception
     */
    public function make(string $name): Tracer
    {
        $this->prefix = "opentracing.tracer.{$name}.";
        [$name, $options] = $this->parseConfig();

        $jaegerConfig = new Config(
            $options,
            $name,
            $this->logger,
            $this->cache
        );
        return $jaegerConfig->initializeTracer();
    }

    private function parseConfig(): array
    {
        return [
            $this->getConfig('name', 'skeleton'),
            $this->getConfig('options', [
                'sampler' => [
                    'type' => SAMPLER_TYPE_CONST,
                    'param' => true,
                ],
                'logging' => false,
            ]),
        ];
    }

    private function getConfig(string $key, $default)
    {
        return $this->config->get($this->prefix . $key, $default);
    }
}
