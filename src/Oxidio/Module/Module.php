<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use fn;
use Generator;
use JsonSerializable;
use OxidEsales\Eshop\Core\Module\Module as OxidModule;
use OxidEsales\Eshop\Core\Registry;
use Oxidio\DI\RegistryResolver;
use Oxidio\DI\SmartyTemplateVars;
use Symfony\Component\Filesystem\Filesystem;
use Invoker\ParameterResolver;

/**
 * @property-read string $id
 * @property-read fn\Cli $cli
 */
class Module implements JsonSerializable
{
    use fn\DI\PropertiesReadOnlyTrait;

    /**
     * @var static[]
     */
    private static $cache = [];

    /**
     * @var fn\DI\Container
     */
    protected $container;

    /**
     * @var fn\DI\Invoker
     */
    private $invoker;

    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        $package = (fn\PACKAGES[$id] ?? []) + [
            CLI => function() {
                return fn\cli($this->container, [
                    'cli.name' => $this->get(TITLE),
                    'cli.version' => $this->getVersion(),
                    'cli.commands.default' => false,
                ]);
            }
        ];

        ($di = $package['extra']['di'] ?? []) && $di = $package['dir'] . $di;
        $this->container = fn\di($package, $di, fn\Composer\DIClassLoader::instance()->getContainer());
        $this->container->set(self::class, $this);
        $this->container->set(ID, $id);
    }

    private function getVersion(): string
    {
        return implode('.', array_slice(explode('.', $this->get(VERSION)), 0, -1));
    }

    private function getBlocks(): Blocks
    {
        return new Blocks($this->get(BLOCKS, []));
    }

    /**
     * @param string $id
     *
     * @return static
     */
    public static function instance(string $id): self
    {
        return self::$cache[$id] ?? self::$cache[$id] = new static($id);
    }

    /**
     * @param string|iterable $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if (is_iterable($name)) {
            return fn\traverse($name, function($default, $name) {
                if (is_numeric($name)) {
                    $name    = $default;
                    $default = fn\mapNull();
                }
                return fn\mapKey($name)->andValue($this->$name ?? $default);
            });
        }
        return $this->$name ?? $default;
    }

    public function renderBlock(string $file): string
    {
        if ($block = $this->getBlocks()->get($file)) {
            return (string) $this->getInvoker()->call($block->callback);
        }
        return '';
    }

    /**
     * @return fn\DI\Invoker
     */
    public function getInvoker(): fn\DI\Invoker
    {
        return $this->invoker ?: $this->invoker = new fn\DI\Invoker(
            new SmartyTemplateVars,
            new ParameterResolver\AssociativeArrayResolver,
            $this->container,
            new ParameterResolver\Container\ParameterNameContainerResolver($this->container),
            new RegistryResolver,
            new ParameterResolver\DefaultValueResolver
        );
    }

    /**
     * @param bool       $enable
     * @param OxidModule $module
     *
     * @return bool
     */
    public function activate(bool $enable, OxidModule $module): bool
    {
        $fs   = new Filesystem;
        $path = $module->getModuleFullPath();
        if ($enable) {
            foreach (Registry::getLang()->getLanguageIds() as $lang) {
                $fs->dumpFile("{$path}/views/admin/$lang/module_options.php", implode(PHP_EOL, [
                    '<?php',
                    sprintf('// autogenerated by %s ', __METHOD__),
                    sprintf('$aLang = %s;', var_export($this->getTranslations($lang), true)),
                ]));
            }

            foreach ($this->getBlocks() as $file => $block) {
                $fs->dumpFile("{$path}/{$file}", sprintf($block, $this->id));
            }

            $fs->dumpFile("{$path}/menu.xml", fn\map($this->getMenu())->string);

        } else {
            $fs->remove("{$path}/views/");
            $fs->remove("{$path}/menu.xml");
        }

        return true;
    }

    private function getMenu(): Generator
    {
        yield '<?xml version="1.0" encoding="UTF-8"?>';
        yield '<OX>';
        foreach (Menu::create($this->get(MENU, [])) as $menu) {
            yield (string)$menu;
        }
        yield '</OX>';
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $author = $this->get('authors')[0] ?? [];
        return [
            ID          => $this->id,
            TITLE       => $this->get(TITLE, $this->id),
            DESCRIPTION => $this->get(DESCRIPTION),
            URL         => $this->get(URL, $this->get('homepage')),
            VERSION     => $this->getVersion(),
            AUTHOR      => $this->get(AUTHOR, $author['name'] ?? null),
            EMAIL       => $this->get(EMAIL, $author['email'] ?? null),
            SETTINGS    => new Settings($this->get(SETTINGS, [])),
            BLOCKS      => $this->getBlocks(),
            EXTEND      => $this->get(EXTEND, []),
            'events'    => new Events
        ];
    }

    /**
     * @return array
     */
    public function getMetadata(): array
    {
        return json_decode(json_encode($this), true);
    }

    /**
     * @param string $lang
     *
     * @return array
     */
    public function getTranslations(string $lang): array
    {
        return fn\traverse((new Settings($this->get(SETTINGS, [])))->translate($lang));
    }
}
