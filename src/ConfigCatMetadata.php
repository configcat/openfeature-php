<?php

declare(strict_types=1);

namespace ConfigCat\OpenFeature;

use OpenFeature\interfaces\common\Metadata;

class ConfigCatMetadata implements Metadata
{
    public function getName(): string
    {
        return 'ConfigCatProvider';
    }
}
