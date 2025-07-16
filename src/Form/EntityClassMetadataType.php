<?php
namespace Aequation\WireBundle\Form;

use Symfony\Component\Form\AbstractType;
// Symfony
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;

class EntityClassMetadataType extends AbstractType
{

    public function __construct(
        protected WireEntityManagerInterface $wireEntityManager
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $wCmdm = $this->wireEntityManager->getEntitiesMetadata();
        $builder
            ->add('classes', ChoiceType::class, [
                'label' => 'Classes',
                'choices' => $wCmdm->getClassnameChoices(),
                'choice_translation_domain' => false,
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'priority' => 100,
                'attr' => [
                    'data-action' => "live#update",
                ],
            ])
            ->add('interfaces', ChoiceType::class, [
                'label' => 'Interfaces',
                'choices' => $wCmdm->getInterfaceChoices(),
                'choice_translation_domain' => false,
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'priority' => 90,
                'attr' => [
                    'data-action' => "live#update",
                ],
            ])
            ->add('type_comparison', CheckboxType::class, [
                'label' => 'Comparison And/Or',
                'data' => true,
                'required' => false,
                'priority' => 80,
                'attr' => [
                    'data-action' => "live#update",
                ],
            ])
            // ->add('type', ChoiceType::class, [
            //     'label' => 'Type',
            //     'choices' => $wCmdm->getTypeChoices(),
            //     'expanded' => false,
            //     'multiple' => false,
            //     'required' => true,
            //     'priority' => 70,
            //     'attr' => [
            //         'data-action' => "live#update",
            //     ],
            // ])
            ->add('mode', ChoiceType::class, [
                'label' => 'Mode',
                'data' => 'final',
                'choices' => $wCmdm->getModeChoices(),
                'choice_translation_domain' => false,
                'expanded' => false,
                'multiple' => false,
                'required' => true,
                'priority' => 60,
                'attr' => [
                    'data-action' => "live#update",
                ],
            ]);
    }

}