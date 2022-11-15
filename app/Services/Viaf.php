<?php

namespace App\Services;

use Exception;

class Viaf
{
    /**
     * @throws Exception
     */
    public function search($query): \Illuminate\Support\Collection
    {
        if (empty($query)) {
            throw new Exception(
                __('validation.min.string', [
                    'attribute' => __('hiko.query'),
                    'min' => '0',
                ]),
                400
            );
        }

        try {
            $url = 'http://viaf.org/viaf/AutoSuggest?query=' . urlencode($query);
            $result = json_decode(file_get_contents($url));
        } catch (Exception $e) {
            throw new Exception(__('hiko.viaf_unavailable'), 503);
        }

        if (!$result->result) {
            throw new Exception(__('hiko.items_not_found'), 404);
        }

        return collect($result->result)->map(function ($item) {
            return [
                'name' => $item->displayForm,
                'type' => $item->nametype,
                'recordID' => $item->recordID,
            ];
        });
    }
}
