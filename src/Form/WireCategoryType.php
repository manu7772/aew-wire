<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WireCategory;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireCategoryServiceInterface;
// Symfony
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class WireCategoryType extends WireAbstractType
{

    public const ENTITY_CLASS = WireCategory::class;

    /** @var WireCategoryServiceInterface */
    protected readonly WireEntityServiceInterface $entityService;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WireCategory */
        // $category = $builder->getData();
        $builder
            ->add('name', null, [
                'label' => 'fields.name',
                'required' => true,
                'priority' => 25
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'fields.type',
                'choices' => $this->entityService->getCategoryTypeChoices(false, false, true),
                'help' => 'fields.help.type',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'priority' => 15
            ])
            ->add('description', TextareaType::class, [
                'label' => 'fields.description',
                'required' => false,
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
