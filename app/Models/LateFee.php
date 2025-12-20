<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LateFee extends Model
{
    use SoftDeletes;

    protected $table = 'late_fees';

    protected $gaurded = [];
} 