<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Meta;

use Generator;
use Oxidio;
use Php;

/**
 * @property-read ReflectionNamespace $namespace
 * @property-read string[]            $docBlock
 * @property-read string              $shortName
 * @property-read string              $value
 */
class ReflectionConstant
{
    use ReflectionTrait;

    protected const DEFAULT = ['docBlock' => [], 'value' => null];

    public function setValue($value, $export = false): self
    {
        $this->properties['value'] = $export ? var_export($value, true) : $value;
        return $this;
    }

    /**
     * @see $namespace
     * @return ReflectionNamespace
     */
    protected function resolveNamespace(): ReflectionNamespace
    {
        $name = $this->properties['name'] ?? null;
        $last = strrpos($name, '\\');
        $last = substr($name, 0, $last);
        return $this->provider->ns($last)->add('constants', $this);
    }

    /**
     * @see $name
     * @return string
     */
    protected function resolveName(): string
    {
        return Php\Lang::sanitize($this->properties['name'] ?? '');
    }

    /**
     * @see $shortName
     * @return string
     */
    protected function resolveShortName(): string
    {
        return $this->namespace->relative($this->name);
    }

    public function toPhp(): Generator
    {
        yield '    /**';
        foreach ($this->docBlock as $line) {
            $line = trim($line);
            yield $line ? "     * $line" : '     *';
        }
        yield '     * @deprecated';
        yield '     */';
        yield "    const {$this->shortName} = {$this->value};";
    }
}
