<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaAdministratives extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'is_main_country', 'parent_id', 'lft', 'rght', 'area_administrative_level_id', 'order', 'visible', 'modified_user_id', 'modified', 'created_user_id', 'created', 'parent_id', 'area_administrative_level_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

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
