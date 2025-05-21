<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Component\interface\OpresultInterface;

interface WireDatabaseCheckerInterface extends WireServiceInterface
{
    // Maintain database - global actions
    public function checkAllDatabase(?OpresultInterface $opresult = null, bool $repair = false): OpresultInterface;
    public function checkDatabase(string $classname, ?OpresultInterface $opresult = null, bool $repair = false): OpresultInterface;
    // Check interfaces
    public function database_check_prefered(string $classname, ?OpresultInterface $opresult = null, bool $repair = false): OpresultInterface;
    public function database_check_owner(string $classname, ?OpresultInterface $opresult = null, bool $repair = false): OpresultInterface;
}