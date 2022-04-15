<?php

namespace Phpactor\WorseReflection\Core\Inference\Resolver;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\ObjectCreationExpression;
use Phpactor\WorseReflection\Core\Exception\CouldNotResolveNode;
use Phpactor\WorseReflection\Core\Inference\Frame;
use Phpactor\WorseReflection\Core\Inference\NodeContext;
use Phpactor\WorseReflection\Core\Inference\Resolver;
use Phpactor\WorseReflection\Core\Inference\NodeContextResolver;
use Phpactor\WorseReflection\Core\Reflection\TypeResolver\GenericHelper;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\Type\GenericClassType;

class ObjectCreationExpressionResolver implements Resolver
{
    public function resolve(NodeContextResolver $resolver, Frame $frame, Node $node): NodeContext
    {
        assert($node instanceof ObjectCreationExpression);
        if (false === $node->classTypeDesignator instanceof Node) {
            throw new CouldNotResolveNode(sprintf('Could not create object from "%s"', get_class($node)));
        }

        $context = $resolver->resolveNode($frame, $node->classTypeDesignator);
        $type = $context->type();

        if ($type instanceof GenericClassType) {
            $arguments = $this->resolveArguments($type, $resolver, $frame, $node);
            $context = $context->withType($type->setArguments($arguments));
        }

        return $context;
    }

    /**
     * @return Type[]
     */
    private function resolveArguments(
        GenericClassType $type,
        NodeContextResolver $resolver,
        Frame $frame,
        ObjectCreationExpression $node
    ): array {
        $reflection = $reflection = $type->reflectionOrNull();
        if (!$reflection) {
            return [];
        }
        foreach ($reflection->methods()->byName('__construct') as $constructor) {
            $arguments = GenericHelper::arguments($resolver, $frame, $node->argumentExpressionList);
            return GenericHelper::argumentsForMethod($arguments, $constructor);
        }

        return [];
    }
}
