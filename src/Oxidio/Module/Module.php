<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use fn;
use JsonSerializable;
use OxidEsales\Eshop\Core\Module\Module as OxidModule;
use OxidEsales\Eshop\Core\Registry;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property-read string $id
 * @property-read fn\Cli $cli
 * @property-read array  $metadata
 */
class Module implements JsonSerializable
{
    use fn\DI\PropertiesReadOnlyTrait;

    /**
     * @var string
     */
    public const CONFIG = __DIR__ . '/../../../config/module.php';

    /**
     * @var fn\DI\Container
     */
    protected $container;

    /**
     * @param fn\DI\Container $container
     */
    public function __construct(fn\DI\Container $container)
    {
        $this->container = $container;
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

    /**
     * @see \fn\Cli::run
     *
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func($this->cli);
    }

    public function renderBlock(string $file, array $vars): string
    {
        /** @var Blocks $blocks */
        $blocks = $this->get(Blocks::class);
        if ($block = $blocks->get($file)) {
            fn\traverse($vars, function($var) {
                if (is_object($var)) {
                    $class = get_class($var);
                    $this->container->set($class, $var);
                }
            });
            return (string) $this->container->call($block->callback, $vars);
        }

        return '';
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

            foreach ($this->get(Blocks::class) as $file => $block) {
                $fs->dumpFile("{$path}/{$file}", sprintf($block, $this->id));
            }
        } else {
            $fs->remove("{$path}/views/");
        }

        return true;
    }


    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            ID       => $this->id,
            TITLE    => $this->get(TITLE),
            URL      => $this->get(URL),
            AUTHOR   => $this->get(AUTHOR),
            SETTINGS => $this->get(Settings::class),
            BLOCKS   => $this->get(Blocks::class),
            EVENTS   => $this->get(Events::class)
        ];
    }
}
