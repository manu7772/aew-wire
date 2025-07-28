<?php
namespace Aequation\WireBundle\Interface;


interface MenuShapeInterface
{

    public function getName(): string;
    public function getTitle(): string;
    public function getLinktitle(): string;
    public function getItems(): iterable;

}