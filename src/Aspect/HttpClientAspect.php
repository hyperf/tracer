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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use Hyperf\Di\Aop\AroundInterface;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Hyperf\Tracer\ExceptionAppender;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\Support\Uri as SupportUri;
use Hyperf\Tracer\SwitchManager;
use OpenTracing\Span;
use OpenTracing\Tracer;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use const OpenTracing\Formats\TEXT_MAP;

class HttpClientAspect implements AroundInterface
{
    use SpanStarter;
    use ExceptionAppender;

    public array $classes = [Client::class . '::requestAsync'];

    public array $annotations = [];

    private Tracer $tracer;

    private SpanTagManager $spanTagManager;

    public function __construct(Tracer $tracer, private SwitchManager $switchManager, SpanTagManager $spanTagManager)
    {
        $this->tracer = $tracer;
        $this->spanTagManager = $spanTagManager;
    }

    /**
     * @return mixed return the value from process method of ProceedingJoinPoint, or the value that you handled
     * @throws Exception
     * @throws Throwable
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        if ($this->switchManager->isEnabled('guzzle') === false) {
            return $proceedingJoinPoint->process();
        }
        $options = $proceedingJoinPoint->arguments['keys']['options'];
        if (isset($options['no_aspect']) && $options['no_aspect'] === true) {
            return $proceedingJoinPoint->process();
        }
        /** @var Client $instance */
        $instance = $proceedingJoinPoint->getInstance();
        /** @var Uri $base_uri */
        $base_uri = $instance->getConfig('base_uri');
        $arguments = $proceedingJoinPoint->arguments;
        $method = strtoupper($arguments['keys']['method'] ?? '');
        $uri = $arguments['keys']['uri'] ?? '';
        $host = $base_uri === null ? (parse_url($uri, PHP_URL_HOST) ?? '') : $base_uri->getHost();
        $span = $this->startSpan(
            sprintf(
                '%s %s/%s',
                $method,
                rtrim((string) ($base_uri ?? ''), '/'),
                ltrim(parse_url(SupportUri::sanitize($uri), PHP_URL_PATH) ?? '', '/')
            )
        );

        $span->setTag('category', 'http');
        $span->setTag('component', 'GuzzleHttp');
        $span->setTag('kind', 'client');
        $span->setTag('source', $proceedingJoinPoint->className . '::' . $proceedingJoinPoint->methodName);
        if ($this->spanTagManager->has('http_client', 'http.url')) {
            $span->setTag($this->spanTagManager->get('http_client', 'http.url'), $uri);
        }
        if ($this->spanTagManager->has('http_client', 'http.host')) {
            $span->setTag($this->spanTagManager->get('http_client', 'http.host'), $host);
        }
        if ($this->spanTagManager->has('http_client', 'http.method')) {
            $span->setTag($this->spanTagManager->get('http_client', 'http.method'), $method);
        }
        $appendHeaders = [];
        // Injects the context into the wire
        $this->tracer->inject(
            $span->getContext(),
            TEXT_MAP,
            $appendHeaders
        );
        $options['headers'] = array_replace($options['headers'] ?? [], $appendHeaders);
        $proceedingJoinPoint->arguments['keys']['options'] = $options;

        $this->appendCustomSpan($span, $options);

        /** @var PromiseInterface $result */
        $result = $proceedingJoinPoint->process();
        $result->then(
            $this->onFullFilled($span, $options),
            $this->onRejected($span, $options)
        );
        $span->finish();

        return $result;
    }

    protected function appendCustomSpan(Span $span, array $options): void
    {
        // just for override
    }

    protected function appendCustomResponseSpan(Span $span, array $options, ?ResponseInterface $response): void
    {
        // just for override
    }

    private function onFullFilled(Span $span, array $options): callable
    {
        return function (ResponseInterface $response) use ($span, $options) {
            $span->setTag(
                $this->spanTagManager->get('http_client', 'http.status_code'),
                $response->getStatusCode()
            );
            $span->setTag('otel.status_code', 'OK');

            $this->appendCustomResponseSpan($span, $options, $response);
        };
    }

    private function onRejected(Span $span, array $options): callable
    {
        return function (RequestException $exception) use ($span, $options) {
            if ($this->switchManager->isEnabled('exception')) {
                $this->appendExceptionToSpan($span, $exception);
            }

            $span->setTag(
                $this->spanTagManager->get('http_client', 'http.status_code'),
                $exception->getResponse()->getStatusCode()
            );

            $this->appendCustomResponseSpan($span, $options, $exception->getResponse());

            return Create::rejectionFor($exception);
        };
    }
}
