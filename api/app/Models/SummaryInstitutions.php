<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryInstitutions extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "summary_institutions";
}
