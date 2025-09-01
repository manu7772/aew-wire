<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WireFactory;
use Aequation\WireBundle\Entity\WireLanguage;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireFactoryServiceInterface;
// Symfony
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;

class WireFactoryType extends WireAbstractType
{

    public const ENTITY_CLASS = WireFactory::class;

    /** @var WireFactoryServiceInterface */
    protected readonly WireEntityServiceInterface $entityService;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WireFactory */
        // $factory = $builder->getData();
        $languageClass = $this->wireEm->getEntitiesMetadata()->findOneFinal([WireLanguage::class])->getName();
        /** @var WireLanguageServiceInterface */
        $languageService = $this->wireEm->getEntityService($languageClass);
        $builder
            ->add('name', TextType::class, [
                'label' => 'fields.name',
                'required' => true,
                'priority' => 140,
            ])
            ->add('title', TextType::class, [
                'label' => 'fields.title',
                'required' => true,
                'help' => 'help.title',
                'priority' => 140,
            ])
            ->add('linktitle', TextType::class, [
                'label' => 'fields.linktitle',
                'required' => true,
                'help' => 'help.linktitle',
                'priority' => 140,
            ])
            ->add('functionality', TextareaType::class, [
                'label' => 'fields.functionality',
                'required' => false,
                'priority' => 120,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'fields.description',
                'required' => false,
                'priority' => 100,
            ])
            ->add('associates', EntityType::class, [
                'label' => 'fields.associates',
                'by_reference' => true,
                'class' => $this->wireEm->findOneFinal(WireUserInterface::class)->name,
                'choice_label' => 'email',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'help' => 'help.associates',
                'priority' => 80,
            ])
            ->add('mainimage', WireImageType::class, [
                'label' => 'compound_name.mainimage',
                'required' => false,
                // 'class' => $this->wireEm->getEntitiesMetadata()->findOneFinal([WireImageInterface::class])->getName(),
                'priority' => 70,
            ])
            ->add('phones', LiveCollectionType::class, [
                'label' => 'fields.phones',
                'required' => false,
                'entry_type' => WirePhonelinkType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => true,
                // 'translation_domain' => 'Phonelink',
                'priority' => 60,
            ])
            ->add('language', EntityType::class, [
                'label' => 'fields.language',
                'class' => $languageService->getEntityClassname(),
                'choices' => $languageService->getRepository()->findBy(['enabled' => true], ['name' => 'ASC']),
                'choice_label' => 'name',
                'required' => true,
                'priority' => 40,
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => 'fields.timezone',
                // 'choices' => $languageService->getTimezoneChoices(),
                'placeholder' => 'fields.select_timezone',
                'required' => true,
                'priority' => 20,
                'constraints' => [
                    new NotNull(
                        message: 'Vous devez choisir un fuseau horaire.',
                        groups: ['persist','update'],
                    ),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'actions.save',
                'priority' => -2
            ])
        ;

        $this->defaultListeners($builder);

    }

}
