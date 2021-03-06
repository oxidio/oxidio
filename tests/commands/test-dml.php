<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use Generator;
use Oxidio\Enum\Tables as T;

/**
 * Test dml functionality
 *
 * @param Core\Shop $shop
 * @param string|null $action insert|update|delete
 * @param string|null $name
 * @param bool $dryRun
 *
 * @return Generator
 */
return static function (Core\Shop $shop, string $action = null, string $name = null, bool $dryRun = false) {
    $where = [T\CONFIG::ID => "test:$name"];
    $modify = $shop->modify(T::CONFIG);

    if ($action === 'insert') {
        yield (object)$modify->insert([
            T\CONFIG::MODULE => 'test',
            T\CONFIG::VARNAME => $name,
            T\CONFIG::ID => "test:$name",
        ])($dryRun);
    } else if ($action === 'update') {
        yield (object)$modify->update([T\CONFIG::TIMESTAMP => null], $where)($dryRun);
    } else if ($action === 'delete') {
        yield (object)$modify->delete($where)($dryRun);
    }

    yield (object)$shop->query(T::CONFIG, [T\CONFIG::ID => ['LIKE', 'test:%']]);
};
