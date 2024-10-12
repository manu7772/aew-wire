<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitScreenableInterface;
use Aequation\WireBundle\Entity\interface\WireHtmlcodeInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
// PHP
use Exception;

trait Screenable
{

    public const HTML_TYPE = null;

    #[ORM\ManyToOne(targetEntity: WireWebpageInterface::class)]
    #[ORM\JoinColumn(nullable: false)]
    protected ?WireWebpageInterface $webpage = null;

    public function __construct_screenable(): void
    {
        if(!($this instanceof TraitScreenableInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitScreenableInterface::class]));
    }

    public function setWebpage(WireHtmlcodeInterface $webpage): static
    {
        $this->webpage = $webpage;
        return $this;
    }

    public function getWebpage(): WireHtmlcodeInterface
    {
        return $this->webpage;
    }


    // public function __clone_Screenable(): void
    // {
    //     // 
    // }


}