<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Meta;

use Php;
use Generator;
use OxidEsales\Eshop\{Core, Core\Model\BaseModel};
use OxidEsales\Facts\Facts;
use OxidEsales\UnifiedNameSpaceGenerator\UnifiedNameSpaceClassMapProvider;
use Oxidio;
use Webmozart\Glob\Glob;

/**
 * @property-read Oxidio\Core\Database $db
 * @property-read ReflectionNamespace $tableNs
 * @property-read ReflectionNamespace|null $fieldNs
 * @property-read ReflectionNamespace $themeNs
 * @property-read EditionClass[] $classes
 * @property-read Table[] $tables
 * @property-read ReflectionNamespace[] $namespaces
 *
 * @method Table table(string|array $name, iterable $props = [])
 * @method EditionClass class(string|array $name, iterable $props = [])
 * @method ReflectionNamespace ns(string|array $name, iterable $props = [])
 * @method ReflectionConstant const(string|array $name, iterable $props = [])
 * @method Template template(string|array $name, iterable $props = [])
 */
class Provider
{
    use Php\PropertiesTrait\ReadOnly;
    use Php\PropertiesTrait\Init;

    protected $data = [];

    /**
     * @see $db
     * @return Oxidio\Core\Database
     */
    protected function resolveDb(): Oxidio\Core\Database
    {
        return Oxidio\Core\Database::get($this->properties['db'] ?? null);
    }

    /**
     * @param string $class
     * @param string|string[] $name
     * @param array $properties
     *
     * @return ReflectionTrait|mixed
     */
    private function get(string $class, $name, array $properties = [])
    {
        is_iterable($name) && $name = Php::map($name, static function ($part) {
            return trim($part, '\\') ?: null;
        })->string('\\');

        return $this->data[$class][$name] ??
            ($this->data[$class][$name] = new $class($this, array_merge($properties, ['name' => $name])));
    }

    public function __call(string $method, array $args)
    {
        static $classes = [
            'table' => Table::class,
            'class' => EditionClass::class,
            'ns' => ReflectionNamespace::class,
            'const' => ReflectionConstant::class,
            'template' => Template::class,
        ];

        if ($class = ($classes[$method] ?? null)) {
            return $this->get($class, ...$args);
        }
        Php::fail($method);
    }


    /**
     * @see $tableNs
     * @return ReflectionNamespace
     */
    protected function resolveTableNs(): ReflectionNamespace
    {
        return $this->ns($this->properties['tableNs'] ?? Core\Database\TABLE::class);
    }

    /**
     * @see $fieldNs
     * @return ReflectionNamespace
     */
    protected function resolveFieldNs(): ?ReflectionNamespace
    {
//        if ($ns = $this->properties['fieldNs'] ?? null) {
//            return $this->ns($ns, ['use' => [substr($this->tableNs, 0, -1)]]);
//        }
        return null;
    }

    /**
     * @see $themeNs
     * @return ReflectionNamespace
     */
    protected function resolveThemeNs(): ReflectionNamespace
    {
        return $this->ns($this->properties['themeNs'] ?? null);
    }

    /**
     * @see $classes
     * @return EditionClass[]
     */
    public function resolveClasses(): array
    {
        $provider = new UnifiedNameSpaceClassMapProvider(new Facts);
        return Php::keys($provider->getClassMap(), function (string $name) {
            $class = $this->class($name, ['tableNs' => $this->tableNs, 'fieldNs' => $this->fieldNs]);
            return Php::mapKey($name)->andValue($class);
        });
    }

    /**
     * @see $tables
     * @return Table[]
     */
    public function resolveTables(): array
    {
        $base = $this->classes[BaseModel::class] ?? null;
        $tables = $this->data[Table::class] ?? [];

        foreach ($this->db->tables as $table) {
            $table = $table->getName();
            if (isset($tables[$table])) {
                continue;
            }
            $this->table($table, ['class' => $base]);
        }
        return $this->data[Table::class] ?? [];
    }

    /**
     * @return iterable
     */
    public function resolveNamespaces(): iterable
    {
        $cached = $this->data[ReflectionNamespace::class] ?? [];
        return Php::map($cached)->sort(static function(ReflectionNamespace $left, ReflectionNamespace $right) {
            return (count($left->use) - count($right->use)) ?: strcmp($left, $right);
        });
    }

    /**
     * @param string $glob
     *
     * @return Generator|Template[]
     */
    public function templates(string $glob): Generator
    {
        $basePath = Glob::getBasePath($glob);
        $offset   = strlen($basePath) + 1;
        foreach (Glob::glob($glob) as $path) {
            $name = substr($path, $offset);
            yield $name => $this->template($name, ['path' => $path, 'namespace' => $this->themeNs]);
        }
    }
}
