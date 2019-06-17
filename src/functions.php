<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio
{
    use fn;
    use OxidEsales\Eshop;

    function db(...$args): Core\Database
    {
        return Core\Database::get(...$args);
    }

    /**
     * @param string $sql
     * @param callable ...$mapper
     *
     * @return fn\Map|array[]
     */
    function select($sql, callable ...$mapper)
    {
        return db()($sql, ...$mapper);
    }

    /**
     * @param mixed ...$args
     *
     * @return Core\Query
     */
    function query(...$args): Core\Query
    {
        return db()->query(...$args);
    }

    function shop($shop = null): Core\Shop
    {
        is_string($shop) && $shop = fn\traverse($_ENV ?? [], static function ($url, &$var) {
            if (strpos($var, 'OXIDIO_SHOP_') !== 0) {
                return null;
            }
            $var = str_replace('_', '-', strtolower(substr($var, 12)));
            return $url;
        })[$shop] ?? $shop;

        return new Core\Shop($shop instanceof Core\Database ? $shop : db($shop));
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
