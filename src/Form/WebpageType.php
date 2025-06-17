<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WireWebpage;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireWebpageServiceInterface;
// Symfony
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class WebpageType extends AbstractType
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
            'priority' => 9
        ]);
        // $wsClass = $this->wireEm->resolveFinalEntity(WireWebsectionInterface::class);
        // if($wsClass) {
            /** @see https://symfony.com/doc/current/reference/forms/types/choice.html */
            $builder->add('websections', ChoiceType::class, [
                'mapped' => false,
                'by_reference' => false,
                // 'class' => reset($wsClass),
                'choices' => $this->entityService->getWebsectionsChoices(),
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'label' => 'fields.websections',
                'required' => true,
                'priority' => 8
            ]);
        // }
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
