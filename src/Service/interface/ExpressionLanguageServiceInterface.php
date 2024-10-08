<?php
namespace Aequation\WireBundle\Service\interface;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

interface ExpressionLanguageServiceInterface extends WireServiceInterface
{

    public function addPhpFunctions(): static;
    // Herited
    public function compile(Expression|string $expression, array $names = []): string;
    public function evaluate(Expression|string $expression, array $values = []): mixed;
    public function parse(Expression|string $expression, array $names, int $flags = 0): ParsedExpression;
    public function lint(Expression|string $expression, ?array $names, int $flags = 0): void;
    public function register(string $name, callable $compiler, callable $evaluator): void;
    public function addFunction(ExpressionFunction $function): void;
    public function registerProvider(ExpressionFunctionProviderInterface $provider): void;

}