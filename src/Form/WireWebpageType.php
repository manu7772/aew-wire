<?php
namespace Aequation\WireBundle\Form;

use Symfony\Component\Form\AbstractType;
use Aequation\WireBundle\Entity\WireWebpage;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
// Symfony
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireWebpageServiceInterface;

class WireWebpageType extends WireAbstractType
{

    public const ENTITY_CLASS = WireWebpage::class;

    /** @var WireWebpageServiceInterface */
    protected readonly WireEntityServiceInterface $entityService;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WireWebpage */
        // $webpage = $builder->getData();

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
        $builder->add('linktitle', null, [
            'label' => 'fields.linktitle',
            'required' => false,
            'priority' => 80
        ]);
        $builder->add('twigfile', ChoiceType::class, [
            'label' => 'fields.twigfile',
            // 'label_attr' => ['class' => 'fieldset-legend'],
            'attr' => ['class' => 'select'],
            'choices' => $this->entityService->getWebpageModels(),
            'multiple' => false,
            'expanded' => false,
            'required' => true,
            'priority' => 70
        ]);
        // $wsClass = $this->wireEm->resolveFinalEntity(WireWebsectionInterface::class);
        // if($wsClass) {
            /** @see https://symfony.com/doc/current/reference/forms/types/choice.html */
            $builder->add('sections', EntityType::class, [
                'label' => 'fields.sections',
                'attr' => [
                    'class' => 'h-35'
                ],
                'by_reference' => true,
                'class' => $this->wireEm->findOneFinal(WireWebsectionInterface::class)->name,
                'choices' => $this->entityService->getWebsectionsChoices(),
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => true,
                'help' => 'Choisissez les sections contenues dans cette page web',
                'priority' => 50
            ]);
        // }
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
        ]);
        $builder->add('submit', SubmitType::class, [
            'label' => 'actions.save',
            'attr' => [
                'class' => 'btn btn-accent btn-block btn-lg mt-4',
            ],
            'priority' => -1
        ]);

    }

}
