<?php
namespace Aequation\WireBundle\Attribute;

use Aequation\WireBundle\Event\WireEntityEvent;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
// PHP
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CurrentUser extends BasePropertyAttribute
{

    public function __construct(
        public bool $replace = false,
        public bool $required = false
    ) {
    }

    // public function apply(
    //     WireEntityEvent $event
    // ): void
    // {
    //     $previous_user = $this->getvalue();
    //     if(empty($previous_user) || $this->replace) {
    //         /** @var WireUserServiceInterface */
    //         $userService = $event->wireEntityManager->getEntityService(WireUserServiceInterface::class);
    //         $user = $userService->getUser();
    //         if($user) {
    //             $this->setValue($user);
    //         } else if(empty($previous_user) && $this->required) {
    //             $user = $userService->getMainAdminUser(true);
    //             if($user) {
    //                 $this->setValue($user);
    //             } else {
    //                 // ...
    //             }
    //         }
    //     }
    // }

}