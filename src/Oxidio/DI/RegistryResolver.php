<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\DI;

use Generator;
use OxidEsales\Eshop\Core;
use Psr\{Container\ContainerInterface, Log\LoggerInterface};
use ReflectionParameter;
use Php;

/**
 * @property-read ContainerInterface $container
 */
class RegistryResolver
{
    use Php\PropertiesTrait\ReadOnly;

    /**
     * @var string[]
     */
    public const DEFINITIONS = [
        Core\Config::class => 'getConfig',
        Core\Session::class => 'getSession',
        Core\Language::class => 'getLang',
        Core\Utils::class => 'getUtils',
        Core\UtilsObject::class => 'getUtilsObject',
        Core\InputValidator::class => 'getInputValidator',
        Core\PictureHandler::class => 'getPictureHandler',
        Core\Request::class => 'getRequest',
        Core\SeoEncoder::class => 'getSeoEncoder',
        Core\SeoDecoder::class => 'getSeoDecoder',
        Core\UtilsCount::class => 'getUtilsCount',
        Core\UtilsDate::class => 'getUtilsDate',
        Core\UtilsFile::class => 'getUtilsFile',
        Core\UtilsPic::class => 'getUtilsPic',
        Core\UtilsServer::class => 'getUtilsServer',
        Core\UtilsString::class => 'getUtilsString',
        Core\UtilsUrl::class => 'getUtilsUrl',
        Core\UtilsXml::class => 'getUtilsXml',
        Core\UtilsView::class => 'getUtilsView',
        Core\Routing\ControllerClassNameResolver::class => 'getControllerClassNameResolver',
        LoggerInterface::class => 'getLogger',
        Core\ConfigFile::class => 'get',
    ];

    /**
     * @see $container
     * @return ContainerInterface
     */
    protected function resolveContainer(): ContainerInterface
    {
        $defs = Php::arr(static::DEFINITIONS, static function (string $method, string $class) {
            yield static function () use ($method, $class) {
                return Core\Registry::$method($class);
            };
        });
        return Php::di($defs, [static::class => $this]);
    }

    /**
     * @see GeneratorResolver
     * @param ReflectionParameter $parameter
     *
     * @return Generator
     */
    public function __invoke(ReflectionParameter $parameter): Generator
    {
        if (($class = $parameter->getClass()) && $this->container->has($class->getName())) {
            yield $this->container->get($class->getName());
        }
    }
}
