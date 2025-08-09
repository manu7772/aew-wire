<?php
namespace Aequation\WireBundle\Dto\transform;

use Aequation\WireBundle\Entity\TextContents;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/**
 * @implements TransformCallableInterface<UserInput, User>
 */
class TextContentsTransformer implements TransformCallableInterface
{

    // public function __construct(
    //     public readonly WireEntityManagerInterface $wireEm,
    // ) {
    // }

    public function __invoke(mixed $value, object $source, ?object $target): mixed
    {
        // dump($value, $source, $target);
        // switch (true) {
        //     case empty($value):
        //         return [];
        //         break;
        //     case !is_array($value):
        //         $value = explode('\n', (string) $value);
        //         break;
        // }
        return $value;
    }
}