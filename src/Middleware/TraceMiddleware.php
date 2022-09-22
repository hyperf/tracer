<?php
/**
 * @noinspection UnknownInspectionInspection
 * @noinspection PhpUnused
 */

declare(strict_types=1);
/**
 * This file is part of Hyperf + PicPay.
 *
 * @link     https://github.com/PicPay/hyperf-tracer
 * @document https://github.com/PicPay/hyperf-tracer/wiki
 * @contact  @PicPay
 * @license  https://github.com/PicPay/hyperf-tracer/blob/main/LICENSE
 */
namespace Hyperf\Tracer\Middleware;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\Tracer\ExceptionAppender;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Coroutine;
use OpenTracing\Span;
use OpenTracing\Tracer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class TraceMiddleware implements MiddlewareInterface
{
    use SpanStarter;
    use ExceptionAppender;

    private SwitchManager $switchManager;

    private SpanTagManager $spanTagManager;

    private Tracer $tracer;

    private array $config;

    private string $sensitive_headers_regex = '/pass|auth|token|secret/i';

    public function __construct(
        Tracer $tracer,
        SwitchManager $switchManager,
        SpanTagManager $spanTagManager,
        ConfigInterface $config,
    ) {
        $this->tracer = $tracer;
        $this->switchManager = $switchManager;
        $this->spanTagManager = $spanTagManager;
        $this->config = $config->get('opentracing');
    }

    /**
     * Process an incoming server request.
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! empty($this->config['ignore_path']) && preg_match($this->config['ignore_path'], $request->getUri()->getPath())) {
            return $handler->handle($request);
        }

        $span = $this->buildSpan($request);

        defer(function () {
            try {
                $this->tracer->flush();
            } catch (Throwable $exception) {
                if (ApplicationContext::hasContainer() && ApplicationContext::getContainer()->has(StdoutLoggerInterface::class)) {
                    ApplicationContext::getContainer()
                        ->get(StdoutLoggerInterface::class)
                        ->error($exception->getMessage());
                }
            }
        });
        try {
            $response = $handler->handle($request);
            $span->setTag($this->spanTagManager->get('response', 'status_code'), $response->getStatusCode());
            $span->setTag('otel.status_code', 'OK');
        } catch (Throwable $exception) {
            $this->switchManager->isEnabled('exception') && $this->appendExceptionToSpan($span, $exception);
            if ($exception instanceof HttpException) {
                $span->setTag($this->spanTagManager->get('response', 'status_code'), $exception->getStatusCode());
            }
            throw $exception;
        } finally {
            $span->finish();
        }

        return $response;
    }

    protected function buildSpan(ServerRequestInterface $request): Span
    {
        $path = $this->getPath($request->getUri());

        $span = $this->startSpan($path);

        $span->setTag('kind', 'server');

        $span->setTag($this->spanTagManager->get('coroutine', 'id'), (string) Coroutine::id());
        $span->setTag($this->spanTagManager->get('request', 'path'), $path);
        $span->setTag($this->spanTagManager->get('request', 'method'), $request->getMethod());
        foreach ($request->getHeaders() as $key => $value) {
            if (preg_match($this->sensitive_headers_regex, $key)) {
                continue;
            }

            $span->setTag($this->spanTagManager->get('request', 'header') . '.' . $key, implode(', ', $value));
        }
        return $span;
    }

    protected function getPath(UriInterface $uri): string
    {
        return $uri->getPath();
    }
}
