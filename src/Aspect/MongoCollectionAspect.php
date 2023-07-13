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
namespace Hyperf\Tracer\Aspect;

use Hyperf\Di\Aop\AroundInterface;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\GoTask\MongoClient\Collection;
use Hyperf\Tracer\ExceptionAppender;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;
use OpenTracing\Tracer;
use ReflectionProperty;
use Throwable;

use const OpenTracing\Formats\TEXT_MAP;

class MongoCollectionAspect implements AroundInterface
{
    use SpanStarter;
    use ExceptionAppender;

    public array $classes = [
        Collection::class,
    ];

    public array $annotations = [];

    protected array $ignoredMethods = [
        'makePayload',
    ];

    protected Tracer $tracer;

    protected SpanTagManager $spanTagManager;

    public function __construct(
        Tracer $tracer,
        protected SwitchManager $switchManager,
        SpanTagManager $spanTagManager,
    ) {
        $this->tracer = $tracer;
        $this->spanTagManager = $spanTagManager;
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        if ($this->switchManager->isEnabled('mongo') === false) {
            return $proceedingJoinPoint->process();
        }

        if (in_array($proceedingJoinPoint->methodName, $this->ignoredMethods)) {
            return $proceedingJoinPoint->process();
        }

        $collectionName = $this->getCollectionName($proceedingJoinPoint);
        $method = $proceedingJoinPoint->methodName;
        $span = $this->startSpan(
            sprintf(
                'Mongo::%s on %s',
                $method,
                $collectionName
            )
        );

        $span->setTag('category', 'datastore');
        $span->setTag('kind', 'client');
        $span->setTag('component', 'MongoDB');
        $span->setTag('db.system', 'mongodb');
        $span->setTag('source', $proceedingJoinPoint->className . '::' . $proceedingJoinPoint->methodName);

        if ($this->spanTagManager->has('mongo', 'collection')) {
            $span->setTag($this->spanTagManager->get('mongo', 'collection'), $collectionName);
        }

        if ($this->spanTagManager->has('mongo', 'method')) {
            $span->setTag($this->spanTagManager->get('mongo', 'method'), $method);
        }

        $appendHeaders = [];
        $this->tracer->inject(
            $span->getContext(),
            TEXT_MAP,
            $appendHeaders
        );

        try {
            $result = $proceedingJoinPoint->process();
            $span->setTag('otel.status_code', 'OK');
        } catch (Throwable $exception) {
            if ($this->switchManager->isEnabled('exception')) {
                $this->appendExceptionToSpan($span, $exception);
            }
            throw $exception;
        } finally {
            $span->finish();
        }

        return $result;
    }

    private function getCollectionName(ProceedingJoinPoint $proceedingJoinPoint): string
    {
        /** @var Collection $collection */
        $collection = $proceedingJoinPoint->getInstance();

        $property = new ReflectionProperty(Collection::class, 'collection');
        $property->setAccessible(true);

        return $property->getValue($collection);
    }
}
