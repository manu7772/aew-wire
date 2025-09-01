<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WireMenu;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Service\interface\WireMenuServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class WireMenuType extends WireAbstractType
{

    public const ENTITY_CLASS = WireMenu::class;

    /** @var WireMenuServiceInterface */
    protected readonly WireEntityServiceInterface $entityService;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WireMenu */
        // $menu = $builder->getData();
        $builder
            ->add('name', null, [
                'label' => 'fields.name',
                'required' => true,
                'priority' => 25
            ])
            ->add('title', null, [
                'label' => 'fields.title',
                'required' => true,
                'priority' => 25
            ])
            ->add('linktitle', null, [
                'label' => 'fields.linktitle',
                'required' => true,
                'priority' => 25
            ])
            ->add('items', EntityType::class, [
                'class' => $this->wireEm->findOneFinal(WireWebpageInterface::class)->name,
                'label' => 'fields.items',
                'required' => false,
                'multiple' => true,
                'expanded' => false,
                'priority' => 5
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'actions.save',
                'priority' => -2
            ])
        ;

        $this->defaultListeners($builder);

    }

}
