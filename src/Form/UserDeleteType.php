<?php
namespace Aequation\WireBundle\Form;

// Symfony
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserDeleteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre mot de passe',
                    ]),
                    new UserPassword([
                        'message' => 'Mot de passe incorrect',
                    ]),
                ],
            ])
            ->add('user_id', HiddenType::class, [
                'data' => $options['user_id'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Supprimer mon compte',
                'attr' => [
                    'class' => 'submit-danger',
                ],
                'priority' => -1
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            /** @see https://symfony.com/doc/current/security/csrf.html */
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id'   => 'delete_profile_token',
        ]);
        $resolver->setRequired('user_id');
        $resolver->setRequired('csrf_token_id');
    }
}