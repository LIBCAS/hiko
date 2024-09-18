<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalProfessionCategory extends Model
{

    protected $connection = 'mysql'; // prefix 'global_' (united DB for professions and categories)

    protected $table = 'global_professions';

    protected $guarded = ['id'];
}
