<?php

namespace rg\injektor;

use ReflectionNamedType;
use ReflectionParameter;
use UnexpectedValueException;
use function get_class;
use function method_exists;

/**
 * @copyright ResearchGate GmbH
 */
class ReflectionClassHelper
{
    /**
     * @throws UnexpectedValueException
     */
    public static function getClassNameFromReflectionParameter(ReflectionParameter $parameter): ?string
    {
        $reflectionType = $parameter->getType();
        if ($reflectionType === null) {
            return null;
        } elseif ($reflectionType instanceof ReflectionNamedType) {
            if ($reflectionType->isBuiltin() === false) {
                return $reflectionType->getName();
            }
        } elseif (method_exists($reflectionType, 'getTypes')) {
            foreach($reflectionType->getTypes() as $type) {
                if ($type->isBuiltin() === false) {
                    return $type->getName();
                }
            }
        } else {
            throw new UnexpectedValueException('Unsupported Reflection type: ' . get_class($reflectionType));
        }

        return null;
    }
}
