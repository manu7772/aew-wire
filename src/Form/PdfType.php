<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WirePdf;
use Aequation\WireBundle\Service\interface\WirePdfServiceInterface;
// Symfony
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PdfType extends AbstractType
{

    public function __construct(
        private TranslatorInterface $translator,
        private WirePdfServiceInterface $entityService
    )
    {
        
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WirePdf */
        // $pdf = $builder->getData();
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
            'data_class' => WirePdf::class,
            'translation_domain' => $this->entityService->getEntityShortname(),
        ]);
    }
}
