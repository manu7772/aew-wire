<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WireArticle;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireArticleServiceInterface;
// Symfony
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class WireArticleType extends WireAbstractType
{

    public const ENTITY_CLASS = WireArticle::class;

    /** @var WireArticleServiceInterface */
    protected readonly WireEntityServiceInterface $entityService;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WireArticle */
        // $article = $builder->getData();
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
                'priority' => -2
            ])
        ;

        $this->defaultListeners($builder);

    }

}
