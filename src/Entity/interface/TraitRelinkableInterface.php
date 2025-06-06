<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;

interface TraitRelinkableInterface extends BaseEntityInterface
{
    public function __construct_relinkable(): void;
    public function getRelinks(): Collection;
    public function addRelink(WireRelinkInterface $relink): bool;
    public function hasRelink(WireRelinkInterface $relink): bool;
    public function removeRelink(WireRelinkInterface $relink): bool;
    public function setRelinkPosition(WireRelinkInterface $relink, int $position): bool;
    public function getRelinkPosition(WireRelinkInterface $relink): ?int;

    // AddressLink
    public function getAddresses(): Collection;
    public function getPreferedAddresse(bool $firstIfNoPrefered = true): ?WireAddresslinkInterface;
    public function setAddresses(Collection $relinks): static;
    public function addAddresse(WireAddresslinkInterface $relink): bool;
    public function removeAddresse(WireAddresslinkInterface $relink): bool;
    // PhoneLink
    public function getPhones(): Collection;
    public function getPreferedPhone(bool $firstIfNoPrefered = true): ?WirePhonelinkInterface;
    public function setPhones(Collection $relinks): static;
    public function addPhone(WirePhonelinkInterface $relink): bool;
    public function removePhone(WirePhonelinkInterface $relink): bool;
    // EmailLink
    public function getEmails(): Collection;
    public function getPreferedEmail(bool $firstIfNoPrefered = true): ?WireEmailinkInterface;
    public function setEmails(Collection $relinks): static;
    public function addEmail(WireEmailinkInterface $relink): bool;
    public function removeEmail(WireEmailinkInterface $relink): bool;
    // UrlLink
    public function getUrls(): Collection;
    public function getPreferedUrl(bool $firstIfNoPrefered = true): ?WireUrlinkInterface;
    public function setUrls(Collection $relinks): static;
    public function addUrl(WireUrlinkInterface $relink): bool;
    public function removeUrl(WireUrlinkInterface $relink): bool;
    // RsLink
    public function getRsocs(): Collection;
    public function getPreferedRsoc(bool $firstIfNoPrefered = true): ?WireRslinkInterface;
    public function setRsocs(Collection $relinks): static;
    public function addRsoc(WireRslinkInterface $relink): bool;
    public function removeRsoc(WireRslinkInterface $relink): bool;
}