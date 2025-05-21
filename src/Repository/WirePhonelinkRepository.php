<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WirePhonelink;
use Aequation\WireBundle\Repository\interface\WirePhonelinkRepositoryInterface;

abstract class WirePhonelinkRepository extends WireRelinkRepository implements WirePhonelinkRepositoryInterface
{

    const NAME = WirePhonelink::class;
    const ALIAS = 'wirephonelink';

}