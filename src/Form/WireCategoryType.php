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
            ->add('description', null, [
                'label' => 'fields.description',
                'required' => false,
                'priority' => 5
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'actions.save',
                'attr' => ['data-submit-actions' => 'save_index'],
                'priority' => -1
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WireCategory::class,
            'translation_domain' => $this->entityService->getEntityShortname(),
        ]);
    }
}
