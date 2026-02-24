<?php

namespace App\Livewire;

use App\Http\Requests\GlobalIdentityRequest;
use App\Http\Requests\IdentityRequest;
use App\Models\GlobalIdentity;
use App\Models\Identity;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class IdentitiesConsistencyCheck extends Component
{
    public $scope = 'all'; // all, local, global
    public $isScanning = false;
    public $issues = [];

    public function scan()
    {
        $this->isScanning = true;
        $this->issues = [];

        // Scan Local Identities
        if ($this->scope === 'local' || $this->scope === 'all') {
            if (tenancy()->initialized) {
                // Use the Request rules, but relax 'required' checks slightly
                // for existing data vs strict create validation if desired,
                // but usually we want to find records violating strict rules.
                $rules = (new IdentityRequest)->rules();

                Identity::chunk(200, function ($identities) use ($rules) {
                    foreach ($identities as $identity) {
                        // Prepare data for validation (e.g. relations are arrays)
                        $data = $identity->attributesToArray();

                        $validator = Validator::make($data, $rules);

                        if ($validator->fails()) {
                            foreach ($validator->errors()->all() as $error) {
                                $this->issues[] = [
                                    'type' => 'local',
                                    'id' => $identity->id,
                                    'name' => $identity->name,
                                    'error' => $error,
                                ];
                            }
                        }
                    }
                });
            }
        }

        // Scan Global Identities
        if ($this->scope === 'global' || $this->scope === 'all') {
            $rules = (new GlobalIdentityRequest)->rules();

            GlobalIdentity::chunk(200, function ($identities) use ($rules) {
                foreach ($identities as $identity) {
                    $data = $identity->attributesToArray();
                    $validator = Validator::make($data, $rules);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $error) {
                            $this->issues[] = [
                                'type' => 'global',
                                'id' => $identity->id,
                                'name' => $identity->name,
                                'error' => $error,
                            ];
                        }
                    }
                }
            });
        }

        $this->isScanning = false;
    }

    public function render()
    {
        return view('livewire.identities-consistency-check');
    }
}
