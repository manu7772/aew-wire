<?php
namespace Aequation\WireBundle\Service\interface;

use DateTimeZone;

interface TimezoneInterface
{

    public function getDateTimezone(): ?DateTimeZone;
    public function getTimezone(): ?string;

}