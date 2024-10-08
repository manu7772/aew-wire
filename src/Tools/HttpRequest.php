<?php
namespace Aequation\WireBundle\Tools;

use Aequation\WireBundle\Tools\interface\ToolInterface;

class HttpRequest implements ToolInterface
{

    public function __toString(): string
    {
        return Objects::getShortname(static::class, false);
    }


    /*************************************************************************************
     * IP
     *************************************************************************************/

    public static function getIp(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /*************************************************************************************
     * CLI
     *************************************************************************************/

    /**
     * Is command line
     * @see PHP php_sapi_name() or PHP_SAPI
     */
    public static function isCli(): bool
    {
        return strtolower(PHP_SAPI) === 'cli';
    }



}