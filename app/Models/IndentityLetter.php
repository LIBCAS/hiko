<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndentityLetter extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
}
