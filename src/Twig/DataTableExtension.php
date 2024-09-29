<?php
namespace Aequation\WireBundle\Twig;

use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Extension\AbstractExtension;

class DataTableExtension extends AbstractExtension
{

    public function __construct(
        private StimulusHelper $stimulus
    )
    {
        
    }

}