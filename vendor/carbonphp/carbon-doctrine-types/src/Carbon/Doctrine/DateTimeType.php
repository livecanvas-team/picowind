<?php

declare (strict_types=1);
namespace PicowindDeps\Carbon\Doctrine;

use PicowindDeps\Carbon\Carbon;
use DateTime;
use PicowindDeps\Doctrine\DBAL\Platforms\AbstractPlatform;
use PicowindDeps\Doctrine\DBAL\Types\VarDateTimeType;
class DateTimeType extends VarDateTimeType implements CarbonDoctrineType
{
    /** @use CarbonTypeConverter<Carbon> */
    use CarbonTypeConverter;
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Carbon
    {
        return $this->doConvertToPHPValue($value);
    }
}
