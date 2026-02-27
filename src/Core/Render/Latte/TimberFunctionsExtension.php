<?php

declare (strict_types=1);
/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */
namespace Picowind\Core\Render\Latte;

use PicowindDeps\Latte\Extension;
class TimberFunctionsExtension extends Extension
{
    /**
     * @var array<string, callable>
     */
    private array $functions;
    /**
     * @param array<string, callable> $functions
     */
    public function __construct(array $functions)
    {
        $this->functions = $functions;
    }
    public function getFunctions(): array
    {
        return $this->functions;
    }
}
