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
        private WireUserServiceInterface $userService
    )
    {
        
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User */
        $user = $builder->getData();
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'required' => true,
                'priority' => 10
            ])
            ->add('name', null, [
                'label' => 'Nom',
                'required' => true,
                'priority' => 9
            ])
            ->add('firstname', null, [
                'label' => 'Prénom',
                'required' => false,
                'priority' => 8
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => !$user || null === $user->getId(),
                'priority' => 6
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'priority' => -1
            ])
        ;

        $current_user = $this->userService->getUser();

        if($user && $this->userService->isUserGranted($current_user, 'ROLE_ADMIN')) {
            $choices = [];
            foreach ($this->userService->getAvailableRoles($current_user) as $role) {
                $choices[$this->translator->trans($role)] = $role;
            }
            // if(array_intersect($user->getRoles(), ['ROLE_SUPER_ADMIN'])) {
            //     $choices[$this->translator->trans('ROLE_SUPER_ADMIN')] = 'ROLE_SUPER_ADMIN';
            // }
            $builder
                ->add('roles', ChoiceType::class, [
                    'label' => 'Autorisations',
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
        ]);
    }
}
