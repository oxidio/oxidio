<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */

namespace Oxidio
{
    use fn;
    use OxidEsales\Eshop;
    use Oxidio\Model\Query;

    /**
     * @param int $fetchMode
     *
     * @return Eshop\Core\Database\Adapter\DatabaseInterface
     */
    function db($fetchMode = Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC)
    {
        return Eshop\Core\DatabaseProvider::getDb($fetchMode);
    }

    /**
     * @param string $sql
     * @param callable ...$mapper
     *
     * @return fn\Map|array[]
     */
    function select($sql, callable ...$mapper)
    {
        return fn\map(db()->select((string)$sql), ...$mapper);
    }

    /**
     * @param mixed ...$args
     *
     * @return Query
     */
    function query(...$args): Query
    {
        return new Query(...$args);
    }
}

namespace Oxidio\Module
{
    /**
     * @param $callable
     *
     * @return Block
     */
    function append($callable): Block
    {
        return new Block($callable, Block::APPEND);
    }

    /**
     * @param $callable
     *
     * @return Block
     */
    function prepend($callable): Block
    {
        return new Block($callable, Block::PREPEND);
    }

    /**
     * @param $callable
     *
     * @return Block
     */
    function overwrite($callable): Block
    {
        return new Block($callable, Block::OVERWRITE);
    }

    /**
     * @param mixed $label
     * @param mixed ...$args
     *
     * @return Menu
     */
    function menu($label, ...$args): Menu
    {
        return new Menu($label, ...$args);
    }

    /**
     * @param mixed $label
     * @param callable $callable
     * @return Menu
     */
    function app($label, $callable): Menu
    {
        $menu           = new Menu($label);
        $menu->class    = App::class;
        $menu->callback = $callable;
        $menu->params   = [
            /* @see Module::resolveParams */
            APP => function(Module $module, $menuKey) {
                return $module->id . ":{$menuKey}";
            }
        ];
        return $menu;
    }
}
