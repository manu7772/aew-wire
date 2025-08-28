<?php
namespace Aequation\WireBundle\Security\Voter;

use Aequation\WireBundle\Entity\WireLanguage;
// Symfony
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class WireLanguageVoter extends BaseEntityVoter
{

    public const ENTITY_CLASS = WireLanguage::class;

    public function voteOnAttribute(
        string $subject,
        mixed $attribute,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool
    {
        if(!$this->appWire->isTranslate() && !$this->appWire->isGranted('ROLE_SUPER_ADMIN')) {
            $vote->addReason('Translations are disabled on this website.');
            return false;
        }

        return parent::voteOnAttribute($subject, $attribute, $token, $vote);
    }


}