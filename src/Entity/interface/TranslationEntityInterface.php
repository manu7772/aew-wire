<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;

interface TranslationEntityInterface
{
    public function getTranslations(): Collection;
    public function addTranslation(WireTranslationInterface $t);

}