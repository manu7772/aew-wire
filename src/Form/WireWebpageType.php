<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\WireWebpage;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireWebpageServiceInterface;
// Symfony
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class WireWebpageType extends AbstractType
{

    public function __construct(
        private TranslatorInterface $translator,
        private WireEntityManagerInterface $wireEm,
        private WireWebpageServiceInterface $entityService
    )
    {}

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
                'by_reference' => true,
                'class' => $this->wireEm->findOneFinal(WireWebsectionInterface::class)->name,
                'choices' => $this->entityService->getWebsectionsChoices(),
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
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
            'attr' => ['data-submit-actions' => 'save_index'],
            'priority' => -1
        ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WireWebpage::class,
            'translation_domain' => $this->entityService->getEntityShortname(),
        ]);
    }
}
