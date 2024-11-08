<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Services\Geonames;
use App\Http\Controllers\Controller;

class AjaxPlaceController extends Controller
{
    protected $geonames;

    public function __construct(Geonames $geonames)
    {
        $this->geonames = $geonames;
    }

    public function __invoke(Request $request): array
    {
        $query = $request->query('search');

        if (empty($query)) {
            return [];
        }

        try {
            $results = $this->geonames->search($query);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }

        return $results->map(function ($place) {
            return [
                'id' => $place['id'],
                'value' => $place['id'],
                'label' => "{$place['name']}, {$place['adminName']}, {$place['country']}",
            ];
        })->toArray();
    }
}
