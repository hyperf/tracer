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

use Hyperf\Utils\Context;
use OpenTracing\Span;

class SwitchManager
{
    private array $config
        = [
            'guzzle' => true,
            'redis' => true,
            'db' => true,
            'method' => false, // experimental
            'exception' => true,
        ];

    /**
     * Apply the configuration to SwitchManager.
     */
    public function apply(array $config): void
    {
        $this->config = array_replace($this->config, $config);
    }

    /**
     * Determine if the tracer is enabled.
     */
    public function isEnabled(string $identifier): bool
    {
        if (! isset($this->config[$identifier])) {
            return false;
        }

        return $this->config[$identifier] && Context::get('tracer.root') instanceof Span;
    }
}
