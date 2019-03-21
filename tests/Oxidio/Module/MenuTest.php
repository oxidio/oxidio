<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use fn;
use fn\test\assert;
use Oxidio;

/**
 */
class MenuTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor(): void
    {
        assert\type(Menu::class, $menu = menu('label'));
        assert\same('label', $menu->label);
        assert\same(null, $menu->class);
        assert\same([], $menu->menus);
        assert\same([], $menu->params);
        assert\same([], $menu->groups);
        assert\same([], $menu->rights);
        assert\same([], $menu->tabs);
        assert\same([], $menu->buttons);
    }

    public function testGetId(): void
    {
        assert\same('label', menu('label')->getId());
        assert\same('de', menu(['de', 'en'])->getId());
        assert\same('en', menu(['en', 'de'])->getId());
    }

    public function testToString(): void
    {
        self::assertToString([
            '    <OXMENU id="label">',
            '    </OXMENU>',
        ], menu('label'));

        self::assertToString([
            '    <OXMENU id="menu">',
            '        <MAINMENU id="main">',
            '            <SUBMENU id="sub">',
            '            </SUBMENU>',
            '        </MAINMENU>',
            '    </OXMENU>',
        ], menu('menu', menu('main', menu('sub'))));
    }

    public function testCreate(): void
    {
        $menus = fn\traverse(Menu::create(Module::instance(fn\VENDOR\OXIDIO\MODULE_BAR)->get(MENU)));

        self::assertToString([
            ['    <OXMENU id="%s">', Menu\ADMIN],
            ['        <MAINMENU id="%s">', 'admin-main'],
            ['            <SUBMENU id="%s">', 'admin-main-sub1'],
            '            </SUBMENU>',
            ['            <SUBMENU id="%s">', 'admin-main-sub2'],
            ['                <TAB id="%s" cl="%s" />', 'admin-main-sub2-t1', admin\main\sub2\t1::class],
            ['                <BTN id="%s" />', 'admin-main-sub2-btn1'],
            ['                <BTN id="%s" />', 'admin-main-sub2-btn2'],
            '            </SUBMENU>',
            '        </MAINMENU>',
            ['        <MAINMENU id="%s">', end($id = explode('/', Menu\ADMIN\USERS))],
            ['            <SUBMENU id="%s" cl="%s" list="%s" groups="%s" rights="%s">',
                'admin-users-sub1',
                admin\users\sub1::class,
                'user_list',
                'g1,g2',
                'r1'
            ],
            '            </SUBMENU>',
            ['            <SUBMENU id="%s" cl="%s">', 'admin-users-sub2', admin\users\sub2::class],
            ['                <TAB id="%s" cl="%s" />', 'admin-users-sub2-t1', admin\users\sub2\t1::class],
            ['                <TAB id="%s" cl="%s" />', 'admin-users-sub2-t2', admin\users\sub2\t2::class],
            ['                <BTN id="%s" />', 'admin-users-sub2-btn1'],
            '            </SUBMENU>',
            ['            <SUBMENU id="%s">', end($id = explode('/', Menu\ADMIN\USERS\GROUPS))],
            ['                <TAB id="%s" cl="%s" />', 'admin-users-groups-t1', admin\users\groups\t1::class],
            ['                <TAB id="%s" cl="%s" />', 'admin-users-groups-t2-de', admin\users\groups\t2::class],
            ['                <BTN id="%s" />', 'admin-users-groups-btn1'],
            ['                <BTN id="%s" />', 'admin-users-groups-btn2'],
            '            </SUBMENU>',
            '        </MAINMENU>',
            '    </OXMENU>',
        ], $menus[0]);

        self::assertToString([
            '    <OXMENU id="bar">',
            '        <MAINMENU id="bar-main">',
            ['            <SUBMENU id="%s">', 'bar-main-sub1'],
            ['                <TAB id="%s" cl="%s" clparam="%s" />',
                'bar-main-sub1-t1',
                bar\main\sub1\t1::class,
                'a=b&amp;c%5B0%5D=d&amp;c%5B1%5D=e'
            ],
            ['                <BTN id="%s" />', 'bar-main-sub1-btn1'],
            ['                <BTN id="%s" cl="%s" />', 'bar-main-sub1-btn2', bar\main\sub1\btn2::class],
            '            </SUBMENU>',
            '        </MAINMENU>',
            ['        <MAINMENU id="%s" cl="%s" clparam="%s">', 'bar-users', bar\users::class, 'bar=user'],
            '        </MAINMENU>',
            '    </OXMENU>',
        ], $menus[1]);

        self::assertToString([
            ['    <OXMENU id="%s" cl="%s">', 'foo', foo::class],
            '    </OXMENU>',
        ], $menus[2]);
    }

    private static function assertToString(array $lines, Menu $menu): void
    {
        assert\same(implode(PHP_EOL, fn\traverse($lines, function($line) {
            return is_array($line) ? sprintf(...$line) : $line;
        })), (string) $menu);
    }
}
