<?php
namespace Aequation\WireBundle\Twig;

use Aequation\WireBundle\Model\DataTable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
// Symfony
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DataTableExtension extends AbstractExtension
{

    public function __construct(
        #[Autowire(service: 'stimulus.helper')]
        private StimulusHelper $stimulus
    )
    {
        
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_datatable', [$this, 'renderDataTable'], ['is_safe' => ['html']]),
        ];
    }


    public function renderDataTable(
        DataTable $table,
        array $attributes = []
    ): string
    {
        $table->setAttributes(array_merge($table->getAttributes(), $attributes));

        $controllers = [];
        if ($table->getDataController()) {
            $controllers[$table->getDataController()] = [];
        }
        $controllers['@aequation/wire/datatables'] = ['view' => $table->getOptions()];

        $stimulusAttributes = $this->stimulus->createStimulusAttributes();
        foreach ($controllers as $name => $controllerValues) {
            $stimulusAttributes->addController($name, $controllerValues);
        }

        foreach ($table->getAttributes() as $name => $value) {
            if ('data-controller' === $name) {
                continue;
            }

            if (true === $value) {
                $stimulusAttributes->addAttribute($name, $name);
            } elseif (false !== $value) {
                $stimulusAttributes->addAttribute($name, $value);
            }
        }

        return vsprintf('<table id="%s" %s></table>', [$table->getId(), $stimulusAttributes]);
    }

}