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
namespace Hyperf\Tracer\Support;

final class Uri
{
    public static function sanitize(string $uri): string
    {
        return preg_replace(
            [
                '/\/(?<=\/)([a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12})(?=\/)?/',
                '/\/(?<=\/)\d+(?=\/)?/',
            ],
            [
                '/<UUID>',
                '/<NUMBER>',
            ],
            $uri
        );
    }
}
