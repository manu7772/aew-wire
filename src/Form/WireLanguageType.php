<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WireLanguage;
use Aequation\WireBundle\Service\interface\WireLanguageServiceInterface;
// Symfony
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WireLanguageType extends AbstractType
{

    public function __construct(
        // private TranslatorInterface $translator,
        // private WireEntityManagerInterface $wireEm,
        private WireLanguageServiceInterface $entityService
    )
    {
        
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WireLanguage */
        // $language = $builder->getData();
        $builder
            ->add('name', null, [
                'label' => 'fields.name',
                'required' => true,
                'priority' => 30
            ])
            ->add('locale', null, [
                'label' => 'fields.locale',
                'required' => true,
                'priority' => 25
            ])
            ->add('description', TextareaType::class, [
                'label' => 'fields.description',
                'required' => false,
                'priority' => 15
            ])
            ->add('timezone', ChoiceType::class, [
                'label' => 'fields.timezone',
                'required' => true,
                'choices' => $this->entityService->getTimezoneChoices(),
                'priority' => 5
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'fields.enabled',
                'required' => false,
                'priority' => 4
            ])
            ->add('prefered', CheckboxType::class, [
                'label' => 'fields.prefered',
                'required' => false,
                'priority' => 3
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
            'data_class' => WireLanguage::class,
            'translation_domain' => $this->entityService->getEntityShortname(),
        ]);
    }
}
