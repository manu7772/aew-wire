<?php
namespace Aequation\WireBundle\Entity\interface;


interface WebsectionCollectionInterface extends BetweenSortedInterface
{
    public function __construct(WireWebpageInterface $webpage, WireWebsectionInterface $websection);
    public function getWebpage(): WireWebpageInterface;
    // public function getWebsection(): WireWebsectionInterface;
}