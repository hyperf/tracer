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
use Hyperf\Tracer\Annotation\Trace;
use Hyperf\Tracer\ExceptionAppender;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SwitchManager;
use OpenTracing\Tracer;
use Throwable;

/** @Aspect */
class TraceAnnotationAspect implements AroundInterface
{
    use SpanStarter;
    use ExceptionAppender;

    public array $classes = [];

    public array $annotations = [Trace::class];

    private Tracer $tracer;

    private SwitchManager $switchManager;

    public function __construct(Tracer $tracer, SwitchManager $switchManager)
    {
        /* @noinspection UnusedConstructorDependenciesInspection */
        $this->tracer = $tracer;
        $this->switchManager = $switchManager;
    }

    /**
     * @return mixed return the value from process method of ProceedingJoinPoint, or the value that you handled
     * @throws Exception
     * @throws Throwable
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $source = $proceedingJoinPoint->className . '::' . $proceedingJoinPoint->methodName;
        $metadata = $proceedingJoinPoint->getAnnotationMetadata();
        /** @var Trace $annotation */
        if ($annotation = $metadata->method[Trace::class] ?? null) {
            $name = $annotation->name;
            $tag = $annotation->tag;
        } else {
            $name = $source;
            $tag = 'source';
        }
        $span = $this->startSpan($name);
        $span->setTag($tag, $source);
        try {
            $result = $proceedingJoinPoint->process();
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
