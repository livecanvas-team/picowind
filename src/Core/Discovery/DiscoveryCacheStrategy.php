<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery;

enum DiscoveryCacheStrategy: string
{
    case FULL = 'full';
    case PARTIAL = 'partial';
    case DISABLED = 'disabled';
}