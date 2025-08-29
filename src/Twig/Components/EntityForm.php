<?php
namespace Aequation\WireBundle\Twig\Components;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Form\WireUserType;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Tools\Encoders;
// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\Component\Form\SubmitButton;
use InvalidArgumentException;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: 'wire:entity-form',
    template: '@AequationWire/components/entity-form.html.twig'
)]
class EntityForm extends AbstractController
{

    use ComponentWithFormTrait;
    use DefaultActionTrait;
    use LiveCollectionTrait;

    // #[LiveProp()]
    public ?string $registeredMessage = null;
    public ?string $errorMessage = null;
    #[LiveProp()]
    public string $baseEntity; // classname (for new entity) or EUID
    #[LiveProp()]
    public string $classname; // classname
    public BaseEntityInterface $entity;
    public WireEntityServiceInterface $service;
    public array $options = [];

    public function __construct(
        private WireEntityManagerInterface $wireEm
    ) {}

    public function getEntityService(): WireEntityServiceInterface
    {
        return $this->service ??= $this->wireEm->getEntityService($this->getClassname());
    }

    public function getClassname(): string
    {
        if(!isset($this->classname)) {
            if(Encoders::isEuidFormatValid($this->baseEntity)) {
                $this->classname = Encoders::getClassOfEuid($this->baseEntity);
            } else {
                $this->classname = $this->baseEntity;
            }
            if(!is_a($this->classname, BaseEntityInterface::class, true)) {
                throw new InvalidArgumentException(vsprintf('Error %s line %d: The class "%s" must implement "%s".', [__METHOD__, __LINE__, $this->classname, BaseEntityInterface::class]));
            }
        }
        return $this->classname;
    }

    public function getEntity(): BaseEntityInterface
    {
        if(!isset($this->entity)) {
            if(Encoders::isEuidFormatValid($this->baseEntity)) {
                // Retrieve the entity by EUID
                $this->entity = $this->wireEm->findByEuid($this->baseEntity);
                if(!$this->entity) {
                    throw new InvalidArgumentException(vsprintf('Error %s line %d: No entity found for EUID "%s".', [__METHOD__, __LINE__, $this->baseEntity]));
                }
            } else {
                // If the entity is a classname, we assume it's a new entity
                $this->entity = $this->getEntityService()->createEntity();
            }
        }
        return $this->entity;
    }

    public function getEntityType(): string
    {
        return $this->getEntityService()->getEntityType(); // or any other form type you want to use
    }

    public function mount(
        ?BaseEntityInterface $entity = null, // Entity object
        ?string $baseEntity = null, // EUID or classname
        array $options = []
    ): void
    {
        if($baseEntity) {
            if(!is_a($baseEntity, BaseEntityInterface::class, true) && !Encoders::isEuidFormatValid($baseEntity)) {
                throw new InvalidArgumentException(vsprintf('Error %s line %d: The parameter $baseEntity must be a valid EUID or a class implementing "%s".', [__METHOD__, __LINE__, BaseEntityInterface::class]));
            }
        }
        if($entity) {
            $this->entity = $entity;
            if($baseEntity) {
                if($entity->getEuid() !== $baseEntity && $entity->getClassname() !== $baseEntity) {
                    throw new InvalidArgumentException(vsprintf('Error %s line %d: The given entity (%s) does not match the given baseEntity (%s).', [__METHOD__, __LINE__, $entity->getEuid(), $baseEntity]));
                }
            }
            $baseEntity = $this->entity->getSelfState()->isLoaded() ? $this->entity->getEuid() : $this->entity->getClassname();
        }
        $this->baseEntity = $baseEntity;
        $this->getEntity();
        // dump($this->entity);
        $this->options = $options;
    }

    public function isNewEntity(): bool
    {
        return $this->getEntity()->getSelfState()->isNew();
    }
    
    public function instantiateForm(): FormInterface
    {
        // Assuming the form is created based on the entity type
        // dump($this);
        return $this->createForm($this->getEntityType(), $this->getEntity(), $this->options);
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    #[LiveAction]
    public function registerType(
        // #[LiveArg('submit')] ?string $submit = null,
    )
    {
        $this->registeredMessage = null;
        $this->errorMessage = null;

        $this->submitForm();
        /** @var Form $form */
        $form = $this->getForm();

        if($form->isSubmitted()) {
            if($form->isValid()) {
                // save to the database
                // or, instead of creating a LiveAction, allow the form to submit
                // to a normal controller: that's even better.
                /** @var BaseEntityInterface $entity */
                $entity = $form->getData();
                $em = $this->wireEm->getEntityManager();
                $em->persist($entity);
                try {
                    $em->flush();
                    return $this->redirectToRoute('admin_'.$entity->getShortname(true).'_show', ['id' => $entity->getId()]);
                } catch (\Throwable $th) {
                    $this->errorMessage = "Des erreurs à l'enregistrement ont été détectées.";
                    // $this->resetForm();
                }
            } else {
                // Handle validation errors
                $this->errorMessage = "Des erreurs de validation ont été détectées.";
            }
        }
        $this->resetForm();
    }

}