<?php

namespace App\MediaLibrary;

use Spatie\MediaLibrary\UrlGenerator\LocalUrlGenerator;

class TenancyUrlGenerator extends LocalUrlGenerator
{
    public function getUrl(): string
    {
        $defaultUrl = $this->getPathRelativeToRoot();
        $tenantPrefix = tenant('table_prefix');
        $modifiedUrl = "/storage/$tenantPrefix/$defaultUrl";
        return $modifiedUrl;
    }
}
