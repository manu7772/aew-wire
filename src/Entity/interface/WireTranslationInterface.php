<?php
namespace Aequation\WireBundle\Entity\interface;


interface WireTranslationInterface
{
    
    public function __construct($locale, $field, $value);
    public function getId();
    public function setLocale($locale);
    public function getLocale();
    public function setField($field);
    public function getField();
    public function setObject($object);
    public function getObject();
    public function setContent($content);
    public function getContent();

}