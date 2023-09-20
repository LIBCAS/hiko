<?php

namespace App\MediaLibrary;

use Spatie\MediaLibrary\UrlGenerator\LocalUrlGenerator;
use Illuminate\Support\Facades\Log;

class TenancyUrlGenerator extends LocalUrlGenerator
{
    public function getUrl(): string
    {
        $defaultUrl = $this->getPathRelativeToRoot();

        $tenantPrefix = tenant('table_prefix');
        $modifiedUrl = str_replace('/storage/', "/storage/{$tenantPrefix}/", $defaultUrl);
        Log::info('QQQ = ' . $modifiedUrl);
        return $modifiedUrl;
    }
}
