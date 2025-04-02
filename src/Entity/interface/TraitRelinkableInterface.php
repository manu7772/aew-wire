<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;

interface TraitRelinkableInterface extends WireEntityInterface
{
    public function __construct_relinkable(): void;
    public function getRelinks(): Collection;
    public function addRelink(WireRelinkInterface $relink): bool;
    public function hasRelink(WireRelinkInterface $relink): bool;
    public function removeRelink(WireRelinkInterface $relink): bool;

    // AddressLink
    public function getAddresses(): Collection;
    public function setAddresses(Collection $relinks): static;
    public function addAddresse(WireAddresslinkInterface $relink): bool;
    public function removeAddresse(WireAddresslinkInterface $relink): bool;
    // PhoneLink
    public function getPhones(): Collection;
    public function setPhones(Collection $relinks): static;
    public function addPhone(WirePhonelinkInterface $relink): bool;
    public function removePhone(WirePhonelinkInterface $relink): bool;
    // EmailLink
    public function getEmails(): Collection;
    public function setEmails(Collection $relinks): static;
    public function addEmail(WireEmailinkInterface $relink): bool;
    public function removeEmail(WireEmailinkInterface $relink): bool;
    // UrlLink
    public function getUrls(): Collection;
    public function setUrls(Collection $relinks): static;
    public function addUrl(WireUrlinkInterface $relink): bool;
    public function removeUrl(WireUrlinkInterface $relink): bool;
    // RsLink
    public function getRs(): Collection;
    public function setRs(Collection $relinks): static;
    public function addRs(WireRslinkInterface $relink): bool;
    public function removeRs(WireRslinkInterface $relink): bool;
}