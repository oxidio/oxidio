<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio
{
    use Php;
    use OxidEsales\Eshop;

    function db(...$args): Core\Database
    {
        return Core\Database::get(...$args);
    }

    /**
     * @param mixed ...$args
     *
     * @return Core\DataQuery
     */
    function query(...$args): Core\DataQuery
    {
        return db()->query(...$args);
    }

    /**
     * @param string|Core\Database $shop
     * @param array $params
     *
     * @return Core\Shop
     */
    function shop($shop = null, array $params = []): Core\Shop
    {
        return Functions::shop(...func_get_args());
    }

    /**
     * @param Php\Package|string|array $package
     * @param string|callable|array    ...$args
     *
     * @return Php\Cli
     */
    function cli($package = null, ...$args): Php\Cli
    {
        return Functions::cli(...func_get_args());
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
            /* @see Module::params */
            APP => function(Module $module, $menuKey) {
                return $module->id . ":{$menuKey}";
            }
        ];
        return $menu;
    }
}
