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
namespace Hyperf\Tracer\Listener;

use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;
use OpenTracing\Tracer;

class DbQueryExecutedListener implements ListenerInterface
{
    use SpanStarter;

    private Tracer $tracer;

    private SwitchManager $switchManager;

    private SpanTagManager $spanTagManager;

    public function __construct(Tracer $tracer, SwitchManager $switchManager, SpanTagManager $spanTagManager)
    {
        /* @noinspection UnusedConstructorDependenciesInspection */
        $this->tracer = $tracer;
        $this->switchManager = $switchManager;
        $this->spanTagManager = $spanTagManager;
    }

    public function listen(): array
    {
        return [QueryExecuted::class];
    }

    public function process(object $event): void
    {
        if ($this->switchManager->isEnabled('db') === false) {
            return;
        }

        if (! $event instanceof QueryExecuted) {
            return;
        }

        $sql = $event->sql;
        if (! Arr::isAssoc($event->bindings)) {
            foreach ($event->bindings as $key => $value) {
                $sql = Str::replaceFirst('?', "'{$value}'", $sql);
            }
        }

        $endTime = microtime(true);
        $span = $this->startSpan($event->sql, [
            'start_time' => (int) (($endTime - $event->time / 1000) * 1000 * 1000),
        ]);

        $span->setTag('category', 'datastore');
        $span->setTag('component', 'MySQL');
        $span->setTag('kind', 'client');
        $span->setTag('otel.status_code', 'OK');
        $span->setTag('db.system', 'mysql');
        $span->setTag('db.name', $event->connectionName);

        $span->setTag($this->spanTagManager->get('db', 'db.statement'), $sql);
        $span->setTag($this->spanTagManager->get('db', 'db.query_time'), $event->time . ' ms');
        $span->finish((int) ($endTime * 1000 * 1000));
    }
}
