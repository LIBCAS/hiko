<?php

namespace App\Http\Controllers\Api;

use App\Models\Letter;
use Spatie\ArrayToXml\ArrayToXml;
use App\Http\Controllers\Controller;

class ModsExportController extends Controller
{
    public function __invoke()
    {
        $result = '<?xml version="1.0" encoding="UTF-8"?><mods_records>';

        Letter::where('status', '=', 'publish')->get()
            ->each(function ($letter) use (&$result) {
                $result .= $this->createRecord($letter);
            });

        $result .= '</mods_records>';

        return response($result, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    protected function createRecord(Letter $letter)
    {
        $record = [
            'titleInfo' => [
                'title' => [
                    '_value' => $letter->name,
                ],
            ],
        ];

        $arrayToXml = new ArrayToXml($record, [
            'rootElementName' => 'mods',
            '_attributes' => [
                'version' => '3.7',
                //'xsi:schemaLocation' => 'http://www.loc.gov/mods/v3 https://www.loc.gov/standards/mods/v3/mods-3-7.xsd',
            ],
        ]);

        return $arrayToXml->dropXmlDeclaration()->toXml();
    }
}
