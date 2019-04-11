<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\ArticleList;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\SeoDecoder;
use OxidEsales\Eshop\Core\Theme;
use OxidEsales\Eshop\Core\ViewConfig;
use Oxidio\DI\SmartyTemplateVars;

use fn;
use DI;
use Oxidio;
use Smarty;

return [
    TITLE    => 'bar module (oxidio)',

    SETTINGS => [
        'foo' => [
            'string' => [SETTINGS\VALUE => 'string'],
            'true'   => [SETTINGS\VALUE => true],
            'false'  => [SETTINGS\VALUE => false],
            'aarr'   => [SETTINGS\VALUE => ['a' => 'A', 'b' => 'B']],
        ],
        'bar' => [
            'selected' => [SETTINGS\VALUE => ['c' => 'C', 'd' => 'D', 'e' => 'E'], SETTINGS\SELECTED => 'd']
        ]
    ],

    EXTEND => [
        SeoDecoder::class => Oxidio\Bar\Core\BarSeoDecoder::class,
    ],

    BLOCKS   => [
        Theme\LAYOUT_BASE   => [
            Theme\LAYOUT_BASE\BLOCK_HEAD_META_ROBOTS  => prepend(function() {

            }),
            Theme\LAYOUT_BASE\BLOCK_HEAD_TITLE => overwrite(function(
                FrontendController $ctrl,
                SmartyTemplateVars $vars,
                Smarty $smarty,
                Config $configFromRegistry,
                SeoDecoder $decoder,
                Article $default = null,
                ArticleList ...$lists
            ) {
                return implode('-', [
                    get_class($ctrl),
                    get_class($vars),
                    get_class($smarty),
                    get_class($configFromRegistry),
                    get_class($decoder),
                    $default ? get_class($default) : '',
                    count($lists)
                ]);
            }),
        ],
        Theme\LAYOUT_FOOTER => [
            Theme\LAYOUT_FOOTER\BLOCK_MAIN => append(function() {}),
        ],
    ],

    CLI   => DI\decorate(function(fn\Cli $cli) {
        $cli->command('bar', function(fn\Cli\IO $io) {
            $io->success('bar');
        });
        $cli->command('db', Oxidio\Bar\Cli\Db::class , ['filter']);
        return $cli;
    }),

    Oxidio\Bar\Cli\Db::class => DI\create(),

    MENU => [
        Menu\ADMIN => [ // merge
            menu(['admin-main'], [ // register new main menu under ADMIN
                admin\main\sub1::class => menu(['label' => 'admin-main-sub1']),
                admin\main\sub2::class => menu('admin-main-sub2', [
                    admin\main\sub2\t1::class => 'admin-main-sub2-t1',
                    'admin-main-sub2-btn1',
                    'admin-main-sub2-btn2',
                ]),
            ]),

            Menu\ADMIN\USERS => [
                // register new sub menus under ADMIN/USERS
                admin\users\sub1::class => menu(['admin-users-sub1', 'list' => 'user_list', 'groups' => ['g1', 'g2'], 'rights' => ['r1']]),
                admin\users\sub2::class => menu('admin-users-sub2', [
                    admin\users\sub2\t1::class => 'admin-users-sub2-t1',
                    admin\users\sub2\t2::class => 'admin-users-sub2-t2',
                    'admin-users-sub2-btn1',
                ]),

                Menu\ADMIN\USERS\GROUPS => [ // register new tabs and buttons under ADMIN/USERS/GROUPS,
                    admin\users\groups\t1::class => 'admin-users-groups-t1',
                    admin\users\groups\t2::class => ['de' => 'admin-users-groups-t2-de', 'en' => 'admin-users-groups-t2-en'],
                    'admin-users-groups-btn1',
                    'admin-users-groups-btn2',
                ],
            ],
        ],

        menu(['bar'], // create
            menu('bar-main', [ // create new main menu under BAR
                bar\main\sub1::class => menu('bar-main-sub1', [
                    bar\main\sub1\t1::class => ['bar-main-sub1-t1', 'params' => ['a' => 'b', 'c' => ['d', 'e']]],
                    'bar-main-sub1-btn1',
                    ['label' => 'bar-main-sub1-btn2', 'class' => bar\main\sub1\btn2::class],
                ]),
                app('bar-app', function(SmartyTemplateVars $vars, App $ctrl, Config $config, ViewConfig $vc) {
                    return '<h2>' . implode('-', [
                        'bar-app',
                        get_class($ctrl),
                        get_class($vars),
                        get_class($config),
                        get_class($vc),
                    ]) . '</h2>';
                })
            ]),
            [menu(['bar-users', 'params' => ['bar' => 'user'], 'class' => bar\users::class])]
        ),

        foo::class => menu('foo'),
    ],
];
