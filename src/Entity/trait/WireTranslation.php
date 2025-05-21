<?php
namespace Aequation\WireBundle\Entity\trait;

trait WireTranslation
{

    public function __toString(): string
    {
        return (string)$this->content;
    }

}
