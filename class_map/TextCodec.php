<?php

namespace Jaeger\Codec;

use Hyperf\Tracer\Support\GuzzleHeaderValidate;
use Hyperf\Tracer\Support\TextCodecOtel;
use Exception;
use Jaeger\SpanContext;

use const Jaeger\TRACE_ID_HEADER;
use const Jaeger\BAGGAGE_HEADER_PREFIX;
use const Jaeger\DEBUG_ID_HEADER_KEY;

/**
 * @codeCoverageIgnore
 */
class TextCodec implements CodecInterface
{
    private $urlEncoding;
    private $traceIdHeader;
    private $baggagePrefix;
    private $debugIdHeader;
    private $prefixLength;

    private TextCodecOtel $openTelemetryCodec;

    /**
     * @param bool $urlEncoding
     * @param string $traceIdHeader
     * @param string $baggageHeaderPrefix
     * @param string $debugIdHeader
     */
    public function __construct(
        bool   $urlEncoding = false,
        string $traceIdHeader = TRACE_ID_HEADER,
        string $baggageHeaderPrefix = BAGGAGE_HEADER_PREFIX,
        string $debugIdHeader = DEBUG_ID_HEADER_KEY
    )
    {
        $this->urlEncoding = $urlEncoding;
        $this->traceIdHeader = str_replace('_', '-', strtolower($traceIdHeader));
        $this->baggagePrefix = str_replace('_', '-', strtolower($baggageHeaderPrefix));
        $this->debugIdHeader = str_replace('_', '-', strtolower($debugIdHeader));
        $this->prefixLength = strlen($baggageHeaderPrefix);
        $this->openTelemetryCodec = new TextCodecOtel();
    }

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
        $this->openTelemetryCodec->inject($spanContext, $carrier);

        $carrier[$this->traceIdHeader] = $this->spanContextToString(
            $spanContext->getTraceId(),
            $spanContext->getSpanId(),
            $spanContext->getParentId(),
            $spanContext->getFlags()
        );

        $baggage = $spanContext->getBaggage();
        if (empty($baggage)) {
            return;
        }

        foreach ($baggage as $key => $value) {
            $encodedValue = $value;

            if ($this->urlEncoding) {
                $encodedValue = urlencode($value);
            }
            $headerName = $this->baggagePrefix . $key;

            if (!GuzzleHeaderValidate::isValidHeader($headerName, $encodedValue)) {
                continue;
            }
            $carrier[$headerName] = $encodedValue;
        }
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
        $spanContext = $this->openTelemetryCodec->extract($carrier);
        if ($spanContext !== null) {
            return $spanContext;
        }

        $traceId = null;
        $spanId = null;
        $parentId = null;
        $flags = null;
        $baggage = null;
        $debugId = null;

        foreach ((array)$carrier as $key => $value) {
            $ucKey = strtolower($key);

            if ($ucKey === $this->traceIdHeader) {
                if ($this->urlEncoding) {
                    $value = urldecode($value);
                }
                [$traceId, $spanId, $parentId, $flags] =
                    $this->spanContextFromString($value);
            } elseif ($this->startsWith($ucKey, $this->baggagePrefix)) {
                if ($this->urlEncoding) {
                    $value = urldecode($value);
                }
                $attrKey = substr($key, $this->prefixLength);
                if ($baggage === null) {
                    $baggage = [strtolower($attrKey) => $value];
                } else {
                    $baggage[strtolower($attrKey)] = $value;
                }
            } elseif ($ucKey === $this->debugIdHeader) {
                if ($this->urlEncoding) {
                    $value = urldecode($value);
                }
                $debugId = $value;
            }
        }

        if ($traceId === null && $baggage !== null) {
            throw new Exception('baggage without trace ctx');
        }

        if ($traceId === null) {
            if ($debugId !== null) {
                return new SpanContext(null, null, null, null, [], $debugId);
            }
            return null;
        }

        return new SpanContext($traceId, $spanId, $parentId, $flags, $baggage);
    }

    /**
     * Store a span context to a string.
     *
     * @param int $traceId
     * @param int $spanId
     * @param int $parentId
     * @param int $flags
     * @return string
     */
    private function spanContextToString($traceId, $spanId, $parentId, $flags)
    {
        $parentId = $parentId ?? 0;
        if (is_int($traceId)) {
            $traceId = sprintf('%016x', $traceId);
        }
        return sprintf('%s:%x:%x:%x', $traceId, $spanId, $parentId, $flags);
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
        $parts = explode(':', $value);

        if (count($parts) != 4) {
            throw new Exception('Malformed tracer state string.');
        }

        return [
            $parts[0],
            CodecUtility::hexToInt64($parts[1]),
            CodecUtility::hexToInt64($parts[2]),
            $parts[3],
        ];
    }

    /**
     * Checks that a string ($haystack) starts with a given prefix ($needle).
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    private function startsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) == $needle;
    }
}
