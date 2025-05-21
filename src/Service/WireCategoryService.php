<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Entity\interface\TraitCategorizedInterface;
use Aequation\WireBundle\Entity\interface\WireCategoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireCategoryServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Knp\Component\Pager\PaginatorInterface;

abstract class WireCategoryService implements WireCategoryServiceInterface
{
    use TraitBaseService;
    use TraitBaseEntityService;
    
    public const ENTITY_CLASS = WireCategoryInterface::class;

    public readonly array $availableTypes;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEm,
        protected PaginatorInterface $paginator,
    ) {
    }
    
    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        $opresult ??= new Opresult();
        // Check all WireCategoryInterface entities
        $all = $this->getRepository()->findAll();
        $repaired = 0;
        foreach ($all as $entity) {
            // Repair category type
            if(!class_exists($entity->getType())) {
                $opresult->addWarning("Category type {$entity->getType()} does not exist");
                if($repair) {
                    $entity->setType($entity->getType());
                    $repaired++;
                }
            }
        }
        if($repaired > 0) {
            $this->getEntityManager()->flush();
            $opresult->addWarning("Repaired $repaired category type(s)");
        } else {
            $opresult->addSuccess("All category types are valid");
        }
        $this->wireEm->decDebugMode();
        return $opresult;
    }

    /**
     * Get available category types
     * - returns final entities related to WireCategoryInterface AND instance of TraitCategorizedInterface
     * 
     */
    public function getAvailableTypes(
        bool $asShornames = true
    ): array
    {
        if(!isset($this->availableTypes)) {
            $relateds = $this->wireEm->getRelateds(
                static::ENTITY_CLASS,
                fn(AssociationMapping $mapping, ClassMetadata $cmd) => count($cmd->subClasses) === 0 && !$cmd->isMappedSuperclass,
                true
            );
            $availableTypes = [];
            foreach (array_keys($relateds) as $class) {
                if(is_a($class, TraitCategorizedInterface::class, true)) {
                    $availableTypes[$class] = $asShornames ? Objects::getShortname($class, false) : $class;
                }
            }
            $this->availableTypes = $availableTypes;
        }
        return $this->availableTypes;
    }

    /**
     * Get category type choices
     * 
     * @return array
     */
    public function getCategoryTypeChoices(): array
    {
        $choices = $this->getAvailableTypes(true);
        return array_flip($choices);
    }

}