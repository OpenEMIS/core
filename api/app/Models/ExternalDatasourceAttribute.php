<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalDatasourceAttribute extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "external_data_source_attributes";
    protected $primaryKey = 'id';
    public $incrementing = false;
}
