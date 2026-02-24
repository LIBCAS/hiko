<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\GlobalIdentity;
use Illuminate\Http\Request;

class AjaxGlobalIdentityController extends Controller
{
    public function __invoke(Request $request): array
    {
        $search = trim((string)$request->query('search', ''));
        if ($search === '') {
            return [];
        }

        $type = $request->query('type');

        $query = GlobalIdentity::query()
            ->select('id', 'name', 'type', 'birth_year', 'death_year')
            ->where('name', 'like', '%' . $search . '%');

        if (!empty($type)) {
            $query->where('type', $type);
        }

        return $query
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function (GlobalIdentity $identity) {
                $dates = trim("{$identity->birth_year} - {$identity->death_year}");
                $label = $identity->name . ($dates !== ' - ' ? " ({$dates})" : '');

                return [
                    'id' => $identity->id,
                    'value' => $identity->id,
                    'label' => $label,
                    'type' => $identity->type,
                ];
            })
            ->values()
            ->toArray();
    }
}
