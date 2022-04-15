<?php

namespace Phpactor\WorseReflection\Core\Reflection\TypeResolver;

use Microsoft\PhpParser\Node\Expression\ArgumentExpression;
use Microsoft\PhpParser\Node\DelimitedList\ArgumentExpressionList;
use Phpactor\WorseReflection\Core\Inference\Frame;
use Phpactor\WorseReflection\Core\Inference\NodeContextResolver;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\WorseReflection\Core\Reflection\ReflectionParameter;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\Type\ArrayType;
use Phpactor\WorseReflection\Core\Type\ClassType;
use Phpactor\WorseReflection\Core\Type\GenericClassType;
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

    /**
     * @return Type[]
     */
    public static function arguments(
        NodeContextResolver $resolver,
        Frame $frame,
        ?ArgumentExpressionList $argumentExpressionList
    ): array {
        if (null === $argumentExpressionList) {
            return [];
        }

        $arguments = [];
        foreach ($argumentExpressionList->getElements() as $argument) {
            if (!$argument instanceof ArgumentExpression) {
                continue;
            }

            $type = $resolver->resolveNode($frame, $argument)->type();

            if ($type instanceof ArrayType) {
                $type = TypeUtil::generalize($type->valueType);
            }

            $arguments[] = $type;
        }


        return $arguments;
    }

    /**
     * @param Type[] $arguments
     * @return Type[]
     */
    public static function argumentsForMethod(array $arguments, ReflectionMethod $constructor): array
    {
        assert($constructor instanceof ReflectionMethod);
        $parameters = iterator_to_array($constructor->parameters());
        $index = -1;
        foreach ($parameters as $parameter) {
            $index++;
            assert($parameter instanceof ReflectionParameter);
            if (!$parameter->isGeneric()) {
                unset($arguments[$index]);
                continue;
            }
        }

        return array_values($arguments);
    }
}
