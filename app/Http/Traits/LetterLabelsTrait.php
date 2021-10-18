<?php

namespace App\Http\Traits;

use App\Models\Location;

trait LetterLabelsTrait
{
    public function getLabels()
    {
        return [
            'ms_manifestation' => [
                [
                    'value' => 'E',
                    'label' => 'Extract',
                ],
                [
                    'value' => 'S',
                    'label' => 'MS Copy',
                ],
                [
                    'value' => 'D',
                    'label' => 'MS Draft',
                ],


                [
                    'value' => 'ALS',
                    'label' => 'MS Letter',
                ],


                [
                    'value' => 'O',
                    'label' => 'Other',
                ],

                [
                    'value' => 'P',
                    'label' => 'Printed',
                ],
            ],
            'type' => [
                [
                    'value' => 'calling card',
                    'label' => 'calling card',
                ],
                [
                    'value' => 'greeting card',
                    'label' => 'greeting card',
                ],
                [
                    'value' => 'invitation card',
                    'label' => 'invitation card',
                ],
                [
                    'value' => 'letter',
                    'label' => 'letter',
                ],
                [
                    'value' => 'picture postcard',
                    'label' => 'picture postcard',
                ],
                [
                    'value' => 'postcard',
                    'label' => 'postcard',
                ],
                [
                    'value' => 'telegram',
                    'label' => 'telegram',
                ],
                [
                    'value' => 'visiting card',
                    'label' => 'visiting card',
                ],
            ],
            'preservation' => [
                [
                    'value' => 'carbon copy',
                    'label' => 'carbon copy',
                ],
                [
                    'value' => 'copy',
                    'label' => 'copy',
                ],
                [
                    'value' => 'draft',
                    'label' => 'draft',
                ],
                [
                    'value' => 'original',
                    'label' => 'original',
                ],
                [
                    'value' => 'photocopy',
                    'label' => 'photocopy',
                ],
            ],
            'copy' => [
                [
                    'value' => 'handwritten',
                    'label' => 'handwritten',
                ],
                [
                    'value' => 'typewritten',
                    'label' => 'typewritten',
                ],
            ],
        ];
    }

    public function getLocations()
    {
        return Location::select(['name', 'type'])
            ->get()
            ->groupBy('type')
            ->toArray();
    }
}
