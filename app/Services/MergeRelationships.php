<?php

namespace App\Services;

use Exception;
use App\Models\Letter;
use App\Jobs\RegenerateNames;
use Illuminate\Support\Facades\DB;

class MergeRelationships
{
    public $oldId;
    public $newId;
    public $model;

    protected $affectedLetters;
    protected array $models = [
        'identity' => [
            'handler' => 'handleIdentity',
            'relationship' => 'identities',
        ],
    ];

    /**
     * @throws Exception
     */
    public function __construct($oldId, $newId, $model)
    {
        if (!array_key_exists($model, $this->models)) {
            throw new Exception(__('Not found'), 404);
        }

        $this->oldId = $oldId;
        $this->newId = $newId;
        $this->model = $model;
        $this->affectedLetters = $this->getAffectedLetters();

        $this->merge();
    }

    public function merge()
    {
        call_user_func([$this, $this->models[$this->model]['handler']]);
    }

    /**
     * @throws Exception
     */
    protected function handleIdentity()
    {
        try {
            DB::table('identity_letter')
                ->where('identity_id', '=', $this->oldId)
                ->update(['identity_id' => $this->newId]);
        } catch (\Illuminate\Database\QueryException $ex) {
            throw new Exception($ex->getMessage(), 500);
        }

        $this->affectedLetters->each(function ($letter) {
            RegenerateNames::dispatch($letter->authors()->get());
            RegenerateNames::dispatch($letter->recipients()->get());
        });
    }

    protected function getAffectedLetters()
    {
        $relationship = $this->models[$this->model]['relationship'];

        return Letter::select('id')
            ->whereHas($relationship, function ($query) use ($relationship) {
                $query->where("{$relationship}.id", '=', $this->oldId);
            })
            ->get();
    }
}
