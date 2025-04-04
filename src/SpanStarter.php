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
namespace Hyperf\Tracer;

use Hyperf\Context\ApplicationContext;
use Hyperf\Context\Context;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Engine\Exception\CoroutineDestroyedException;
use Hyperf\Tracer\Support\Uuid;
use OpenTracing\Span;
use Psr\Http\Message\ServerRequestInterface;

use const OpenTracing\Formats\TEXT_MAP;
use const OpenTracing\Tags\SPAN_KIND;
use const OpenTracing\Tags\SPAN_KIND_RPC_CLIENT;

trait SpanStarter
{
    /**
     * Helper method to start a span while setting context.
     */
    protected function startSpan(
        string $name,
        array $option = [],
        string $kind = SPAN_KIND_RPC_CLIENT
    ): Span {
        $root = $this->getTracerRoot(Coroutine::id());
        if (! $root instanceof Span) {
            /** @var ServerRequestInterface $request */
            $request = Context::get(ServerRequestInterface::class);
            if (! $request instanceof ServerRequestInterface) {
                // If the request object is absent, we are probably in a commandline context.
                // Throwing an exception is unnecessary.
                $root = $this->tracer->startSpan($name, $option);
                $root->setTag(SPAN_KIND, $kind);
                Context::set('tracer.root', $root);
                return $root;
            }
            $carrier = array_map(static fn ($header) => $header[0], $request->getHeaders());

            // Extracts the context from the HTTP headers.
            $spanContext = $this->tracer->extract(TEXT_MAP, $carrier);
            if ($spanContext) {
                $option['child_of'] = $spanContext;
            }
            $root = $this->tracer->startSpan($name, $option);
            $root->setTag(SPAN_KIND, $kind);

            if ($spanContext === null && ! empty($correlationId = $request->getHeaderLine('X-Request-ID'))) {
                $root->getContext()->setTraceId((string) Uuid::asInt($correlationId));
            }

            Context::set('tracer.root', $root);
            return $root;
        }
        $option['child_of'] = $root->getContext();
        $child = $this->tracer->startSpan($name, $option);
        $child->setTag(SPAN_KIND, $kind);
        $child->setTag('parent.name', $root->getOperationName());
        return $child;
    }

    public function getTracerRoot(int $coroutineId): ?Span
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

        return $this->getTracerRoot($parent_id);
    }
}
