<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WireSlider;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Service\interface\WireSliderServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class WireSliderType extends WireAbstractType
{

    public const ENTITY_CLASS = WireSlider::class;

    /** @var WireSliderServiceInterface */
    protected readonly WireEntityServiceInterface $entityService;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WireSlider */
        // $slider = $builder->getData();
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
