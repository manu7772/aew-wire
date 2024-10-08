<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\EntityCreatorInterface;

use function Symfony\Component\String\u;

class EntityCreator implements EntityCreatorInterface
{

    public string $namespace;
    // public array $class_uses = [];
    // public bool|string $extends = false;
    public array $implements = [];
    public array $constants = [];
    public array $traits = [];

    public function __construct(
        public string $shortname,
        protected array $data,
        protected array $options
    ) {
        $this->shortname = $this->normalizeName($shortname);
        // $this->namespace = $this->options['namespace'];
        // $this->extends = is_string($this->data['extends']) ? $this->extractShortname($this->data['extends']) : false;
        // $this->constants = $this->data['constants'];
        // $this->traits = $this->data['traits'];
        // $this->getClassUses();
    }

    private function normalizeName(
        string $string
    ): string
    {
        return u($string)->camel()->title();
    }

    public function getFilename(): string
    {
        return $this->getShortname().'.php';
    }

    public function getShortname(): string
    {
        return $this->normalizeName($this->shortname);
    }

    public function getNamespace(): string
    {
        return $this->options['namespace'];
    }

    public function getExtends(): string|false
    {
        return is_string($this->data['extends']) ? $this->extractShortname($this->data['extends']) : false;
    }

    public function getConstants(): array
    {
        return $this->data['constants'];
    }

    public function getTraits(): array
    {
        return $this->data['traits'];
    }

    public function getClassUses(): array
    {
        $class_uses = [];
        if(is_string($this->data['extends'])) $class_uses[] = $this->data['extends'];
        // sort($class_uses);
        return $class_uses;
    }


    private function extractShortname(
        string $classname
    ): string
    {
        return u($classname)->afterLast('\\', false);
    }

    private function extractNamespace(
        string $classname
    ): string
    {
        return u($classname)->beforeLast('\\', false);
    }

}