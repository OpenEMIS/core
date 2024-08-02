<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaAdministratives extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "area_administratives";


    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }


    public function areaAdministrativesChild()
    {
        return $this->children()->with('areaAdministrativesChild');
    }

    public function areaAdministrativelevels()
    {
        return $this->belongsTo(AreaAdministrativeLevels::class, 'area_administrative_level_id', 'id');
    }
}
