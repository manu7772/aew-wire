<?php
namespace Aequation\WireBundle\Twig\Components;

use Aequation\WireBundle\Form\EntityClassMetadataType;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent()]
final class EntitiesClassMetadata extends AbstractController
{

    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[ExposeInTemplate(name: 'list', getter: 'getList')]
    public Collection $list;
    #[ExposeInTemplate(name: 'listAll', getter: 'getListAll')]
    public Collection $listAll;
    #[ExposeInTemplate(name: 'command', getter: 'getCommand')]
    public string $command;

    public function __construct(
        public WireEntityManagerInterface $wireEm
    ) {}

    public function instantiateForm(): FormInterface
    {
        return $this->createForm(EntityClassMetadataType::class);
    }

    public function getList(): Collection
    {
        $wCmdm = $this->wireEm->getEntitiesMetadata()->resetFilters();
        $wCmdm->setTypeCompare((bool) $this->formValues['type_comparison']);
        if($this->formValues['mode'] !== 'all') {
            $wCmdm->setSearchMode($this->formValues['mode']);
        }
        return $wCmdm
            ->filterClasses(
                array_merge(
                    $this->formValues['interfaces'] ?? [],
                    $this->formValues['classes'] ?? []
                )
            )->sortBy('shortname', true);
    }

    public function getListAll(): Collection
    {
        return $this->listAll = $this->wireEm->getEntitiesMetadata()->resetFilters()->getAll();
    }

    public function getCommand(): string
    {
        return $this->command = $this->formValues['mode'] === 'all' && (!$this->formValues['interfaces'] && !$this->formValues['classes'])
            ? 'ClassMetadataManager->getAll()'
            : vsprintf('ClassMetadataManager%s%s%s', [
                $this->formValues['type_comparison'] ? '' : '->setTypeCompare(false)',
                $this->formValues['mode'] === 'all' ? '' : vsprintf('->setSearchMode("%s")', [$this->formValues['mode']]),
                $this->formValues['interfaces'] || $this->formValues['classes'] ? vsprintf('->filterClasses(%s)', [
                    json_encode(array_merge(
                        $this->formValues['interfaces'] ?? [],
                        $this->formValues['classes'] ?? []
                    ))
                ]) : '->filterClasses([])'
            ]);
    }

}