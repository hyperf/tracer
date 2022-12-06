#!/usr/bin/env php
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
ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

require_once __DIR__ . '/../config/consts.php';
require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

Hyperf\Di\ClassLoader::init();

Swoole\Runtime::enableCoroutine(true);

Swoole\Coroutine::set([
    'hook_flags' => SWOOLE_HOOK_ALL,
    'exit_condition' => static fn (): bool => Swoole\Coroutine::stats()['coroutine_num'] === 0,
]);

$code = 0;
Swoole\Coroutine\run(static function () use (&$code): void {
    try {
        $code = PHPUnit\TextUI\Command::main(false);
    } catch (PHPUnit\TextUI\RuntimeException $e) {
        if ($e->getMessage() === 'swoole exit') {
            return;
        }

        throw $e;
    } finally {
        Swoole\Timer::clearAll();
        Hyperf\Utils\Coordinator\CoordinatorManager::until(Hyperf\Utils\Coordinator\Constants::WORKER_EXIT)->resume();
    }
});
exit($code);
