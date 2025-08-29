<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\WireCategory;
use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireCategoryServiceInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
// Symfony
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class WireCategoryType extends AbstractType
{

    public function __construct(
        private TranslatorInterface $translator,
        private WireEntityManagerInterface $wireEm,
        private WireCategoryServiceInterface $entityService
    )
    {
        
    }

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
            ->add('description', null, [
                'label' => 'fields.description',
                'required' => false,
                'priority' => 5
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'actions.save',
                'attr' => [
                    'class' => 'btn btn-accent btn-block btn-lg mt-4',
                ],
                'priority' => -1
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WireCategory::class,
            'translation_domain' => $this->entityService->getEntityShortname(),
            'attr' => [
                // 'novalidate' => true,
                'data-action' => 'live#action:prevent',
                'data-live-action-param' => 'registerType',
            ],
        ]);
    }
}
