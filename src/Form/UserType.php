<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
// Symfony
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserType extends AbstractType
{

    public function __construct(
        private TranslatorInterface $translator,
        private WireUserServiceInterface $entityService
    )
    {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WireUser */
        $user = $builder->getData();
        $builder
            ->add('email', EmailType::class, [
                'label' => 'fields.email',
                'required' => true,
                'priority' => 10
            ])
            ->add('name', null, [
                'label' => 'fields.name',
                'required' => true,
                'priority' => 9
            ])
            ->add('firstname', null, [
                'label' => 'fields.firstname',
                'required' => false,
                'priority' => 8
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'fields.password',
                'required' => !$user || null === $user->getId(),
                'priority' => 6
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'actions.save',
                'attr' => ['data-submit-actions' => 'save_index'],
                'priority' => -1
            ])
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
                    'expanded' => false,
                    'priority' => 7
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WireUser::class,
            'translation_domain' => $this->entityService->getEntityShortname(),
        ]);
    }
}
