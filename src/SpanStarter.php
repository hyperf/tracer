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

use Hyperf\Context\RequestContext;
use Hyperf\Rpc;
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
        $root = TracerContext::getRoot();
        $tracer = TracerContext::getTracer();
        if (! $root instanceof Span) {
            $request = RequestContext::getOrNull();
            if (! $request instanceof ServerRequestInterface) {
                // If the request object is absent, we are probably in a commandLine context.
                // Throwing an exception is unnecessary.
                $root = $tracer->startSpan($name, $option);
                $root->setTag(SPAN_KIND, $kind);
                TracerContext::setRoot($root);
                return $root;
            }
            $carrier = array_map(static fn ($header) => $header[0], $request->getHeaders());

            // Extracts the context from the HTTP headers.
            $spanContext = $tracer->extract(TEXT_MAP, $carrier);
            if ($spanContext) {
                $option['child_of'] = $spanContext;
            }
            $root = $tracer->startSpan($name, $option);
            $root->setTag(SPAN_KIND, $kind);
            TracerContext::setRoot($root);
            return $root;
        }
        $option['child_of'] = $root->getContext();
        $child = $tracer->startSpan($name, $option);
        $child->setTag(SPAN_KIND, $kind);
        return $child;
    }
}
