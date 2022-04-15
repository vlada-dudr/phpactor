<?php

namespace Phpactor\WorseReflection\Core\Reflection\TypeResolver;

use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\Type\ClassType;
use Phpactor\WorseReflection\Core\Type\GenericClassType;
use Phpactor\WorseReflection\Core\Util\NodeUtil;
use Phpactor\WorseReflection\TypeUtil;

class GenericHelper
{
    public static function resolveMethodType(ReflectionClassLike $class, ReflectionClassLike $declaringClass, Type $type): Type
    {
        if (!$type instanceof ClassType) {
            return $type;
        }

        $parameterName = TypeUtil::short($type);

        if ($class->templateMap()->has($parameterName)) {
            $methodType = $class->templateMap()->get($parameterName, $class->arguments());
            if (TypeUtil::isDefined($methodType)) {
                return $methodType;
            }
        }

        $extendsType = $class->docblock()->extends();
        $extendsType = $class->scope()->resolveFullyQualifiedName($extendsType);

        if ($extendsType instanceof GenericClassType) {
            $arguments = $extendsType->arguments();
            return $declaringClass->templateMap()->get($type->__toString(), $arguments);
        }

        $implements = $class->docblock()->implements();

        foreach ($implements as $implementsType) {
            $implementsType = $class->scope()->resolveFullyQualifiedName($implementsType);
            if (!$implementsType instanceof GenericClassType) {
                continue;
            }

            if ($implementsType->name()->full() === $declaringClass->name()->__toString()) {
                $arguments = $implementsType->arguments();
                return $declaringClass->templateMap()->get($type->__toString(), $arguments);
            }
        }

        return $type;
    }
}
