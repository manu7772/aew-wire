<?php
namespace Aequation\WireBundle\Service\interface;


interface ServerInfoInterface extends WireServiceInterface
{
    public function getSymfonyInfo(?string $name = null): mixed;
    public function getPhpInfo(?string $name = null): mixed;
    public function getDatabaseInfo(?string $name = null): mixed;

}