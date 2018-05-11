<?php

namespace common\components\mailing;

use ReflectionClass;
use ReflectionException;

/**
 * Trait IsTrait
 * @package common\components\mailing
 */
trait IsTrait
{
    /**
     * @param string|ReflectionClass $name
     * @param string $implement
     * @return bool
     */
    protected function is($name, $implement)
    {
        try {
            $reflection = $name instanceof ReflectionClass
                ? $name
                : new ReflectionClass($name);
            return $reflection->getName() === $implement || in_array($implement, $reflection->getInterfaceNames(), true)
                ? true
                : $this->is($reflection->getParentClass(), $implement);
        } catch (ReflectionException $exception) {
            return false;
        }
    }
}