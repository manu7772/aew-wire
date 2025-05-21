<?php
namespace Aequation\WireBundle\Service\interface;


interface SurveyRecursionInterface
{
    public const MAX_SURVEY_RECURSION = 300;

    public function __construct(AppWireServiceInterface $appWire);

    /**
     * Is active
     * 
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Survey recursion in some methods (DEV only)
     * use: $this->survey(__METHOD__.'::*somename*');
     * 
     * @param string $name
     * @param int|null $max
     * @return void
     */
    public function survey(
        string $name,
        ?int $max = null,
        ?string $errorMessage = null
    ): void;
}
