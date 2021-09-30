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

class SwitchManager
{
    private const DEFAULTS = [
        'guzzle' => true,
        'redis' => true,
        'db' => true,
        'method' => false, // experimental
        'exception' => true,
    ];

    private array $config = self::DEFAULTS;

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
        return $this->config[$identifier] ?? false;
    }
}
