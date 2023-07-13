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

use OpenTracing\Span;
use Throwable;

trait ExceptionAppender
{
    private function appendExceptionToSpan(Span $span, Throwable $exception): void
    {
        $span->setTag('error', true);

        $span->setTag('otel.status_code', 'ERROR');
        $span->setTag('otel.status_description', $exception->getMessage());

        $span->setTag($this->spanTagManager->get('exception', 'class'), $exception::class);
        $span->setTag($this->spanTagManager->get('exception', 'code'), $exception->getCode());
        $span->setTag($this->spanTagManager->get('exception', 'message'), $exception->getMessage());
        $span->setTag($this->spanTagManager->get('exception', 'stack_trace'), (string) $exception);
    }
}
