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
namespace Hyperf\Tracer\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AroundInterface;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Hyperf\Redis\Redis;
use Hyperf\Tracer\ExceptionAppender;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;
use JsonException;
use OpenTracing\Tracer;
use Throwable;

/** @Aspect */
class RedisAspect implements AroundInterface
{
    use SpanStarter;
    use ExceptionAppender;

    public array $classes = [Redis::class . '::__call'];

    public array $annotations = [];

    private Tracer $tracer;

    private SwitchManager $switchManager;

    private SpanTagManager $spanTagManager;

    public function __construct(Tracer $tracer, SwitchManager $switchManager, SpanTagManager $spanTagManager)
    {
        /* @noinspection UnusedConstructorDependenciesInspection */
        $this->tracer = $tracer;
        $this->switchManager = $switchManager;
        $this->spanTagManager = $spanTagManager;
    }

    /**
     * @return mixed return the value from process method of ProceedingJoinPoint, or the value that you handled
     * @throws Exception
     * @throws JsonException
     * @throws Throwable
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        if ($this->switchManager->isEnabled('redis') === false) {
            return $proceedingJoinPoint->process();
        }

        $arguments = $proceedingJoinPoint->arguments['keys'];
        $span = $this->startSpan('redis::' . $arguments['name']);

        $span->setTag('category', 'datastore');
        $span->setTag('component', 'Redis');
        $span->setTag('kind', 'client');
        $span->setTag('db.system', 'redis');

        $span->setTag($this->spanTagManager->get('redis', 'arguments'), json_encode($arguments['arguments'], JSON_THROW_ON_ERROR));
        try {
            $result = $proceedingJoinPoint->process();
            $span->setTag($this->spanTagManager->get('redis', 'result'), json_encode($result, JSON_THROW_ON_ERROR));
            $span->setTag('otel.status_code', 'OK');
        } catch (Throwable $exception) {
            $this->switchManager->isEnabled('exception') && $this->appendExceptionToSpan($span, $exception);
            throw $exception;
        } finally {
            $span->finish();
        }
        return $result;
    }
}
