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

namespace Hyperf\Tracer;

use Hyperf\Context\ApplicationContext;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Engine\Exception\CoroutineDestroyedException;
use Hyperf\Context\Context;
use OpenTracing\Span;
use OpenTracing\Tracer;

use function Hyperf\Support\make;

class TracerContext
{
    public const TRACER = 'tracer.tracer';

    public const ROOT = 'tracer.root';

    public const TRACE_ID = 'tracer.trace_id';

    public static function setTracer(Tracer $tracer): Tracer
    {
        return Context::set(self::TRACER, $tracer);
    }

    public static function getTracer(): Tracer
    {
        return Context::getOrSet(self::TRACER, fn () => make(Tracer::class));
    }

    public static function setRoot(Span $root): Span
    {
        return Context::set(self::ROOT, $root);
    }

    public static function getRoot(): ?Span
    {
        return self::getTracerRoot(Coroutine::id());
    }

    public static function setTraceId(string $traceId): string
    {
        return Context::set(self::TRACE_ID, $traceId);
    }

    public static function getTraceId(): ?string
    {
        return Context::get(self::TRACE_ID) ?: null;
    }

    private static function getTracerRoot(int $coroutineId): ?Span
    {
        /** @var null|Span $root */
        $root = Context::get('tracer.root', null, $coroutineId);

        if ($root instanceof Span) {
            return $root;
        }

        if ($coroutineId <= 1) {
            return $root;
        }

        try {
            $parent_id = Coroutine::parentId($coroutineId);
        } catch (CoroutineDestroyedException $exception) {
            if (ApplicationContext::hasContainer() && ApplicationContext::getContainer()->has(StdoutLoggerInterface::class)) {
                ApplicationContext::getContainer()
                    ->get(StdoutLoggerInterface::class)
                    ->warning($exception->getMessage());
            }
            return null;
        }

        return self::getTracerRoot($parent_id);
    }
}
