<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Entity\interface\WireItemTranslationInterface;
use Aequation\WireBundle\Entity\trait\WireEntity;
use Aequation\WireBundle\Entity\trait\WireTranslation;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation;

#[ORM\Entity]
#[ORM\Table(name: 'translations_item')]
#[ORM\UniqueConstraint(name: 'lookup_unique_idx', columns: ['locale', 'object_id', 'field'])]
class WireItemTranslation extends AbstractPersonalTranslation implements WireItemTranslationInterface
{
    use WireEntity, WireTranslation;

    public const ICON = [
        'ux' => 'tabler:flag',
        'fa' => 'fa-flag'
        // Add other types and their corresponding icons here
    ];
    public const SERIALIZATION_PROPS = ['id','locale','field','content'];
    // public const DO_EMBED_STATUS_EVENTS = [];

    #[ORM\ManyToOne(targetEntity: WireItemInterface::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'object_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $object;

    /**
     * Convenient constructor
     *
     * @param string $locale
     * @param string $field
     * @param string $value
     */
    public function __construct($locale, $field, $value)
    {
        $this->__construct_entity();
        $this->setLocale($locale);
        $this->setField($field);
        $this->setContent($value);
    }

}