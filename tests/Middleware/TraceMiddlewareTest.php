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
namespace HyperfTest\Tracer\Middleware;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Tracer\Middleware\TraceMiddleware;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use OpenTracing\Tracer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 * @covers \Hyperf\Tracer\Middleware\TraceMiddleware
 */
final class TraceMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private Tracer $tracer;

    private SwitchManager $switchManager;

    private SpanTagManager $spanTagManager;

    private ConfigInterface $config;

    private TraceMiddleware $traceMiddleware;

    protected function setUp(): void
    {
        $this->tracer = Mockery::mock(Tracer::class);
        $this->switchManager = Mockery::spy(SwitchManager::class);
        $this->spanTagManager = Mockery::spy(SpanTagManager::class);
        $this->config = Mockery::mock(ConfigInterface::class);

        $this->config
            ->expects('get')
            ->with('opentracing')
            ->andReturn(['ignore_path' => '/^\/health$/']);

        $this->traceMiddleware = new TraceMiddleware(
            $this->tracer,
            $this->switchManager,
            $this->spanTagManager,
            $this->config,
        );

        parent::setUp();
    }

    public function testProcessIgnorePathIgnores(): void
    {
        $uri = Mockery::mock(UriInterface::class);
        $uri->expects('getPath')->andReturn('/health');

        $request = Mockery::mock(ServerRequestInterface::class);
        $request->expects('getUri')->andReturn($uri);

        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->expects('handle')->with($request);

        $this->tracer->expects('startSpan')->never();
        $this->traceMiddleware->process($request, $handler);
    }
}
