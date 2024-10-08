<?php
namespace Aequation\WireBundle\Tools;

use Aequation\WireBundle\Tools\interface\ToolInterface;
// Symfony
use Symfony\Component\String\ByteString;

class Encoders implements ToolInterface
{

    public function __toString(): string
    {
        return Objects::getShortname(static::class, false);
    }


    /*************************************************************************************
     * PASSWORD
     *************************************************************************************/

    public static function generatePassword(
        int $length = 12,
        string $chars = null
    ): string
    {
        return ByteString::fromRandom($length, $chars)->toString();
    }

    /*************************************************************************************
     * UID
     *************************************************************************************/

	/**
	 * 
	 * @see https://jasonmccreary.me/articles/php-convert-uniqid-to-timestamp/
	 * $timestamp = substr(uniqid(), 0, -5);
	 * echo date('r', hexdec($timestamp));  // Thu, 05 Sep 2013 15:55:04 -0400
	 */
	public static function geUniquid($prefix = "", string $separator = '.') {
		if(is_object($prefix)) $prefix = spl_object_hash($prefix).'_'.Times::getMicrotimeid().'@';
		if(!is_string($prefix)) $prefix = md5(json_encode($prefix)).'_'.Times::getMicrotimeid().'@';
        if(empty($prefix)) $prefix = 'UID';
		$uniquid = uniqid($prefix, true);
        if($separator !== '.') $uniquid = preg_replace('/\.+/', $separator, $uniquid);
        return $uniquid;
	}

        /**
     * Is EUID valid format
     * Ex. Aequation\LaboBundle\Model\User|65a8d53a34fc58.63711012
     * @param mixed $euid
     * @return boolean
     */
    public static function isEuidFormatValid(mixed $euid): bool
    {
        return is_string($euid) && preg_match('/^([a-zA-Z0-9\\\\]+)\\|([a-f0-9]{14}\\.\\d{8})$/', $euid);
    }

    /**
     * Get classname in EUID
     * @param string $euid
     * @return string|null
     */
    public static function getClassOfEuid(string $euid): ?string
    {
        return static::isEuidFormatValid($euid)
            ? preg_replace('/^([a-zA-Z0-9\\\\]+)\\|([a-f0-9]{14}\\.\\d{8})$/', '$1', $euid)
            : null;
        
    }


    /*************************************************************************************
     * JSON
     *************************************************************************************/

    /**
     * Is a valid Json
     * Target Version: PHP 8.3
     * @param mixed $json
     * @return boolean
     */
    public static function isJson(mixed $json): bool
    {
        return is_string($json)
            ? json_validate($json)
            : false;
        // json_decode($json);
        // return json_last_error() === JSON_ERROR_NONE;
    }

    public static function fromJson(mixed $json): mixed
    {
        return is_string($json) && json_validate($json)
            ? json_decode($json)
            : $json;
    }

}