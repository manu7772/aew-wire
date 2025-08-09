<?php
namespace Aequation\WireBundle\Twig\Components;

use Aequation\WireBundle\Form\EntityClassMetadataType;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Objects;
use Aequation\WireBundle\Tools\Strings;
// Symfony
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Twig\Markup;

#[AsLiveComponent(
    name: 'wire:entities-classmetadata',
    template: '@AequationWire/components/entities-classmetadata.html.twig'
)]
final class EntitiesClassmetadata extends AbstractController
{

    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[ExposeInTemplate(name: 'list', getter: 'getList')]
    public Collection $list;
    // #[ExposeInTemplate(name: 'listAll', getter: 'getListAll')]
    // public Collection $listAll;
    #[ExposeInTemplate(name: 'command', getter: 'getCommand')]
    public Markup $command;

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
        return $this->wireEm->getEntitiesMetadata()->resetFilters()->getAll();
    }

    public function getCommand(): Markup
    {
        $command = $this->formValues['mode'] === 'all' && (!$this->formValues['interfaces'] && !$this->formValues['classes'])
            ? '<span class="text-info">ClassMetadataManager</span>-><span class="text-success">getAll</span>()'
            : vsprintf('<span class="text-info">ClassMetadataManager</span>%s%s%s', [
                $this->formValues['type_comparison'] ? '' : '-><span class="text-success">setTypeCompare</span>(false)',
                $this->formValues['mode'] === 'all' ? '' : vsprintf('-><span class="text-success">setSearchMode</span>("%s")', [$this->formValues['mode']]),
                $this->formValues['interfaces'] || $this->formValues['classes'] ? vsprintf('-><span class="text-success">filterClasses</span>(%s)', [
                    implode(', ', array_merge(
                        static::shortnames($this->formValues['interfaces']) ?? [],
                        static::shortnames($this->formValues['classes']) ?? []
                    ))
                ]) : '-><span class="text-success">filterClasses</span>([])'
            ]);
        return $this->command = Strings::markup($command);
    }

    protected static function shortnames(?array $classes): ?array
    {
        if(!$classes) return null;
        return array_map(
            static fn(string $class): string => Objects::getShortname($class).'::class',
            $classes
        );
    }

}