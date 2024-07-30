<?php

namespace ConfigCat\OpenFeature;

use OpenFeature\interfaces\common\Metadata;

class ConfigCatMetadata implements Metadata
{
    public function getName(): string
    {
        return 'ConfigCatProvider';
    }
}
