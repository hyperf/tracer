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
namespace Jaeger;

use ArrayIterator;
use OpenTracing\SpanContext as OTSpanContext;
use ReturnTypeWillChange;

class SpanContext implements OTSpanContext
{
    private array $baggage;

    public function __construct(
        private string $traceId,
        private string $spanId,
        private ?string $parentId = null,
        private ?int $flags = null,
        ?array $baggage = null,
        private ?int $debugId = null,
    ) {
        $this->baggage = $baggage ?? [];
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function getIterator(): ArrayIterator|iterable
    {
        return new ArrayIterator($this->baggage);
    }

    /**
     * {@inheritdoc}
     */
    public function getBaggageItem(string $key): ?string
    {
        return $this->baggage[$key] ?? null;
    }

    /**
     * {@inheritdoc}
     *
     * @return SpanContext
     */
    public function withBaggageItem(string $key, string $value): OTSpanContext
    {
        return new self(
            $this->traceId,
            $this->spanId,
            $this->parentId,
            $this->flags,
            [$key => $value] + $this->baggage
        );
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function setTraceId(string $traceId): self
    {
        $this->traceId = $traceId;
        return $this;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function getSpanId(): string
    {
        return $this->spanId;
    }

    public function getFlags(): ?int
    {
        return $this->flags;
    }

    public function getBaggage(): array
    {
        return $this->baggage;
    }

    public function getDebugId(): ?int
    {
        return $this->debugId;
    }

    public function isDebugIdContainerOnly(): bool
    {
        return ($this->traceId === null) && ($this->debugId !== null);
    }
}
