<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WireWebsection;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireWebsectionServiceInterface;
// Symfony
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class WireWebsectionType extends WireAbstractType
{

    public const ENTITY_CLASS = WireWebsection::class;

    /** @var WireWebsectionServiceInterface */
    protected readonly WireEntityServiceInterface $entityService;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WireWebsection */
        // $websection = $builder->getData();

        $builder->add('name', null, [
            'label' => 'fields.name',
            'required' => true,
            'priority' => 100
        ]);
        $builder->add('title', null, [
            'label' => 'fields.title',
            'required' => true,
            'priority' => 90
        ]);
        $builder->add('twigfile', ChoiceType::class, [
            'label' => 'fields.twigfile',
            // 'label_attr' => ['class' => 'fieldset-legend'],
            // 'attr' => ['class' => 'select'],
            'choices' => $this->entityService->getWebsectionModels(),
            'multiple' => false,
            'expanded' => false,
            'required' => true,
            'priority' => 70
        ]);
        $builder->add('mainmenu', EntityType::class, [
            'label' => 'fields.mainmenu',
            'by_reference' => true,
            'class' => $this->wireEm->findOneFinal(WireMenuInterface::class)->name,
            'choice_label' => 'name',
            'multiple' => false,
            'expanded' => false,
            'required' => true,
            'help' => 'Choisissez le menu principal pour cette page web',
            'priority' => 45
        ]);
        $builder->add('enabled', CheckboxType::class, [
            'label' => 'fields.enabled',
            'required' => false,
            'help' => 'Activer/désactiver la page web',
            'priority' => 40
        ])
        ->add('submit', SubmitType::class, [
            'label' => 'actions.save',
            'priority' => -2
        ])
        ;

        $this->defaultListeners($builder);

    }
}
