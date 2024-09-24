<?php

/**
 * This file is part of Hyperf + OpenCodeCo
 *
 * @link     https://opencodeco.dev
 * @document https://hyperf.wiki
 * @contact  leo@opencodeco.dev
 * @license  https://github.com/opencodeco/hyperf-metric/blob/main/LICENSE
 */
namespace Hyperf\Tracer\Support;

use Exception;
use Jaeger\Codec\CodecInterface;
use Jaeger\Codec\CodecUtility;
use Jaeger\SpanContext;

/**
 * @codeCoverageIgnore
 */
class TextCodecOtel implements CodecInterface
{
    const VERSION = '00';
    private string $traceIdHeader = 'traceparent';
    private string $traceStateHeader = 'tracestate';

    /**
     * {@inheritdoc}
     *
     * @param SpanContext $spanContext
     * @param mixed $carrier
     *
     * @return void
     * @see \Jaeger\Tracer::inject
     *
     */
    public function inject(SpanContext $spanContext, &$carrier)
    {
        $carrier[$this->traceIdHeader] = $this->spanContextToString(
            $spanContext->getTraceId(),
            $spanContext->getSpanId(),
            $spanContext->getFlags()
        );

        $baggage = $spanContext->getBaggage();
        if (empty($baggage)) {
            return;
        }

        $baggageHeader = [];

        foreach ($baggage as $key => $value) {
            $value = $key . '=' . $value;
            if (!GuzzleHeaderValidate::isValidHeaderValue($value)) {
                continue;
            }
            $baggageHeader[] = $value;
        }
        $carrier[$this->traceStateHeader] = implode(',', $baggageHeader);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $carrier
     * @return SpanContext|null
     *
     * @throws Exception
     * @see \Jaeger\Tracer::extract
     *
     */
    public function extract($carrier)
    {
        $baggage = [];
        $carrier = (array)$carrier;

        if (!isset($carrier[$this->traceIdHeader])) {
            return null;
        }

        [$version, $traceId, $spanId, $flags] = $this->spanContextFromString($carrier[$this->traceIdHeader]);
        if (!empty($carrier[$this->traceStateHeader])) {
            $traceStateHeaders = $carrier[$this->traceStateHeader];
            $state = explode(',', $traceStateHeaders);
            foreach ($state as $stateItem) {
                $stateItem = trim($stateItem);
                $stateItem = explode('=', $stateItem);
                if (count($stateItem) !== 2) {
                    continue;
                }
                $stateKey = $stateItem[0];
                $stateValue = $stateItem[1];
                $baggage[$stateKey] = $stateValue;
            }
        }

        if ($traceId === null && $baggage !== []) {
            throw new Exception('baggage without trace ctx');
        }

        if ($traceId === null) {
            return null;
        }

        return new SpanContext($traceId, $spanId, null, $flags, $baggage);
    }

    /**
     * Store a span context to a string.
     *
     * @param string $traceId
     * @param string $spanId
     * @param string $flags
     * @return string
     */
    private function spanContextToString($traceId, $spanId, $flags)
    {
        $flags = str_pad($flags, 2, "0", STR_PAD_LEFT);
        return sprintf('%s-%s-%s-%s',
            self::VERSION,
            JaegerDecoder::traceIdDecoder($traceId),
            JaegerDecoder::spanIdDecoder($spanId),
            $flags
        );
    }

    /**
     * Create a span context from a string.
     *
     * @param string $value
     * @return array
     *
     * @throws Exception
     */
    private function spanContextFromString($value): array
    {
        $parts = explode('-', $value);

        if (count($parts) != 4) {
            throw new Exception('Malformed tracer state string.');
        }
        /**
         * TraceId em Otel ja é um hexadecimal de 32 caracteres e precisa permanecer assim.
         * Span id sofre conversões no caminho porém é reportado em hexa, por isso é necessária a conversão
         */

        return [
            $parts[0],
            $parts[1],//
            CodecUtility::hexToInt64($parts[2]),//
            $parts[3],
        ];
    }
}
