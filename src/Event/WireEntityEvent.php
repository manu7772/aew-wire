<?php
namespace Aequation\WireBundle\Event;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Contracts\EventDispatcher\Event;
// use Symfony\Component\Form\FormEvents;

class WireEntityEvent extends Event
{

    public const ON_LOAD = 'on.load';
    public const POST_CREATE = 'post.create';
    public const POST_MODEL = 'post.model';
    public const POST_CLONE = 'post.clone';

    public const BEFORE_PERSIST = 'before.persist';
    public const BEFORE_UPDATE = 'before.update';
    public const BEFORE_REMOVE = 'before.remove';

    public const FORM_PRE_SET_DATA = 'form.pre_set_data';
    public const FORM_POST_SET_DATA = 'form.post_set_data';
    public const FORM_PRE_SUBMIT = 'form.pre_submit';
    public const FORM_POST_SUBMIT = 'form.post_submit';

    public function __construct(
        public readonly WireEntityInterface $entity,
        public readonly WireEntityManagerInterface $wireEntityManager
    ) {}

    public function getEntity(): WireEntityInterface
    {
        return $this->entity;
    }

    public function getObject(): WireEntityInterface
    {
        return $this->entity;
    }

    public function getEntityService(): WireEntityManagerInterface|WireEntityServiceInterface
    {
        return $this->wireEntityManager->getEntityService($this->entity);
    }

}