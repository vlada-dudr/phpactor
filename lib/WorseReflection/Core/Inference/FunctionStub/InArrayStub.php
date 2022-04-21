<?php

namespace Phpactor\WorseReflection\Core\Inference\FunctionStub;

use Microsoft\PhpParser\Node\DelimitedList\ArgumentExpressionList;
use Microsoft\PhpParser\Node\Expression\ArgumentExpression;
use Phpactor\WorseReflection\Core\Inference\Frame;
use Phpactor\WorseReflection\Core\Inference\FunctionStub;
use Phpactor\WorseReflection\Core\Inference\NodeContext;
use Phpactor\WorseReflection\Core\Inference\NodeContextFactory;
use Phpactor\WorseReflection\Core\Inference\NodeContextResolver;
use Phpactor\WorseReflection\Core\Inference\TypeAssertion;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\TypeFactory;
use Phpactor\WorseReflection\Core\Type\ArrayLiteral;
use Phpactor\WorseReflection\Core\Type\BooleanLiteralType;
use Phpactor\WorseReflection\Core\Type\Literal;
use Phpactor\WorseReflection\Core\Type\ScalarType;

class InArrayStub implements FunctionStub
{
    public function resolve(
        NodeContextResolver $resolver,
        Frame $frame,
        NodeContext $context,
        ArgumentExpressionList $node
    ): NodeContext {
        $context = $context->withName('in_array');
        $context = $context->withType(TypeFactory::bool());

        $args = [];
        foreach ($node->getChildNodes() as $expression) {
            if (!$expression instanceof ArgumentExpression) {
                continue;
            }

            $args[] = $resolver->resolveNode($frame, $expression);
        }

        if (isset($args[0]) && isset($args[1])) {
            $valueType = $args[0]->type();
            $arrayType = $args[1]->type();
            if (!$arrayType instanceof ArrayLiteral) {
                return $context;
            }

            if ($valueType instanceof Literal && $valueType instanceof ScalarType) {
                $context = $context->withType(
                    new BooleanLiteralType(
                        in_array($valueType->value(), $arrayType->value())
                    )
                );
            }

            foreach ($args[0]->typeAssertions() as $typeAssertion) {
                $context = $context->withTypeAssertion(TypeAssertion::forContext($args[0], function (Type $type) use ($arrayType) {
                    return TypeFactory::union(...$arrayType->iterableValueTypes());
                }, function (Type $type) {
                    return $type;
                }));
            }
        }

        return $context;
    }
}
