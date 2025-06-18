<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// PHP
use RuntimeException;

abstract class EntityController extends AbstractController
{

    public const ENTITY_CLASS = null;

    public readonly WireEntityServiceInterface $service;


    public function __construct(
        protected WireEntityManagerInterface $wireEm,
        protected EntityManagerInterface $entityManager,
        protected TranslatorInterface $translator
    )
    {
        $this->service = $this->wireEm->getEntityService(static::ENTITY_CLASS ?? '');
        if (!is_a($this->service->getEntityClassname(), static::ENTITY_CLASS ?? '', true)) {
            throw new RuntimeException(vsprintf('Error %s line %d: Service found does not manage an instance of "%s".', [__METHOD__, __LINE__, static::ENTITY_CLASS]));
        }
    }

    protected function getTemplatePath(
        string $action
    ): string
    {
        $templates = [
            '@AequationWire/admin/entity/'.$this->getEntityShortname(true).'/'.$action.'.html.twig',
            '@AequationWire/admin/entity/'.$action.'.html.twig',
        ];
        foreach ($templates as $template) {
            if($this->container->get('twig')->getLoader()->exists($template)) {
                return $template;
            }
        }
        throw new RuntimeException(vsprintf('Error %s line %d: No template found for action "%s".', [__METHOD__, __LINE__, $action]));
    }

    protected function getEntityClassname(): string
    {
        return $this->service->getEntityClassname();
    }

    protected function getEntityShortname(
        bool $lowercase = false
    ): string
    {
        $shortname = $this->service->getEntityShortname();
        return $lowercase ? strtolower($shortname) : $shortname;
    }

}