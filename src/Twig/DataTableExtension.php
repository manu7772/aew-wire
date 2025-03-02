<?php
namespace Aequation\WireBundle\Twig;

use Aequation\WireBundle\Model\DataTable;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DataTableExtension extends AbstractExtension
{

    public function __construct(
        #[Autowire(service: 'stimulus.helper')]
        private StimulusHelper $stimulus
    )
    {}

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
        // $attributes['data-turbo-temporary'] = "false";
        $table->setAttributes(array_merge($table->getAttributes(), $attributes));

        $controllers = [];
        if ($table->getDataController()) {
            $controllers[$table->getDataController()] = [];
        }
        $controllers['@aequation/wire/datatable'] = ['view' => $table->getOptions()];

        $stimulusAttributes = $this->stimulus->createStimulusAttributes();
        foreach ($controllers as $name => $controllerValues) {
            $stimulusAttributes->addController($name, $controllerValues);
        }

        foreach ($table->getAttributes() as $name => $value) {
            if(!in_array($name, ['data-controller'])) {
                if(true === $value) {
                    $stimulusAttributes->addAttribute($name, $name);
                } else if (false !== $value) {
                    $stimulusAttributes->addAttribute($name, $value);
                }
            }
        }

        return vsprintf('<table%s %s></table>', [empty($table->getId()) ? '' : ' id="'.$table->getId().'"', $stimulusAttributes]);
    }

}