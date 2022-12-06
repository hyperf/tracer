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
namespace Hyperf\Tracer;

use OpenTracing\Span;
use Throwable;

use function get_class;

trait ExceptionAppender
{
    private function appendExceptionToSpan(Span $span, Throwable $exception): void
    {
        $span->setTag('error', true);

        $span->setTag('otel.status_code', 'ERROR');
        $span->setTag('otel.status_description', $exception->getMessage());

        $span->setTag($this->spanTagManager->get('exception', 'class'), get_class($exception));
        $span->setTag($this->spanTagManager->get('exception', 'code'), $exception->getCode());
        $span->setTag($this->spanTagManager->get('exception', 'message'), $exception->getMessage());
        $span->setTag($this->spanTagManager->get('exception', 'stack_trace'), (string) $exception);
    }
}
