<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WireLanguage;
use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Service\interface\WireLanguageServiceInterface;
// Symfony
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class WireUserType extends AbstractType
{

    public const ENTITY_CLASS = WireUser::class;

    public readonly string $classname;

    public function __construct(
        private TranslatorInterface $translator,
        private WireUserServiceInterface $entityService
    )
    {}

    public function getFinalClassname(): string
    {
        return $this->classname ??= $this->entityService->getWireEm()->getEntitiesMetadata()->findOneFinal([static::ENTITY_CLASS])->getName();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WireUser */
        $user = $builder->getData();
        $wireEm = $this->entityService->getWireEm();
        $languageClass = $wireEm->getEntitiesMetadata()->findOneFinal([WireLanguage::class])->getName();
        /** @var WireLanguageServiceInterface */
        $languageService = $wireEm->getEntityService($languageClass);
        $builder
            ->add('email', EmailType::class, [
                'label' => 'fields.email',
                'required' => true,
                'priority' => 100,
                'help' => 'fields.help.email',
                'constraints' => [
                    new NotNull(
                        message: 'L\'email est obligatoire.',
                        groups: ['persist','update'],
                    ),
                    new Email(
                        message: $this->translator->trans('errors.invalid_email'),
                        groups: ['persist','update'],
                    ),
                ],
            ])
            ->add('name', null, [
                'label' => 'fields.name',
                'required' => true,
                'priority' => 90,
                'constraints' => [
                    new NotNull(
                        message: 'Le nom est obligatoire.',
                        groups: ['persist','update'],
                    ),
                    new Length(
                        min: 2,
                        max: 64,
                        minMessage: $this->translator->trans('errors.name_too_short'),
                        maxMessage: $this->translator->trans('errors.name_too_long'),
                        groups: ['persist','update'],
                    ),
                ],
            ])
            ->add('firstname', null, [
                'label' => 'fields.firstname',
                'required' => false,
                'priority' => 80,
                'constraints' => [
                    new Length(
                        min: 1,
                        max: 64,
                        minMessage: $this->translator->trans('errors.name_too_short'),
                        maxMessage: $this->translator->trans('errors.name_too_long'),
                        groups: ['persist','update'],
                    ),
                ],
            ])
            ->add('phones', LiveCollectionType::class, [
                'label' => 'fields.phones',
                'entry_type' => WirePhonelinkType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'priority' => 70,
            ])
            ->add('language', EntityType::class, [
                'label' => 'fields.language',
                'class' => $languageService->getEntityClassname(),
                'choices' => $languageService->getRepository()->findBy(['enabled' => true], ['name' => 'ASC']),
                'choice_label' => 'name',
                'required' => true,
                'priority' => 60,
            ])
            ->add('timezone', ChoiceType::class, [
                'label' => 'fields.timezone',
                'choices' => $languageService->getTimezoneChoices(),
                'placeholder' => 'fields.select_timezone',
                'required' => true,
                'priority' => 50,
                'constraints' => [
                    new NotNull(
                        message: 'Vous devez choisir un fuseau horaire.',
                        groups: ['persist','update'],
                    ),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'fields.plainPassword',
                'required' => $user->getSelfState()->isNew(),
                'priority' => 40,
                'always_empty' => false,
                'help' => 'fields.help.plainPassword',
                'constraints' => [
                    new Length(
                        min: 8,
                        max: 64,
                        minMessage: $this->translator->trans('errors.name_too_short'),
                        maxMessage: $this->translator->trans('errors.name_too_long'),
                        groups: ['persist','update'],
                    ),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'actions.save',
                // 'attr' => [
                //     // 'data-action' => 'live#action:prevent',
                //     // 'data-live-action-param' => 'registerType',
                //     'data-live-submit-param' => 'submit',
                // ],
                'priority' => -2
            ])
            // ->add('submit_continue', SubmitType::class, [
            //     'label' => 'actions.save_continue',
            //     // 'attr' => [
            //     //     // 'data-action' => 'live#action:prevent',
            //     //     // 'data-live-action-param' => 'registerType',
            //     //     'data-live-submit-param' => 'submit_continue',
            //     // ],
            //     'priority' => -1
            // ])
        ;

        $current_user = $this->entityService->getUser();

        if($user && $this->entityService->isGrantedForUser($current_user, 'ROLE_ADMIN')) {
            $choices = [];
            foreach ($this->entityService->getAvailableRoles($current_user) as $role) {
                $choices[$this->translator->trans($role)] = $role;
            }
            // if(array_intersect($user->getRoles(), ['ROLE_SUPER_ADMIN'])) {
            //     $choices[$this->translator->trans('ROLE_SUPER_ADMIN')] = 'ROLE_SUPER_ADMIN';
            // }
            $builder
                ->add('roles', ChoiceType::class, [
                    'label' => 'fields.roles',
                    'choices' => $choices,
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'priority' => 30
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->getFinalClassname(),
            'translation_domain' => $this->entityService->getEntityShortname(),
            'attr' => [
                // 'novalidate' => true,
                'data-action' => 'live#action:prevent',
                'data-live-action-param' => 'registerType',
            ]
        ]);
    }
}
