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

        $dateCreated = $this->dateCreated($letter);

        if ($dateCreated) {
            $record['dateCreated'] = $dateCreated;
        }

        $arrayToXml = new ArrayToXml($record, [
            'rootElementName' => 'mods',
            '_attributes' => [
                'version' => '3.7',
                //'xsi:schemaLocation' => 'http://www.loc.gov/mods/v3 https://www.loc.gov/standards/mods/v3/mods-3-7.xsd',
            ],
        ]);

        return $arrayToXml->dropXmlDeclaration()->toXml();
    }

    protected function dateCreated($letter)
    {
        $dateCreatedStart = $this->formatDate($letter->date_day, $letter->date_month, $letter->date_year);
        $dateCreatedEnd = $letter->date_is_range ? $this->formatDate($letter->date_day, $letter->date_month, $letter->date_year) : null;

        $qualifiers = array_filter([
            $letter->date_uncertain ? 'questionable' : null,
            $letter->date_inferred ? 'inferred' : null,
            $letter->date_approximate ? 'approximate' : null,
        ]);


        if ($dateCreatedStart && $dateCreatedEnd) {
            return [
                [
                    '_attributes' => [
                        'qualifier' => implode(' ', $qualifiers),
                    ],
                    '_value' => $dateCreatedStart . '-' . $dateCreatedEnd,
                ],
                [
                    '_attributes' => [
                        'point' => 'start',
                    ],
                    '_value' => $dateCreatedStart,
                ],
                [
                    '_attributes' => [
                        'point' => 'end',
                    ],
                    '_value' => $dateCreatedEnd,
                ],
            ];
        }

        return [
            '_attributes' => [
                'qualifier' => implode(' ', $qualifiers),
            ],
            '_value' => $dateCreatedStart,
        ];
    }

    protected function formatDate($day, $month, $year)
    {
        if (!$day && !$month && !$year) {
            return null;
        }

        $date = $day ? "{$day}." : '';
        $date .= $month ? "{$month}." : '';
        $date .= $year ? "{$year}" : '';

        return $date;
    }
}
