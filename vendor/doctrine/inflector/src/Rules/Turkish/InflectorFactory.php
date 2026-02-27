<?php

declare (strict_types=1);
namespace PicowindDeps\Doctrine\Inflector\Rules\Turkish;

use PicowindDeps\Doctrine\Inflector\GenericLanguageInflectorFactory;
use PicowindDeps\Doctrine\Inflector\Rules\Ruleset;
final class InflectorFactory extends GenericLanguageInflectorFactory
{
    protected function getSingularRuleset(): Ruleset
    {
        return Rules::getSingularRuleset();
    }
    protected function getPluralRuleset(): Ruleset
    {
        return Rules::getPluralRuleset();
    }
}
