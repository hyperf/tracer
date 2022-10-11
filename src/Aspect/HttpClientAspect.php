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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AroundInterface;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Hyperf\Tracer\ExceptionAppender;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;
use OpenTracing\Span;
use OpenTracing\Tracer;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use const OpenTracing\Formats\TEXT_MAP;

/** @Aspect */
class HttpClientAspect implements AroundInterface
{
    use SpanStarter;
    use ExceptionAppender;

    public array $classes = [Client::class . '::requestAsync'];

    public array $annotations = [];

    private Tracer $tracer;

    private SwitchManager $switchManager;

    private SpanTagManager $spanTagManager;

    public function __construct(Tracer $tracer, SwitchManager $switchManager, SpanTagManager $spanTagManager)
    {
        $this->tracer = $tracer;
        $this->switchManager = $switchManager;
        $this->spanTagManager = $spanTagManager;
    }

    /**
     * @throws Exception
     * @throws Throwable
     * @return mixed return the value from process method of ProceedingJoinPoint, or the value that you handled
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
                ltrim(parse_url($this->clearUri($uri), PHP_URL_PATH) ?? '', '/')
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

        /** @var PromiseInterface $result */
        $result = $proceedingJoinPoint->process();
        $result->then(
            $this->onFullFilled($span),
            $this->onRejected($span)
        );
        $span->finish();

        return $result;
    }

    protected function clearUri(string $uri): string
    {
        return preg_replace(
            '/\/[0-9]+\//',
            '/****/',
            $uri
        );
    }

    private function onFullFilled(Span $span): callable
    {
        return function (ResponseInterface $response) use ($span) {
            $span->setTag(
                $this->spanTagManager->get('http_client', 'http.status_code'),
                $response->getStatusCode()
            );
            $span->setTag('otel.status_code', 'OK');
        };
    }

    private function onRejected(Span $span): callable
    {
        return function (RequestException $exception) use ($span) {
            if ($this->switchManager->isEnabled('exception')) {
                $this->appendExceptionToSpan($span, $exception);
            }

            $span->setTag(
                $this->spanTagManager->get('http_client', 'http.status_code'),
                $exception->getResponse()->getStatusCode()
            );

            return Create::rejectionFor($exception);
        };
    }
}
