<?php
namespace Aequation\WireBundle\Twig;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Tools\Objects;
use Aequation\WireBundle\Tools\Strings;
// Symfony
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\Markup;
// PHP
use Exception;
use Stringable;
use DateTimeInterface;

class WireExtension extends AbstractExtension
{

    public function __construct(
        private AppWireServiceInterface $appWire,
        // private WireUserServiceInterface $userService,
        private TranslatorInterface $translator,
        private Environment $twig
    )
    {}

    public function getFunctions(): array
    {
        $functions = [
            new TwigFunction('current_year', [$this->appWire, 'getCurrentYear']),
            new TwigFunction('user_granted', [$this->appWire, 'isUserGranted']),
            new TwigFunction('list_roles', [$this, 'listRoles'], ['is_safe' => ['html']]),
            new TwigFunction('field_value', [$this, 'fieldValue'], ['is_safe' => ['html']]),
            new TwigFunction('print_attributes', [$this, 'printAttributes'], ['is_safe' => ['html']]),
            new TwigFunction('action_path', [$this->appWire, 'getActionPath']),
            new TwigFunction('action_url', [$this->appWire, 'getActionUrl']),
            // new TwigFunction('printr', [Objects::class, 'toDebugString'], ['is_safe' => ['html']]),
            new TwigFunction('toDump', [$this, 'toDump'], ['is_safe' => ['html']]),
            // TURBO-UX
            new TwigFunction('turbo_memory', [$this, 'turboMemory']),
            new TwigFunction('turbo_preload', [$this, 'turboPreload']),
        ];
        if(!$this->appWire->isDev()) {
            // Prevent dump function call if not in dev evnironment
            $functions[] = new TwigFunction('dump', [$this, 'dump']);
        }
        return $functions;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('filter_active', [$this, 'filterActive']),
            new TwigFilter('shortname', [Objects::class, 'getShortname']),
            new TwigFilter('classname', [Objects::class, 'getClassname']),
            new TwigFilter('tailwind_merge', [$this, 'tailwindMerge']),
        ];
    }

    // /**
    //  * Get Twig globals
    //  * @return array
    //  */
    // public function getGlobals(): array
    // {
    //     return [
    //         'app' => $this->appWire,
    //         'currentYear' => $this->getCurrentYear(),
    //     ];
    // }


    /*************************************************************************************
     * FILTERS
     *************************************************************************************/

    public function filterActive(
        null|ArrayCollection|array $items,
        bool $keepEmptyCollections = false
    ): ArrayCollection|array
    {
        if(empty($items)) {
            // If items is empty, return empty array
            return [];
        }
        $type = gettype($items);
        if($type === 'array') {
            $items = new ArrayCollection($items);
        }
        $items = $items->filter(fn(WireItemInterface $item) => $item instanceof WireEcollectionInterface ? $item->isActive() && ($keepEmptyCollections || !$item->getItems()->isEmpty()) : $item->isActive());
        // Filtered items
        return $type === 'array'
            ? $items->toArray()
            : $items;
    }

    public function tailwindMerge(string $classes1, string $classes2 = ''): string
    {
        $classes1 = preg_split('/\s+/', $classes1);
        $classes2 = preg_split('/\s+/', $classes2);
        return implode(' ', array_unique(array_merge($classes1, $classes2)));
    }

    /*************************************************************************************
     * FUNCTIONS
     *************************************************************************************/

     public function listRoles(
        UserInterface|array $roles,
        bool $asString = true
    ): string|array
    {
        if($roles instanceof UserInterface) {
            $roles = $roles->getRoles();
        }
        $roles = array_map(function($role) {
            return $this->translator->trans($role);
        }, $roles);
        return $asString ? implode(', ', $roles) : $roles;
    }

    public function fieldValue(
        object $object,
        string $property,
        ?string $trans_domain = null,
        array $options = []
    ): string|Markup
    {
        if($options['template'] ?? false) {
            if(isset($options['template']['from_string'])) {
                // Render template from string
                $template = $this->twig->createTemplate($options['template']['from_string'], 'field_value_'.Objects::getShortname($object, true).'_'.strtolower($property));
                return $template->render([
                    'entity' => $object,
                    'trans_domain' => $trans_domain,
                    'options' => $options,
                ]);
            } else if(isset($options['template']['path'])) {
                // Render template from file
                return $this->twig->render($options['template']['path'], [
                    'entity' => $object,
                    'trans_domain' => $trans_domain,
                    'options' => $options,
                ]);
            }
        }
        $accessor = PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->getPropertyAccessor();
        if(!$accessor->isReadable($object, $property)) {
            throw new Exception(vsprintf('Error %s line %d: property "%s" is not readable on %s%s', [__METHOD__, __LINE__, $property, $object::class, $object instanceof Stringable ? ' named '.$object->__toString() : '']));
        }
        $value = $accessor->getValue($object, $property);
        $type = gettype($value);
        switch (true) {
            case $type === 'NULL':
                if(isset($options['null_value']['from_string'])) {
                    // Render template from string
                    $template = $this->twig->createTemplate($options['null_value']['from_string'], 'field_value_null_'.Objects::getShortname($object, true).'_'.strtolower($property));
                    $value = $template->render([
                        'entity' => $object,
                        'trans_domain' => $trans_domain,
                        'options' => $options,
                    ]);
                } else if(isset($options['null_value']['path'])) {
                    // Render template from file
                    $value = $this->twig->render($options['null_value']['path'], [
                        'entity' => $object,
                        'trans_domain' => $trans_domain,
                        'options' => $options,
                    ]);
                } else {
                    $value = $this->translator->trans($options['null_value'] ?? '', [], $trans_domain);
                }
                break;
            case $type === 'boolean':
                $value = $value ? 'true' : 'false';
                break;
            case $type === 'object' && $value instanceof BaseEntityInterface:
                $value = is_string($options['related_access'] ?? false) ? $accessor->getValue($value, $options['related_access']) : $value->__toString();
                break;
            case $type === 'object' && $value instanceof DateTimeInterface:
                $value = $value->format($options['format'] ?? 'd/m/Y H:i:s');
                break;
            case $type === 'array' || $value instanceof Collection:
                if($value instanceof Collection) {
                    $value = $value->toArray();
                }
                $value = array_map(function($v) use ($trans_domain) {
                    return $trans_domain ? $this->translator->trans((string)$v, [], $trans_domain) : (string)$v;
                }, $value);
                $value = implode($options['list_separator'] ?? ', ', $value);
                break;
        }
        return (string)$value;
    }

    public function printAttributes(
        array $attributes
    ): Markup
    {
        $markup = '';
        foreach ($attributes as $key => $value) {
            if(is_string($value) || is_numeric($value) || is_bool($value)) {
                // Only string, numeric or boolean values are allowed
                $markup .= ' '.$key.'="'.htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8').'"';
            } else if(is_array($value)) {
                // If value is an array, convert it to a string
                $markup .= ' '.$key.'="'.htmlspecialchars(implode(' ', $value), ENT_QUOTES, 'UTF-8').'"';
            }
        }
        dump(trim($markup));
        return Strings::markup(trim($markup));
    }

    public function toDump(
        mixed $something,
        int $depth = 3,
        array $groups = [],
    ): ?Markup
    {
        return Objects::toDump($something, true, $depth, $this->appWire->get(NormalizerServiceInterface::class)->getSerializer(), $groups);
    }

    /**
     * Enable/Disable data-turbo-temporary attribute
     * @param boolean $enable
     * @return Markup
     */
    public function turboMemory(bool $enable) : Markup
    {
        return Strings::markup(' data-turbo-temporary="'.($enable ? 'true' : 'false').'"');
    }

    /**
     * Enable/Disable data-turbo attribute
     * @param boolean $enable
     * @return Markup
     */
    public function turboPreload(bool $enable) : Markup
    {
        return Strings::markup(' data-turbo="'.($enable ? 'true' : 'false').'"');
    }

    /**
     * Remove dump() function to prevent error when production environment
     * @param mixed $value
     * @return null
     */
    public function dump(mixed $value): null
    {
        throw new Exception(vsprintf('Error %s line %d: function %s() is not available in production environment', [__METHOD__, __LINE__, 'dump']));
        return null;
    }

}