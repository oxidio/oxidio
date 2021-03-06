<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Meta;

use Php;
use Oxidio;
use ReflectionClass;

use OxidEsales\Eshop\{
    Application\Component\Widget\WidgetController,
    Application\Controller\AccountController,
    Application\Controller\Admin\AdminController,
    Application\Controller\Admin\AdminDetailsController,
    Application\Controller\Admin\AdminListController,
    Application\Controller\Admin\DynamicExportBaseController,
    Application\Controller\Admin\ListComponentAjax,
    Application\Controller\Admin\ObjectSeo,
    Application\Controller\Admin\ShopConfiguration,
    Application\Controller\ArticleDetailsController,
    Application\Controller\ArticleListController,
    Application\Controller\FrontendController,
    Core\Base,
    Core\Controller\BaseController,
    Core\Model\BaseModel,
    Core\Model\ListModel,
    Core\Model\MultiLanguageModel,
    Core\SeoEncoder
};

/**
 * @property-read string                 $shortName
 * @property-read string                 $edition
 * @property-read string                 $template
 * @property-read string                 $package
 * @property-read ReflectionClass        $reflection
 * @property-read ReflectionNamespace    $tableNs
 * @property-read ReflectionNamespace    $fieldNs
 * @property-read ReflectionConstant[]   $fields
 * @property-read Php\Map|EditionClass[] $derivation
 * @property-read EditionClass|null      $parent
 * @property-read object|null            $instance
 * @property-read Table|null             $table
 */
class EditionClass
{
    use ReflectionTrait;

    /**
     * @inheritDoc
     */
    protected function init(): void
    {
        $this->table;
    }

    /**
     * @var string[]
     */
    private const PACKAGES = [
        Base::class                        => '\\',
        SeoEncoder::class                  => '\\Seo',
        BaseModel::class                   => '\\Model',
        MultiLanguageModel::class          => '\\Model\\I18n',
        ListModel::class                   => '\\Model\\List',
        BaseController::class              => '\\Controller',
        FrontendController::class          => '\\Front',
        WidgetController::class            => '\\Front\\Widget',
        AccountController::class           => '\\Front\\Account',
        ArticleListController::class       => '\\Front\\Article\\List',
        ArticleDetailsController::class    => '\\Front\\Article\\Details',
        AdminController::class             => '\\Admin',
        ListComponentAjax::class           => '\\Admin\\Component',
        AdminListController::class         => '\\Admin\\List',
        AdminDetailsController::class      => '\\Admin\\Details',
        DynamicExportBaseController::class => '\\Admin\\Details\\Export',
        ShopConfiguration::class           => '\\Admin\\Details\\Config',
        ObjectSeo::class                   => '\\Admin\\Details\\Seo',
    ];

    /**
     * @see $derivation
     * @return Php\Map
     */
    protected function resolveDerivation(): Php\Map
    {
        $ref = $this->reflection;
        $parents = [];
        while($parent = $ref->getParentClass()) {
            $parents[] = $parent->getName();
            $ref = $parent;
        }
        return Php::map($parents, function (string $class) {
            return strpos($class, $this->edition) === 0 ? $this->provider->class($class) : null;
        });
    }

    /**
     * @see $edition
     * @return string
     */
    protected function resolveEdition(): string
    {
        $ns      = explode('\\', $this->name);
        $edition = [$ns[0]];
        if ($ns[0] === 'OxidEsales') {
            $edition[] = $ns[1];
        }
        return implode('\\', $edition) . '\\';
    }

    /**
     * @see $parent
     * @return EditionClass|null
     */
    protected function resolveParent(): ?self
    {
        $parent = $this->reflection->getParentClass();
        return $parent ? $this->provider->class($parent->getName()) : null;
    }

    /**
     * @see $reflection
     * @return ReflectionClass
     */
    protected function resolveReflection(): ReflectionClass
    {
        return new ReflectionClass($this->name);
    }

    /**
     * @see $shortName
     * @return string
     */
    protected function resolveShortName(): string
    {
        return $this->reflection->getShortName();
    }

    /**
     * @see $package
     * @return string
     */
    protected function resolvePackage(): string
    {
        static $packages;
        if ($packages === null) {
            $packages = Php::map(self::PACKAGES)->sort(function(string $left, string $right) {
                return $this->provider->class($left)->derivation->count() - $this->provider->class($right)->derivation->count();
            }, Php\Map\Sort::KEYS | Php\Map\Sort::REVERSE)->traverse;
        }
        foreach ($packages as $baseClass => $package) {
            if (is_a($this->name, $baseClass, true)) {
                return $package;
            }
        }
        return '\\UNKNOWN';
    }

    /**
     * @see $instance
     * @return object|null
     */
    protected function resolveInstance()
    {
        $ref = $this->reflection;
        if ($ref->isInstantiable()) {
            try {
                return $ref->newInstance();
            } catch(\ArgumentCountError $e) {
                return $ref->newInstanceWithoutConstructor();
            }
        }
        return null;
    }

    /**
     * @see $tableNs
     * @return ReflectionNamespace
     */
    protected function resolveTableNs(): ReflectionNamespace
    {
        return $this->provider->ns($this->properties['tableNs'] ?? null);
    }

    /**
     * @see $fieldNs
     * @return ReflectionNamespace
     */
    protected function resolveFieldNs(): ReflectionNamespace
    {
        $use = substr($this->tableNs, 0, -1);
        return $this->provider->ns(($this->properties['fieldNs'] ?? null) ?: $this->name, ['use' => [$use]]);
    }

    /**
     * @see $table
     * @return Table|null
     */
    protected function resolveTable(): ?Table
    {
        if (($model = $this->instance) && $model instanceof BaseModel && $tableName = $model->getCoreTableName()) {
            $table = $this->provider->table($tableName, ['class' => $this]);
            $table->const->add('docBlock', "@see \\{$this->name}::__construct");
            return $table;
        }
        return null;
    }

    /**
     * @see $fields
     * @return Php\Map
     */
    protected function resolveFields(): Php\Map
    {
        return Php::map(function () {
            if ($this->reflection->isSubclassOf(BaseModel::class)) {
                $prefix = $this->fieldNs->shortName === $this->shortName . '\\' ? '' : $this->shortName . '_';
                foreach ($this->instance->getFieldNames() as $fieldName) {
                    $name = strtoupper($prefix . Oxidio\Oxidio::after($fieldName, 'ox'));
                    yield $fieldName => $this->provider->const([$this->fieldNs, $name], [
                        'value' => "'$fieldName'",
                        'docBlock' => ["@see \\{$this->name}"]
                    ]);
                }
            }
        });
    }

    /**
     * @see $template
     * @return string|null
     */
    protected function resolveTemplate(): ?string
    {
        return $this->instance instanceof BaseController ? $this->instance->getTemplateName() : null;
    }
}
