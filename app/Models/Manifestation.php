<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTenantConnection;
use App\Models\Letter;
use App\Models\Location;
use App\Models\GlobalLocation;

class Manifestation extends Model
{
    use UsesTenantConnection;

    protected $guarded = ['id'];
    protected $connection = 'tenant';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->initializeUsesTenantTable();
    }

    public function letter()
    {
        return $this->belongsTo(Letter::class);
    }

    public function repository()
    {
        return $this->belongsTo(Location::class, 'repository_id');
    }

    public function archive()
    {
        return $this->belongsTo(Location::class, 'archive_id');
    }

    public function collection()
    {
        return $this->belongsTo(Location::class, 'collection_id');
    }

    public function globalRepository()
    {
        return $this->belongsTo(GlobalLocation::class, 'global_repository_id');
    }

    public function globalArchive()
    {
        return $this->belongsTo(GlobalLocation::class, 'global_archive_id');
    }

    public function globalCollection()
    {
        return $this->belongsTo(GlobalLocation::class, 'global_collection_id');
    }
}
