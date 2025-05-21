<?php
namespace Aequation\WireBundle\Entity\interface;


interface TraitUnamedInterface extends WireEntityInterface
{

    public function __construct_unamed(): void;
    // Interface of all entities
    public function updateUname(null|UnameInterface|string $uname = null): static;
    /**
     * Set the Uname object
     * 
     * @param UnameInterface|string $uname
     * @return static
     */
    public function setUname(UnameInterface|string $uname): static;
    /**
     * Get the Uname object
     * 
     * @return UnameInterface|null
     */
    public function getUname(): ?UnameInterface;
    /**
     * Get the Uname name
     * 
     * @return string|null
     */
    public function getUnameName(): ?string;

}